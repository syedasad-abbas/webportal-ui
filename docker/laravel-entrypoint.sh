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

if [ -n "${FREESWITCH_DIRECTORY_PATH:-}" ]; then
  mkdir -p "$FREESWITCH_DIRECTORY_PATH"
  chown -R www-data:www-data "$FREESWITCH_DIRECTORY_PATH"
  chmod -R ug+rwX "$FREESWITCH_DIRECTORY_PATH"
fi

service cron start

if [ "$#" -eq 0 ]; then
  set -- apache2-foreground
fi

exec "$@"
