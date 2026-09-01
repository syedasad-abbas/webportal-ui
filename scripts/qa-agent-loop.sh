#!/usr/bin/env bash
set -uo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ARTIFACT_ROOT="${QA_ARTIFACT_ROOT:-$ROOT_DIR/.qa-artifacts}"
MAX_ROUNDS="${QA_AGENT_MAX_ROUNDS:-8}"
REQUIRED_CLEAN_RUNS="${QA_REQUIRED_CLEAN_RUNS:-2}"
AUTO_FIX="${QA_AUTO_FIX:-true}"
CODEX_BIN="${QA_CODEX_BIN:-codex}"

mkdir -p "$ARTIFACT_ROOT"
clean_runs=0
previous_fingerprint=""
repeated_failure_count=0

echo "QA agent loop: up to $MAX_ROUNDS rounds; $REQUIRED_CLEAN_RUNS clean UI runs required."

for ((round = 1; round <= MAX_ROUNDS; round += 1)); do
  run_id="$(date -u +%Y%m%dT%H%M%SZ)-round-$round"
  round_dir="$ARTIFACT_ROOT/$run_id"
  report="$round_dir/report.json"
  mkdir -p "$round_dir"

  echo
  echo "=== UI QA round $round/$MAX_ROUNDS ($run_id) ==="
  QA_RUN_ID="$run_id" \
  QA_ARTIFACT_DIR="$round_dir" \
  QA_REPORT_PATH="$report" \
  node "$ROOT_DIR/backend/qa-ui-webdriver.js" 2>&1 | tee "$round_dir/ui-qa.log"
  test_status=${PIPESTATUS[0]}

  if [[ $test_status -eq 0 ]]; then
    clean_runs=$((clean_runs + 1))
    echo "Clean UI run $clean_runs/$REQUIRED_CLEAN_RUNS."
    if [[ $clean_runs -ge $REQUIRED_CLEAN_RUNS ]]; then
      echo "QA_COMPLETE: all covered UI functionality passed twice from fresh browser sessions."
      exit 0
    fi
    continue
  fi

  clean_runs=0
  if [[ ! -f "$report" ]]; then
    echo "QA_BLOCKED: the UI runner crashed before producing $report"
    exit 2
  fi

  fingerprint="$(node -e 'const fs=require("fs"),crypto=require("crypto");const report=JSON.parse(fs.readFileSync(process.argv[1],"utf8"));process.stdout.write(crypto.createHash("sha256").update(JSON.stringify(report.failed||[])).digest("hex"));' "$report")"
  if [[ "$fingerprint" == "$previous_fingerprint" ]]; then
    repeated_failure_count=$((repeated_failure_count + 1))
  else
    repeated_failure_count=0
  fi
  previous_fingerprint="$fingerprint"

  if [[ "$AUTO_FIX" != "true" ]]; then
    echo "QA_FAILED: failures remain; auto-fix is disabled. See $report"
    exit 1
  fi
  if [[ $repeated_failure_count -ge 2 ]]; then
    echo "QA_BLOCKED: the same report repeated three times. See $report"
    exit 2
  fi
  if ! command -v "$CODEX_BIN" >/dev/null 2>&1; then
    echo "QA_BLOCKED: Codex CLI was not found. Set QA_CODEX_BIN or install Codex."
    exit 2
  fi

  agent_prompt="You are the repair stage of an autonomous QA loop for $ROOT_DIR.
Read the machine report at $report and its screenshots/logs in $round_dir.
Classify every failure as product bug, test bug, or environment/external-service blocker before editing.
For confirmed product bugs, make the smallest safe fix and run focused static/unit checks for touched code.
For stale or incorrect QA assertions, correct the test without weakening the user-observable behavior being checked.
All functional testing must be driven through the UI. API/network data may only diagnose or trace a UI failure.
Do not place a real phone call, delete non-QA data, change credentials, discard existing user edits, commit, or run scripts/qa-agent-loop.sh recursively.
Preserve unrelated dirty-worktree changes. End with a concise list of classifications, files changed, and checks run."

  echo "Starting repair agent for round $round..."
  "$CODEX_BIN" exec \
    --cd "$ROOT_DIR" \
    --sandbox workspace-write \
    --ask-for-approval never \
    --output-last-message "$round_dir/repair-summary.txt" \
    "$agent_prompt" 2>&1 | tee "$round_dir/repair-agent.log"
  repair_status=${PIPESTATUS[0]}
  if [[ $repair_status -ne 0 ]]; then
    echo "QA_BLOCKED: repair agent failed. See $round_dir/repair-agent.log"
    exit 2
  fi
done

echo "QA_BLOCKED: reached $MAX_ROUNDS rounds without $REQUIRED_CLEAN_RUNS consecutive clean runs."
exit 2
