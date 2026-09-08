#!/bin/sh
# Configure only ESL authentication; leave SIP profiles and requests untouched.
set -eu

socket_config="${1:-/etc/freeswitch/autoload_configs/event_socket.conf.xml}"
if [ -z "${EVENT_SOCKET_PASSWORD:-}" ]; then
    exit 0
fi

# ESL authentication is line-oriented.
case "$EVENT_SOCKET_PASSWORD" in
    *'
'*|*"$(printf '\r')"*)
        echo '[freeswitch] EVENT_SOCKET_PASSWORD must not contain line breaks' >&2
        exit 1
        ;;
esac

if ! grep -q 'name="password"' "$socket_config"; then
    echo '[freeswitch] Event socket password setting is missing' >&2
    exit 1
fi

# Escape XML first, then escape the sed replacement metacharacters.
escaped_password=$(printf '%s' "$EVENT_SOCKET_PASSWORD" |
    sed -e 's/\&/\&amp;/g' -e 's/</\&lt;/g' -e 's/>/\&gt;/g' -e 's/"/\&quot;/g' -e "s/'/\&apos;/g" |
    sed -e 's/[\\&|]/\\&/g')
sed -i "s|\(name=\"password\"[[:space:]]*value=\"\)[^\"]*|\1${escaped_password}|" "$socket_config"
