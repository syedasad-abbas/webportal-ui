#!/bin/sh
# Detect the host machine's primary IPv4 address (the one used for the default
# route). This is the address containers should use to reach services bound to
# the host (e.g. FreeSWITCH running with network_mode: host), and the address
# FreeSWITCH should advertise for SIP/RTP.
#
# It follows whatever network the host is on (192.168.1.x, 192.168.18.x, ...)
# with no hardcoding. Override by setting HOST_IP in the environment.

if [ -n "$HOST_IP" ]; then
    echo "$HOST_IP"
    exit 0
fi

# Preferred: the source address selected for the default route.
ip=$(ip -4 route get 1.1.1.1 2>/dev/null | sed -n 's/.*src \([0-9.]*\).*/\1/p' | head -n1)

# Fallback: first global non-loopback IPv4.
if [ -z "$ip" ]; then
    ip=$(ip -4 addr show scope global 2>/dev/null | sed -n 's/.*inet \([0-9.]*\)\/.*/\1/p' | head -n1)
fi

echo "$ip"
