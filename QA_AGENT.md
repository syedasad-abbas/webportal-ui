# Autonomous UI QA Agent

Run one UI audit:

```bash
npm --prefix backend run qa:ui
```

Run the diagnose, repair, and retest loop:

```bash
./scripts/qa-agent-loop.sh
```

The loop runs `backend/qa-ui-webdriver.js` with a fresh headless Firefox session
for every round. Reports, screenshots, UI logs, and repair summaries are saved
under `.qa-artifacts/`. Functional actions use the UI; API and network evidence
is restricted to diagnostics and log tracing. The application, browser driver,
and dedicated QA account must be available before starting.

The runner covers the scenarios implemented in its inventory: navigation,
dashboard, dialer controls, tabs, inputs, theme, audio configuration, admin
pages, and responsive layout where available. Review the report for precisely
what passed, failed, or was blocked. An element being present is not proof that
its full workflow works. This finite suite cannot certify every possible action,
data combination, browser, permission level, or external integration.

After a failure, the repair agent classifies it as a product bug, test bug, or
environment/external-service blocker. It may edit the repository to fix a
confirmed bug, then a fresh UI run checks the result. Existing unrelated edits
must be preserved. A changed assertion must still verify the intended visible
behavior. Unmet prerequisites and untested behavior belong in blocked coverage.

Completion requires the configured number of consecutive clean runs (two by
default), valid reports, at least one passing check, and no failed or blocked
checks. Three consecutive runs with the same failing check names stop the loop;
volatile error details do not reset this counter. Timeouts and the round limit
also stop it. The final round is never followed by an unverified repair.
A repository lock prevents overlapping loops, including recursive invocation.
The lock file can remain after exit; the operating-system lock is released when
the process exits.

Requirements for the loop are Bash, Node.js, GNU `timeout`, `flock`, `tee`, and
Codex CLI for repairs. Repairs run with `workspace-write` sandboxing and no
interactive approval prompts; restrictions or missing access may block a repair.
Do not run multiple independent repair tools against the same checkout.

Useful controls:

- `QA_AGENT_MAX_ROUNDS=12` changes the maximum number of UI rounds (default 8).
- `QA_REQUIRED_CLEAN_RUNS=2` changes the convergence threshold.
- `QA_AUTO_FIX=false` audits without invoking repair agents.
- `QA_TEST_TIMEOUT_SECONDS=600` bounds each UI runner invocation.
- `QA_REPAIR_TIMEOUT_SECONDS=1200` bounds each repair invocation.
- `QA_ARTIFACT_ROOT=/absolute/path` changes the evidence directory.
- `QA_CODEX_BIN=/path/to/codex` selects the repair CLI.
- `QA_EMAIL` and `QA_PASSWORD` select the dedicated QA account.
- `QA_BASE_URL` (default `http://127.0.0.1:8080`) and `QA_API_HEALTH_URL` select the portal and diagnostic API.
- `QA_ECHO_NUMBER=9196` enables the user-authorized controlled echo fixture when supported by the runner.

Positive integer controls accept at most six digits. The clean-run threshold
must fit within the round limit. Exit codes are `0` for covered checks passing,
`1` for failures with auto-fix disabled, and `2` for blockers, invalid
configuration, timeouts, repeated failures, or exhausted rounds.

Machine reports require `passed` and `failed` arrays and may include `blocked`
and `inventory` arrays. Each passed, failed, or blocked check has a nonempty
`name` and may carry `detail`. Missing or malformed reports never count as a
clean run, even when the test process exits successfully.

Real PSTN/SIP audio verification requires a controlled echo destination or an
inbound-call fixture. Audio element and WebRTC control checks alone cannot
prove audible two-way sound, ringtone playback, or microphone capture. Calls outside the explicitly authorized controlled echo fixture, destructive actions on non-QA data, credentials changes, and commits are
excluded from automatic repairs. End-to-end call sound, external-service
workflows, and any other missing fixtures must remain explicitly unresolved
until exercised through the UI with suitable QA data.
