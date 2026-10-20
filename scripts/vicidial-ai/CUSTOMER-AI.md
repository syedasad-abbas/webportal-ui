# Dedicated VICIdial customer-to-AI endpoint

Status: application-side implementation; NOT deployed or verified against the
installed remote-agent routing. Keep the feature disabled until that routing
and its failure destination have been verified on the VICIdial VM.

Existing FreeSWITCH manual/inbound AI routes, Gemini prompts and credentials are
unchanged. This adds destination `vici-ai-customer` on the existing public SIP
profile. It uses a new Lua script and internal backend route. It is not the
`vici-ai-<reservation>` human-assist conference route.

## Required call contract

- The caller is an inbound customer assigned to a dedicated AI remote agent.
- VICIdial creates ONE new SIP leg to this endpoint per customer and hangs that
  leg up when that customer leaves. Never send an idle/persistent agent channel.
- Asterisk supplies `X-Vici-AI-Agent` (dedicated numeric agent identity) and
  `X-Vici-Customer-ID` (that customer's Asterisk uniqueid) SIP headers.
- The backend checks actual FreeSWITCH channel variables over ESL, not a
  browser-supplied source IP, before answering. It requires the internal token,
  configured VM source IP, exact destination, allowlisted agent and enabled
  inbound AI settings. Headers are routing metadata; the trusted VM's private
  network source is the SIP admission boundary. Do not expose this lab route
  publicly or accept untrusted traffic impersonating that VM.
- The trusted Asterisk route must be used exclusively for inbound AI calls.
  The endpoint cannot independently verify VICIdial customer state from SIP
  headers. Persistent connections are NOT supported by this implementation.

## Configuration (manual, after VM verification)

```
VICIDIAL_CUSTOMER_AI_ENABLED=true
VICIDIAL_CUSTOMER_AI_AGENTS=9901
VICIDIAL_AI_SERVER_IP=172.16.253.142
VICIDIAL_CUSTOMER_AI_SOURCE_IP=
```

9901 is an example; select an unused dedicated VICIdial identity first. IDs in
this list are excluded from automatic human-assist conferencing, even while the
customer endpoint is disabled, so they cannot receive a duplicate AI leg.
Existing global AI must be saved, enabled, ready, and allow Inbound or Both.
Both does NOT add outbound VICIdial support.

If the private VM network source-NATs SIP, set
`VICIDIAL_CUSTOMER_AI_SOURCE_IP` to the exact address observed in the Lua
authorization diagnostic. In this lab the observed SIP source was
`192.168.18.31`, despite the VM address being `172.16.253.142`. Leave
`VICIDIAL_AI_SERVER_IP` unchanged for the conference worker. An empty override
retains the original source check. This trusts that private NAT source, not a
unique VM identity: do not expose the endpoint to untrusted hosts sharing it.

The existing private `webportal-ai` SIP peer can route to FreeSWITCH at
192.168.18.31:5080. Confirm the actual listener before deployment. The final
Asterisk dialplan must set the headers, Dial SIP/webportal-ai/vici-ai-customer,
and return failed/ended AI calls to an explicitly chosen human/queue fallback.
Do not install a guessed remote-agent dialplan or use an existing human's
extension. The installed VICIdial schema and remote-agent call path must first
be inspected; the configuration is not automatically created by this change.

## Lifecycle and failure

A fresh FreeSWITCH UUID creates a fresh Gemini session with the normal configured
customer greeting, not the human-conference greeting. No shared conversation
history is carried between UUIDs. The Lua script sends lease heartbeats and
ends the dedicated AI session when its SIP leg ends. A missing heartbeat or
failed Gemini setup closes the AI session. Lua ends only its own AI channel;
backend code neither originates, redirects nor kills Asterisk participants.

If AI fails after answer, Asterisk must provide the queue/human fallback; an
answered call does not automatically return to its previous queue. Automated
human transfer, disposition and remote-agent availability are VM-side work,
not implemented or claimed by this endpoint. Verify them before real traffic.

## Acceptance checks (manual, not run by implementation)

1. Verify dedicated remote-agent identity, inbound group and per-customer routing.
2. Verify source IP, SIP/RTP reachability, headers and fallback destination.
3. Deploy app code manually; reload FreeSWITCH XML for the added route.
4. With browsers closed, place two successive internal inbound calls. Each
   should get a fresh greeting and no prior customer's details.
5. End a customer call and verify its AI session is removed and agent available.
6. Fail AI and verify fallback; do not leave the caller in a silent connection.
7. Confirm existing ordinary FreeSWITCH calls and human-assist calls still work.
