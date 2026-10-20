# VICIdial API controls with a persistent agent phone

The portal keeps its existing WebRTC phone connection. Customer controls are
sent server-side through Laravel to `/agc/api.php`, always scoped to the signed-in
portal user's stored VICIdial agent mapping. API passwords never enter browser
configuration. Commands are not retried automatically or sent through redirects.

The dialer's VICIdial panel supports requesting agent audio, ready/pause,
customer dialing, customer hangup, dispositions, hold/resume, keypad tones,
queue count, agent logout, and approved conference/transfer destinations.
An accepted command is not proof of the resulting call state; use the logged-in
agent console to confirm it. A customer hangup preserves agent audio, including
when missing API credentials prevent the command from being sent.

## Configure and activate

Set each user's **VICIdial API Username** and **VICIdial API Password** in
**Users → Create/Edit**. These take priority over the shared API account. The
target agent still comes from that user's **VICIdial Agent User** field.
API passwords are encrypted at rest, never prefilled or flashed back after
validation errors, and never included in browser phone configuration. Leaving
the password blank when editing preserves it; changing the API username requires
a new password. The phone registration password is never used for API login.

Deploy the new migration before saving API credentials:
`php artisan migrate --force` (run inside the app container after deploying the
updated image). No migration was run as part of this change.

Server configuration and the optional shared fallback remain in `.env`:

```dotenv
VICIDIAL_API_URL=https://172.16.253.142/agc/api.php
VICIDIAL_API_USER=your_api_user
VICIDIAL_API_PASSWORD=your_api_password
VICIDIAL_DISPOSITIONS=YOUR_ALLOWED_CODES
VICIDIAL_CONFERENCE_TARGETS='{}'
```

The API account must be active, have API access enabled and permission for the
functions being used. These credentials are separate from each phone's
registration password. Leave the current CA bundle configured for HTTPS.
Use the statuses allowed by the relevant campaign for `VICIDIAL_DISPOSITIONS`.

For optional conference targets, configure a name-to-number JSON map, for
example `{"supervisor":"8500"}` only if 8500 is an actual, intended destination
in your VICIdial dialplan. Numbers are dialed exactly, without campaign prefixes.
The browser can select only configured names. A target named `ai` requires a
working AI audio endpoint; naming a target does not provision or connect AI.

Deploy the changed app image manually when ready. No builds, service restarts,
or call-changing API requests were executed as part of this change.

After deployment:

1. Connect the mapped user's browser phone on the same dialer page.
2. Log into the VICIdial agent console with the assigned phone and campaign.
   Keep that console session running and avoid a second webphone registration.
3. Accept agent audio once, then use the portal's API controls for customer calls.
4. First use **Check queue**, a read-only API action. Resolve credential or
   session errors before trying call controls.
5. Test customer dialing, hold/resume, customer hangup and disposition with a
   test call. Confirm that agent audio survives customer hangup. Test conference
   and transfer only with a configured test destination.

## Audio and unattended operation

The [official Agent API](https://www.vicidial.org/docs/AGENT_API.txt) operates on
the VICIdial agent screen/session. `call_agent` calls the phone for an existing
session; it does not create or maintain a browser-free agent login.

This control layer therefore does not provide unattended AI answering when
the browser/agent session is absent. That needs server-side audio routing and
a supported session lifecycle (such as a deliberately configured remote-agent
or inbound AI route). The separate AMI worker in this directory adds AI to an
existing human conference; it is not an unattended agent implementation and is
not required for the API controls above.

Do not install that worker solely to enable API controls. The previously
prepared `VICIDIAL_AI_*` settings apply to the worker, not to these controls.
