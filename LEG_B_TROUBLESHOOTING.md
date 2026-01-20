# Leg B Join: Files + Troubleshooting

## Files involved in Leg B joining the conference
- `backend/src/services/callService.js`
  - Builds the outbound call (B-leg) and uses `&conference(call-<uuid>@default)`.
  - Key: `application: `&conference(${conferenceName}@default)`.
- `backend/src/lib/freeswitch.js`
  - Sends the originate command: `bgapi originate {vars} <dialstring> <application>`.
- `backend/src/routes/calls.js`
  - API endpoint that triggers `callService.originate()`.
- `backend/src/services/callControlService.js`
  - Reads channel vars and status (uuid_exists, sip_term_status, hangup_cause, billsec).
- `laravel/resources/js/dialer/webrtc-client.js`
  - Browser joins the same conference by calling `sip:call-<uuid>@<domain>`.
- `freeswitch/conf/dialplan/default.xml`
  - `web_dialer_conference` extension answers and runs `conference $1@default`.
- `freeswitch/conf/sip_profiles/internal.xml` / `freeswitch/conf/sip_profiles/external.xml` (if present)
  - SIP profile behavior for inbound WebRTC or outbound gateway leg.
- `freeswitch/conf/autoload_configs/conference.conf.xml` (if present)
  - Conference profile settings (including `default`).
- `freeswitch/conf` and `/etc/freeswitch/gateways/*.xml`
  - Gateway config for the carrier (e.g., outbound proxy, register, from-domain).

## Troubleshooting Leg B (outbound) join

### 1) Verify the originate command and variables
- Confirm the backend is using the gateway or direct SIP as expected:
  - Check backend logs for `[call] originate` and verify `sipHost`, `transport`, and destination.
- Confirm the originate app is `&conference(call-<uuid>@default)`.
- Check B-leg channel variables:
  - `uuid_dump <uuid>` and look for:
    - `variable_sip_gateway_name`
    - `variable_sip_route_uri` (outbound proxy)
    - `variable_sip_destination_url`
    - `variable_sip_req_user` / `variable_sip_req_host`
    - `variable_origination_uuid`

### 2) Confirm the conference exists and has members
- A conference is created only when the outbound leg answers (with current config).
- Check conference membership:
  - `conference call-<uuid> list`
- If the conference does not exist:
  - The B-leg did not answer yet, or failed before answer.

### 3) Check channel state and SIP progress
- `show channels like <uuid>`
- `uuid_dump <uuid>`
- Inspect:
  - `Channel-State`, `Answer-State`
  - `variable_sip_last_response`, `variable_sip_last_response_text`
  - `variable_hangup_cause`

### 4) Validate gateway configuration
- Confirm the gateway file exists and matches the carrier:
  - `/etc/freeswitch/gateways/<carrier_id>.xml`
- Validate outbound proxy, from-domain, and register settings.
- Check gateway status:
  - `sofia status gateway <gateway_name>`

### 5) Validate dial string and destination formatting
- Ensure destination is normalized correctly (US/CA only):
  - `backend/src/services/callService.js` enforces +1 and strips non-digits.
- Confirm any carrier prefix logic in `carrier_prefixes` is correct.
- Verify `sip_req_user` and `sip_to_user` match expected E.164 / carrier format.

### 6) Confirm FreeSWITCH can reach the carrier
- Check network connectivity from the FreeSWITCH container to the outbound proxy.
- Confirm the carrier expects UDP/TCP/port and your transport matches.
- Look for SIP errors in FreeSWITCH logs (`/var/log/freeswitch/freeswitch.log`).

### 7) Verify WebRTC leg
- Ensure the browser joins `sip:call-<uuid>@<domain>`.
- Validate the dialplan `web_dialer_conference` matches the conference name format.
- Check if the browser leg is connected (conference list should show it).

### 8) RTP/media checks (after answer)
- Once answered, check RTP stats:
  - `uuid_dump <uuid>` for `variable_rtp_*` and media IP/port.
- Confirm external media IP if behind NAT.

### 9) Common failure patterns
- Conference missing: outbound leg never answered.
- Wrong gateway: carrier_id mismatch or gateway file not loaded.
- Bad caller ID: carrier rejects PAI/RPID or invalid CID.
- Invalid destination: digits stripped or prefixed unexpectedly.
- Codec mismatch: carrier rejects offered codecs.

### 10) Minimal test sequence
- Place a call that auto-answers.
- Confirm conference exists and list members.
- Confirm B-leg RTP vars appear after answer.

