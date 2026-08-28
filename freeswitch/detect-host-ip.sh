#!/bin/sh

# This container uses network_mode: host. The source address selected for the
# default route is therefore the host machine address FreeSWITCH listens on.
if [ -n "$HOST_IP" ]; then
    printf '%s\n' "$HOST_IP"
    exit 0
fi

host_ip="$(ip -4 route get 1.1.1.1 2>/dev/null | sed -n 's/.*src \([0-9.]*\).*/\1/p' | head -n1)"
if [ -z "$host_ip" ]; then
    host_ip="$(ip -4 addr show scope global 2>/dev/null | sed -n 's/.*inet \([0-9.]*\)\/.*/\1/p' | head -n1)"
fi

printf '%s\n' "$host_ip"
