Local development URL: http://localhost:8080

## Local setup

1. Copy `backend/.env` and `laravel/.env` if needed, adjust secrets.
2. Ensure Docker Desktop exposes `host.docker.internal` (default on macOS/Windows; on Linux Compose adds the host-gateway entry).
3. Run `docker compose up --build` from the repo root.
4. Access the UI on `http://localhost:8080` and the API on `http://localhost:4000`.



SELECT id, user_id, duration_seconds, connected_at, ended_at
FROM call_logs
ORDER BY created_at DESC
LIMIT 10;

\watch 2
