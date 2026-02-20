#!/bin/bash
set -euo pipefail

cd /var/www/html

if ! command -v npm >/dev/null 2>&1; then
  echo "npm is not available in this container." >&2
  exit 1
fi

if [ ! -d node_modules ]; then
  echo "[entrypoint] installing npm dependencies..."
  npm install
fi

if [ ! -f public/build/manifest.json ]; then
  echo "[entrypoint] building Vite assets..."
  npm run build
fi

service cron start

if [ "$#" -eq 0 ]; then
  set -- apache2-foreground
fi

exec "$@"
