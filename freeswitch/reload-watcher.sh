#!/bin/sh
# Watch for reload trigger file and reload FreeSWITCH XML
# This runs inside the FreeSWITCH container

WATCH_DIR="/etc/freeswitch/directory/webphone"
TRIGGER_FILE="$WATCH_DIR/.reload_trigger"
FS_PASSWORD="${EVENT_SOCKET_PASSWORD:-ClueCon}"

fs_command() {
  fs_cli -p "$FS_PASSWORD" -x "$1"
}

detect_bind_ip() {
  if [ -n "${FREESWITCH_BIND_IP:-}" ]; then
    printf '%s\n' "$FREESWITCH_BIND_IP"
    return
  fi

  # Prefer the address used by the current default route. This avoids Docker
  # bridge addresses on hosts with more than one IPv4 interface.
  ip -4 route get 1.1.1.1 2>/dev/null \
    | awk '{ for (i = 1; i <= NF; i += 1) if ($i == "src") { print $(i + 1); exit } }'
}

recover_sip_profiles() {
  echo "[watcher] Network address changed; reloading SIP profiles..."
  fs_command "reloadxml" > /dev/null 2>&1 || return 1

  # FreeSWITCH stops IPv4 Sofia profiles when their former bind address is no
  # longer local. Reloading XML alone does not bring those profiles back.
  fs_command "sofia profile internal start" > /dev/null 2>&1 || true
  fs_command "sofia profile external start" > /dev/null 2>&1 || true
  echo "[watcher] SIP profile recovery complete"
}

echo "[watcher] Starting XML reload watcher..."

# Initial reload to ensure FreeSWITCH picks up existing files
sleep 5
fs_command "reloadxml" > /dev/null 2>&1
echo "[watcher] Initial reload complete"

LAST_BIND_IP="$(detect_bind_ip)"

# Watch for trigger file and reload
while true; do
  if [ -f "$TRIGGER_FILE" ]; then
    rm -f "$TRIGGER_FILE"
    fs_command "reloadxml" > /dev/null 2>&1
    echo "[watcher] XML reloaded"
  fi

  CURRENT_BIND_IP="$(detect_bind_ip)"
  if [ -n "$CURRENT_BIND_IP" ] && [ -n "$LAST_BIND_IP" ] && [ "$CURRENT_BIND_IP" != "$LAST_BIND_IP" ]; then
    # Allow FreeSWITCH's IP-change event to finish before restoring profiles
    # from freshly preprocessed XML containing the new address.
    sleep 2
    recover_sip_profiles
  fi
  if [ -n "$CURRENT_BIND_IP" ]; then
    LAST_BIND_IP="$CURRENT_BIND_IP"
  fi
  sleep 2
done
