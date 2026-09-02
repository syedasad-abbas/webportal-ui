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

# Optional explicit overrides. With neither set, vars.xml advertises the
# detected host address for both SIP and RTP.
sip_ip_override="${FREESWITCH_EXTERNAL_SIP_IP:-${EXTERNAL_IP:-}}"
rtp_ip_override="${FREESWITCH_EXTERNAL_RTP_IP:-${EXTERNAL_IP:-}}"
if [ -n "$sip_ip_override" ] || [ -n "$rtp_ip_override" ]; then
    vars_file="/etc/freeswitch/vars.xml"
    if [ -f "$vars_file" ]; then
        if [ -n "$sip_ip_override" ]; then
            sed -i -E "s|data=\"external_sip_ip=[^\"]*\"|data=\"external_sip_ip=${sip_ip_override}\"|" "$vars_file"
            echo "[freeswitch] Overrode external_sip_ip with $sip_ip_override"
        fi
        if [ -n "$rtp_ip_override" ]; then
            sed -i -E "s|data=\"external_rtp_ip=[^\"]*\"|data=\"external_rtp_ip=${rtp_ip_override}\"|" "$vars_file"
            echo "[freeswitch] Overrode external_rtp_ip with $rtp_ip_override"
        fi
    fi
fi

# Start the XML reload watcher in the background
/usr/local/bin/reload-watcher.sh &

# Start FreeSWITCH (original entrypoint)
exec /usr/bin/freeswitch -nc -nf -nonat
