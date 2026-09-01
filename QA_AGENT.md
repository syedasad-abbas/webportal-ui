# Autonomous UI QA Agent

Run the safe UI-only audit once:

```bash
npm --prefix backend run qa:ui
```

Run the diagnose, repair, and retest loop:

```bash
./scripts/qa-agent-loop.sh
```

The loop starts a fresh headless Firefox session each round, logs in through the
portal, exercises the dashboard, navigation, dialer, tabs, inputs, theme,
audio configuration, admin pages, and responsive layout, and writes JSON,
screenshots, and logs under `.qa-artifacts/`. Functional actions are performed
only in the UI. The backend health API and captured browser resource metadata
are diagnostic evidence, not substitutes for UI tests.

When a run fails, the repair stage first classifies the result as a product bug,
test bug, or external/environment blocker. It may patch the repository for a
confirmed bug, then the outer process creates a new browser session and reruns
the full suite. Completion requires two consecutive clean runs. Three identical
failure reports or the configured round limit stop the process as blocked so a
bad assertion or unavailable carrier cannot cause an infinite edit loop.

Useful controls:

- `QA_AGENT_MAX_ROUNDS=12` changes the repair limit.
- `QA_REQUIRED_CLEAN_RUNS=2` changes the convergence threshold.
- `QA_AUTO_FIX=false` runs the UI audit without invoking Codex repairs.
- `QA_EMAIL` and `QA_PASSWORD` select the dedicated QA account.
- `QA_BASE_URL` and `QA_API_HEALTH_URL` select the portal and diagnostic API.

Real PSTN/SIP sound verification requires a controlled echo destination or an
inbound-call fixture. The suite safely verifies the remote-audio element and
WebRTC controls, but intentionally does not call an arbitrary real number.
