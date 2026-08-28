#!/bin/sh
# Entrypoint script for FreeSWITCH container

# FreeSWITCH uses host networking, so it is the authoritative place to detect
# the host machine's LAN address. Publish that address through the shared
# gateway volume for the bridged backend container to use for ESL.
host_ip_file="${FREESWITCH_HOST_IP_FILE:-/etc/freeswitch/gateways/.host-ip}"
host_ip="$(/usr/local/bin/detect-host-ip.sh 2>/dev/null || true)"
if [ -n "$host_ip" ]; then
    mkdir -p "$(dirname "$host_ip_file")"
    printf '%s\n' "$host_ip" > "$host_ip_file"
    echo "[freeswitch] Published host IP $host_ip to $host_ip_file"
else
    echo "[freeswitch] Warning: unable to detect the host machine IP" >&2
fi

# Start the XML reload watcher in the background
/usr/local/bin/reload-watcher.sh &

# Start FreeSWITCH (original entrypoint)
exec /usr/bin/freeswitch -nc -nf -nonat
