#!/bin/sh
# Entrypoint script for FreeSWITCH container

# Apply the ESL password before FreeSWITCH starts.
/usr/local/bin/configure-event-socket.sh || exit 1

# SIP/RTP bind IP, external IP, and the RTP port range are all resolved
# natively inside conf/vars.xml via FreeSWITCH's own X-PRE-PROCESS
# cmd="exec-set" directives (using local_ip_v4, which FreeSWITCH auto-detects
# correctly because this container always runs with network_mode: host).
# No script-based IP detection or config patching is needed here.

# Start the XML reload watcher in the background
/usr/local/bin/reload-watcher.sh &

# Start FreeSWITCH (original entrypoint)
exec /usr/bin/freeswitch -nc -nf -nonat
