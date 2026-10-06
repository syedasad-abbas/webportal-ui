#!/usr/bin/env bash
set -uo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ARTIFACT_ROOT="${QA_ARTIFACT_ROOT:-$ROOT_DIR/.qa-artifacts}"
MAX_ROUNDS="${QA_AGENT_MAX_ROUNDS:-8}"
REQUIRED_CLEAN_RUNS="${QA_REQUIRED_CLEAN_RUNS:-2}"
AUTO_FIX="${QA_AUTO_FIX:-true}"
CODEX_BIN="${QA_CODEX_BIN:-codex}"
TEST_TIMEOUT="${QA_TEST_TIMEOUT_SECONDS:-600}"
REPAIR_TIMEOUT="${QA_REPAIR_TIMEOUT_SECONDS:-1200}"

for setting in MAX_ROUNDS REQUIRED_CLEAN_RUNS TEST_TIMEOUT REPAIR_TIMEOUT; do
  if [[ ! ${!setting} =~ ^[1-9][0-9]{0,5}$ ]]; then
    echo "QA_BLOCKED: $setting must be a positive integer of at most six digits."
    exit 2
  fi
done
if [[ $REQUIRED_CLEAN_RUNS -gt $MAX_ROUNDS || ! $AUTO_FIX =~ ^(true|false)$ ]]; then
  echo 'QA_BLOCKED: clean-run threshold must fit within the round limit; QA_AUTO_FIX must be true or false.'
  exit 2
fi
for dependency in node timeout flock tee; do
  if ! command -v "$dependency" >/dev/null 2>&1; then
    echo "QA_BLOCKED: required command is missing: $dependency"
    exit 2
  fi
done
mkdir -p "$ARTIFACT_ROOT" || exit 2
ARTIFACT_ROOT="$(cd "$ARTIFACT_ROOT" && pwd)" || exit 2
# A repository-wide lock also covers callers using different artifact roots.
exec 9>"$ROOT_DIR/.qa-agent-loop.lock" || exit 2
if ! flock -n 9; then
  echo 'QA_BLOCKED: another QA loop is already running in this repository.'
  exit 2
fi
clean_runs=0
previous_fingerprint=""
repeated_failure_count=0

echo "QA agent loop: up to $MAX_ROUNDS rounds; $REQUIRED_CLEAN_RUNS clean UI runs required."

for ((round = 1; round <= MAX_ROUNDS; round += 1)); do
  run_id="$(date -u +%Y%m%dT%H%M%SZ)-$$-round-$round"
  round_dir="$ARTIFACT_ROOT/$run_id"
  report="$round_dir/report.json"
  mkdir -p "$round_dir" || exit 2

  echo "UI QA round $round/$MAX_ROUNDS ($run_id)"
  QA_RUN_ID="$run_id" \
  QA_ARTIFACT_DIR="$round_dir" \
  QA_REPORT_PATH="$report" \
  timeout --kill-after=15s "${TEST_TIMEOUT}s" node "$ROOT_DIR/backend/qa-ui-webdriver.js" 2>&1 | tee "$round_dir/ui-qa.log"
  statuses=("${PIPESTATUS[@]}")
  test_status=${statuses[0]}
  if [[ ${statuses[1]} -ne 0 || $test_status -eq 124 || $test_status -eq 137 ]]; then
    echo "QA_BLOCKED: UI run timed out or its log could not be saved. See $round_dir"
    exit 2
  fi

  # Validate even successful processes: exit zero alone is not evidence of coverage.
  metadata="$(node - "$report" <<'NODE'
const fs = require('fs'), crypto = require('crypto');
try {
  const report = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
  for (const key of ['passed', 'failed']) {
    if (!Array.isArray(report[key])) throw new Error(`${key} must be an array`);
  }
  if (report.blocked !== undefined && !Array.isArray(report.blocked)) throw new Error('blocked must be an array');
  const blocked = report.blocked || [];
  for (const entry of [...report.passed, ...report.failed, ...blocked]) {
    if (!entry || typeof entry.name !== 'string' || !entry.name.trim()) throw new Error('checks require nonempty names');
  }
  // Failure details may contain timestamps, ports, and generated QA IDs. Stable
  // check names identify lack of progress without volatile diagnostic text.
  const names = [...new Set(report.failed.map(item => item.name))].sort();
  const fingerprint = crypto.createHash('sha256').update(JSON.stringify(names)).digest('hex');
  process.stdout.write(`${report.passed.length} ${report.failed.length} ${blocked.length} ${fingerprint}`);
} catch (error) {
  console.error(`Invalid or missing report: ${error.message}`);
  process.exit(1);
}
NODE
)" || { echo "QA_BLOCKED: cannot assess UI results in $report"; exit 2; }
  read -r passed_count failed_count blocked_count fingerprint <<< "$metadata"

  if [[ $failed_count -eq 0 ]]; then
    if [[ $test_status -ne 0 || $blocked_count -gt 0 || $passed_count -eq 0 ]]; then
      echo "QA_BLOCKED: $blocked_count blocked checks; runner exit $test_status; $passed_count passed checks. See $report"
      exit 2
    fi
    clean_runs=$((clean_runs + 1))
    echo "Clean UI run $clean_runs/$REQUIRED_CLEAN_RUNS."
    if [[ $clean_runs -ge $REQUIRED_CLEAN_RUNS ]]; then
      echo "QA_COMPLETE: covered checks passed in $REQUIRED_CLEAN_RUNS consecutive fresh browser runs. This does not certify untested functionality. See $report"
      exit 0
    fi
    continue
  fi

  clean_runs=0
  if [[ "$fingerprint" == "$previous_fingerprint" ]]; then
    repeated_failure_count=$((repeated_failure_count + 1))
  else
    repeated_failure_count=1
  fi
  previous_fingerprint="$fingerprint"

  if [[ "$AUTO_FIX" != "true" ]]; then
    echo "QA_FAILED: failures remain; auto-fix is disabled. See $report"
    exit 1
  fi
  if [[ $repeated_failure_count -ge 3 ]]; then
    echo "QA_BLOCKED: the same failing checks repeated three times. See $report"
    exit 2
  fi
  if [[ $round -eq $MAX_ROUNDS ]]; then
    break
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
Record unmet prerequisites and untested behavior as blocked coverage rather than passing checks.
All functional testing must be driven through the UI. API/network data may only diagnose or trace a UI failure.
Only call a controlled echo fixture explicitly authorized by the user and configured in QA_ECHO_NUMBER. Do not call other numbers, delete non-QA data, change credentials, discard existing user edits, commit, or run scripts/qa-agent-loop.sh recursively.
Verify that the local test server serves the changed files before retesting. If source is not mounted into a local container, synchronize only your changed application files and clear its compiled view cache; never deploy to a remote or production server. Report unavailable local runtime access as blocked.
Preserve unrelated dirty-worktree changes. End with a concise list of classifications, files changed, and checks run."

  echo "Starting repair agent for round $round..."
  timeout --kill-after=15s "${REPAIR_TIMEOUT}s" "$CODEX_BIN" --ask-for-approval never exec \
    --cd "$ROOT_DIR" \
    --sandbox workspace-write \
    --output-last-message "$round_dir/repair-summary.txt" \
    "$agent_prompt" 2>&1 | tee "$round_dir/repair-agent.log"
  statuses=("${PIPESTATUS[@]}")
  if [[ ${statuses[0]} -ne 0 || ${statuses[1]} -ne 0 ]]; then
    echo "QA_BLOCKED: repair agent failed, timed out, or its log could not be saved. See $round_dir/repair-agent.log"
    exit 2
  fi
done

echo "QA_BLOCKED: reached $MAX_ROUNDS rounds without $REQUIRED_CLEAN_RUNS consecutive clean runs. Latest evidence: $report"
exit 2
