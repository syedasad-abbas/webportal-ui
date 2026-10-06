#!/usr/bin/env bash
set -uo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ARTIFACT_ROOT="${QA_ARTIFACT_ROOT:-$ROOT_DIR/.qa-artifacts/autonomous}"
APP_CONTAINER="${QA_APP_CONTAINER:-webportal-ui-app-1}"
MAX_ROUNDS="${QA_AGENT_MAX_ROUNDS:-8}"
MAX_REPAIRS="${QA_AGENT_MAX_REPAIRS:-3}"
AUTO_FIX="${QA_AUTO_FIX:-true}"
BASE_URL="${QA_BASE_URL:-http://127.0.0.1:8080}"
API_HEALTH_URL="${QA_API_HEALTH_URL:-http://127.0.0.1:4000/health}"
CODEX_BIN="${QA_CODEX_BIN:-codex}"

if ! [[ "$MAX_ROUNDS" =~ ^[1-9][0-9]{0,5}$ ]] || ! [[ "$MAX_REPAIRS" =~ ^[0-9]+$ ]]; then
  echo "QA_BLOCKED: round and repair limits must be non-negative positive integers." >&2
  exit 2
fi
if ! [[ "$AUTO_FIX" =~ ^(true|false)$ ]]; then
  echo "QA_BLOCKED: QA_AUTO_FIX must be true or false." >&2
  exit 2
fi

for dependency in docker curl node timeout flock tee; do
  if ! command -v "$dependency" >/dev/null 2>&1; then
    echo "QA_BLOCKED: required command is missing: $dependency" >&2
    exit 2
  fi
done

if ! docker inspect "$APP_CONTAINER" >/dev/null 2>&1; then
  echo "QA_BLOCKED: application container $APP_CONTAINER is unavailable." >&2
  exit 2
fi

mkdir -p "$ARTIFACT_ROOT"
ARTIFACT_ROOT="$(cd "$ARTIFACT_ROOT" && pwd)"
exec 9>"$ROOT_DIR/.qa-autonomous-loop.lock"
if ! flock -n 9; then
  echo "QA_BLOCKED: autonomous QA loop is already running." >&2
  exit 2
fi

cleanup() {
  if [[ -f "$ARTIFACT_ROOT/current-api-trace.json" ]]; then
    node "$ROOT_DIR/backend/qa-loop-agent.js" "$ARTIFACT_ROOT/current-api-trace.json" >/dev/null 2>&1 || true
  fi
}
trap cleanup EXIT

run_api_trace() {
  run_id="$1"
  local trace="$ARTIFACT_ROOT/$run_id/api-trace.json"
  local status
  status="$(curl --silent --show-error --max-time 10 -o "$trace" -w '%{http_code}' "$API_HEALTH_URL" || true)"
  printf '{"runId":"%s","url":"%s","status":%s,"body":%s}\n' \
    "$run_id" "$API_HEALTH_URL" "$status" "$(cat "$trace" 2>/dev/null || printf '{}')" > "$trace"
}

sync_changed_files() {
  local changed_file target_path destination
  local changed_list
  changed_list="$(git -C "$ROOT_DIR" diff --name-only -- backend/src backend/qa-ui-loop.js backend/qa-loop-agent.js backend/qa-loop-agent.test.js laravel/; git -C "$ROOT_DIR" ls-files --others --exclude-standard -- backend/src backend/qa-ui-loop.js backend/qa-loop-agent.js backend/qa-loop-agent.test.js laravel/)"
  while IFS= read -r changed_file; do
    [[ -z "$changed_file" ]] && continue
    case "$changed_file" in
      backend/src/*|backend/qa-ui-loop.js|backend/qa-loop-agent.js|backend/qa-loop-agent.test.js)
        target_path="${changed_file#backend/}"
        destination="/app/$target_path"
        ;;
      laravel/*)
        target_path="${changed_file#laravel/}"
        destination="/var/www/html/$target_path"
        ;;
      *)
        continue
        ;;
    esac
    docker exec "$APP_CONTAINER" mkdir -p "$(dirname "$destination")" || return 1
    docker cp "$ROOT_DIR/$changed_file" "$APP_CONTAINER:$destination" || return 1
  done <<< "$changed_list"
}

run_ui_test() {
  run_id="$1"
  local log="$ARTIFACT_ROOT/$run_id/ui.log"
  local report="$ARTIFACT_ROOT/$run_id/ui-report.json"
  local container_report="/tmp/qa-loop-report-$run_id.json"
  docker cp "$ROOT_DIR/backend/qa-ui-loop.js" "$APP_CONTAINER:/app/qa-ui-loop.js"
  docker exec \
    -e QA_BASE_URL="$BASE_URL" \
    -e QA_API_HEALTH_URL="$API_HEALTH_URL" \
    -e QA_REPORT_PATH="$container_report" \
    -e QA_MAX_CYCLES=3 \
    -e QA_HEADLESS=true \
    "$APP_CONTAINER" node /app/qa-ui-loop.js > "$log" 2>&1
  test_status=$?
  if [[ $test_status -ne 0 ]]; then
    echo "QA_RESULT: UI runner failed with exit $test_status in round $round" >&2
  fi
  if docker exec "$APP_CONTAINER" test -f "$container_report"; then
    docker cp "$APP_CONTAINER:$container_report" "$report"
  fi
  return "$test_status"
}

repeat_failure_count=0
previous_failure_fingerprint=""
failed_rounds=0

for ((round = 1; round <= MAX_ROUNDS; round += 1)); do
  run_id="$(date -u +%Y%m%dT%H%M%SZ)-round-$round"
  mkdir -p "$ARTIFACT_ROOT/$run_id"
  echo "=== autonomous UI QA round $round/$MAX_ROUNDS ==="
  run_api_trace "$run_id"

  set +e
  run_ui_test "$run_id"
  ui_status=$?
  set -e

  failure_fingerprint="$(grep -E 'FAIL \[cycle|QA_RESULT' "$ARTIFACT_ROOT/$run_id/ui.log" 2>/dev/null | sed -E 's/.*\[cycle [0-9]+\] / /; s/ - .*//' | sort -u | sha256sum | cut -d' ' -f1 || true)"
  if [[ -z "$failure_fingerprint" ]]; then
    failure_fingerprint="$(date +%s)"
  fi

  if [[ $ui_status -eq 0 ]]; then
    echo "QA_COMPLETE: UI runner returned success in round $round."
    exit 0
  fi

  if [[ "$failure_fingerprint" == "$previous_failure_fingerprint" ]]; then
    repeat_failure_count=$((repeat_failure_count + 1))
  else
    repeat_failure_count=1
  fi
  previous_failure_fingerprint="$failure_fingerprint"

  if [[ $repeat_failure_count -ge 3 ]]; then
    echo "QA_BLOCKED: same UI failures repeated three times; stopping to avoid an infinite repair loop." >&2
    exit 2
  fi

  if [[ "$AUTO_FIX" != "true" ]]; then
    echo "QA_FAILED: failures are recorded; automatic repair is disabled." >&2
    exit 1
  fi

  if [[ $round -ge $MAX_ROUNDS ]]; then
    echo "QA_BLOCKED: maximum rounds exhausted." >&2
    exit 2
  fi

  if ! command -v "$CODEX_BIN" >/dev/null 2>&1; then
    echo "QA_BLOCKED: repair agent $CODEX_BIN is unavailable." >&2
    exit 2
  fi

  cat > "$ARTIFACT_ROOT/$run_id/repair-prompt.txt" <<PROMPT
You are the repair stage for the autonomous QA loop in $ROOT_DIR.

Evidence:
- UI log: $ARTIFACT_ROOT/$run_id/ui.log
- API trace: $ARTIFACT_ROOT/$run_id/api-trace.json

Rules:
1. Classify every failure as product bug, test bug, or environment/external blocker.
2. For confirmed product bugs, make the smallest safe fix and preserve unrelated edits.
3. For stale assertions, change only the test and retain the intended behavior.
4. Do not change credentials, call arbitrary numbers, delete non-QA data, commit, or run this loop recursively.
5. Use API traces only to explain a UI failure; all functional actions must be driven through the UI.
6. Retest locally and capture the exact command and result.
7. Stop if the failure is an external dependency or unavailable fixture and record it as blocked.
PROMPT

  timeout --kill-after=15s 1200s "$CODEX_BIN" --ask-for-approval never exec \
    --cd "$ROOT_DIR" --sandbox workspace-write \
    --output-last-message "$ARTIFACT_ROOT/$run_id/repair-summary.txt" \
    "$(cat "$ARTIFACT_ROOT/$run_id/repair-prompt.txt")" \
    > "$ARTIFACT_ROOT/$run_id/repair.log" 2>&1
  repair_status=$?
  if [[ $repair_status -ne 0 ]]; then
    echo "QA_BLOCKED: repair agent failed or timed out (exit $repair_status)." >&2
    exit 2
  fi
  if ! sync_changed_files; then
    echo "QA_BLOCKED: repaired files could not be synchronized into the application container." >&2
    exit 2
  fi
  if ! docker exec "$APP_CONTAINER" supervisorctl restart backend apache >/dev/null 2>&1; then
    echo "QA_BLOCKED: application services could not be restarted after repair." >&2
    exit 2
  fi
  failed_rounds=$((failed_rounds + 1))
  if [[ $failed_rounds -ge $MAX_REPAIRS ]]; then
    echo "QA_BLOCKED: maximum repair attempts exhausted." >&2
    exit 2
  fi
done

echo "QA_BLOCKED: no clean run was reached within $MAX_ROUNDS rounds." >&2
exit 2
