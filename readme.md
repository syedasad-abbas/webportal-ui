Local development URL: http://localhost:8080

## Telephony network setup

By default no IP values need to be pinned:

- FreeSWITCH derives its LAN bind and directory domain from the default-route interface.
- FreeSWITCH advertises the detected host address in SIP Via/From/Contact and
  in RTP/SDP.
- The backend discovers the Docker gateway used to reach host-networked FreeSWITCH.
- The dialer derives its SIP domain and WebSocket address from the portal URL.

`SIP_DOMAIN`, `FREESWITCH_HOST`, `WEBRTC_WS`, and `WEBRTC_SIP_DOMAIN` remain
optional overrides. `FREESWITCH_EXTERNAL_SIP_IP` and
`FREESWITCH_EXTERNAL_RTP_IP` can explicitly override SIP and RTP advertisement
without editing the configuration. An HTTPS deployment must use WSS with a
certificate valid for its public hostname.

The host firewall/router must allow the intended sources to reach SIP
`5060/udp,tcp` (WebRTC/internal), SIP `5080/udp,tcp` (carrier/external), WebSocket
`5066/tcp` or WSS `7443/tcp`, and RTP `40000-41000/udp`. FreeSWITCH dynamically
selects media ports from that RTP range for each call; port `5080` carries SIP
signaling only. Port `8021/tcp` is
the FreeSWITCH control socket: allow it only from the Docker application subnet,
never from the public internet.

Inbound calls must be delivered by the carrier to the external profile on port
5080 and to a DID matched by `freeswitch/conf/dialplan/public/00_inbound_did.xml`.
Outbound calling requires a real carrier domain/proxy; `localhost` is only a
placeholder and routes calls back to this PBX.

## Local setup

1. Copy `backend/.env` and `laravel/.env` if needed, adjust secrets.
2. Ensure Docker Desktop exposes `host.docker.internal` (default on macOS/Windows; on Linux Compose adds the host-gateway entry).
3. Run `docker compose up --build` from the repo root.
4. Access the UI on `http://localhost:8080` and the API on `http://localhost:4000`.



SELECT id, user_id, duration_seconds, connected_at, ended_at
FROM call_logs
ORDER BY created_at DESC
LIMIT 10;

\watch 2
