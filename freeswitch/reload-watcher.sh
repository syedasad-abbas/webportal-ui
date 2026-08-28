#!/bin/sh
# Watch for reload trigger file and reload FreeSWITCH XML
# This runs inside the FreeSWITCH container

WATCH_DIR="/etc/freeswitch/directory/webphone"
TRIGGER_FILE="$WATCH_DIR/.reload_trigger"

echo "[watcher] Starting XML reload watcher..."

# Initial reload to ensure FreeSWITCH picks up existing files
sleep 5
fs_cli -x "reloadxml" > /dev/null 2>&1
echo "[watcher] Initial reload complete"

# Watch for trigger file and reload
while true; do
  if [ -f "$TRIGGER_FILE" ]; then
    rm -f "$TRIGGER_FILE"
    fs_cli -x "reloadxml" > /dev/null 2>&1
    echo "[watcher] XML reloaded"
  fi
  sleep 2
done
