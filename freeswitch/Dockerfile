FROM debian:bookworm-slim AS sngrep-builder

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl sngrep ncurses-term \
    && curl -fsSL \
      https://github.com/amigniter/mod_audio_stream/releases/download/v1.0.3/mod-audio-stream_1.0.3_amd64.deb \
      -o /tmp/mod-audio-stream.deb \
    && dpkg-deb -x /tmp/mod-audio-stream.deb /tmp/mod-audio-stream \
    && module_path="$(find /tmp/mod-audio-stream -type f -name mod_audio_stream.so -print -quit)" \
    && test -n "$module_path" \
    && cp "$module_path" /tmp/mod_audio_stream.so \
    && rm -f /tmp/mod-audio-stream.deb \
    && rm -rf /var/lib/apt/lists/*

FROM syedasadabbas/voip-freeswitch-with-sounds:1.0.0

COPY --from=sngrep-builder /usr/bin/sngrep /usr/local/bin/sngrep
COPY --from=sngrep-builder /tmp/mod_audio_stream.so /usr/lib/freeswitch/mod/mod_audio_stream.so
RUN mkdir -p /lib/x86_64-linux-gnu /lib64
COPY --from=sngrep-builder /lib/x86_64-linux-gnu/libmenuw.so.6 /lib/x86_64-linux-gnu/libmenuw.so.6
COPY --from=sngrep-builder /lib/x86_64-linux-gnu/libmenuw.so.6.4 /lib/x86_64-linux-gnu/libmenuw.so.6.4
COPY --from=sngrep-builder /lib/x86_64-linux-gnu/libformw.so.6 /lib/x86_64-linux-gnu/libformw.so.6
COPY --from=sngrep-builder /lib/x86_64-linux-gnu/libformw.so.6.4 /lib/x86_64-linux-gnu/libformw.so.6.4
COPY --from=sngrep-builder /lib/x86_64-linux-gnu/libpanelw.so.6 /lib/x86_64-linux-gnu/libpanelw.so.6
COPY --from=sngrep-builder /lib/x86_64-linux-gnu/libpanelw.so.6.4 /lib/x86_64-linux-gnu/libpanelw.so.6.4
COPY --from=sngrep-builder /lib/x86_64-linux-gnu/libncursesw.so.6 /lib/x86_64-linux-gnu/libncursesw.so.6
COPY --from=sngrep-builder /lib/x86_64-linux-gnu/libncursesw.so.6.4 /lib/x86_64-linux-gnu/libncursesw.so.6.4
COPY --from=sngrep-builder /lib/x86_64-linux-gnu/libpcap.so.0.8 /lib/x86_64-linux-gnu/libpcap.so.0.8
COPY --from=sngrep-builder /lib/x86_64-linux-gnu/libpcap.so.1.10.3 /lib/x86_64-linux-gnu/libpcap.so.1.10.3
COPY --from=sngrep-builder /lib/x86_64-linux-gnu/libdbus-1.so.3 /lib/x86_64-linux-gnu/libdbus-1.so.3
COPY --from=sngrep-builder /lib/x86_64-linux-gnu/libdbus-1.so.3.32.4 /lib/x86_64-linux-gnu/libdbus-1.so.3.32.4
COPY --from=sngrep-builder /usr/share/terminfo /usr/share/terminfo
RUN ln -sf /usr/share/terminfo/x/xterm+256color /usr/share/terminfo/x/xterm-256color

COPY conf /etc/freeswitch

# Add reload watcher script and entrypoint
COPY reload-watcher.sh /usr/local/bin/reload-watcher.sh
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
COPY configure-event-socket.sh /usr/local/bin/configure-event-socket.sh
COPY detect-host-ip.sh /usr/local/bin/detect-host-ip.sh
RUN chmod +x /usr/local/bin/configure-event-socket.sh /usr/local/bin/reload-watcher.sh /usr/local/bin/entrypoint.sh /usr/local/bin/detect-host-ip.sh

# Override the base image probe so ESL checks use the configured password.
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD fs_cli -H 127.0.0.1 -p "${EVENT_SOCKET_PASSWORD:-ClueCon}" -x status | grep -q '^UP'

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
