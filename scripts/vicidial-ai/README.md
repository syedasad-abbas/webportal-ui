# VICIdial inbound AI conference

This connects the existing Gemini voice service as a third participant in an
active VICIdial MeetMe call. The human agent stays on the same browser dialer.
It requires the worker and private SIP route below; changing the portal alone
cannot add an audio participant on a different PBX.

The worker checks `vicidial_live_agents.status=INCALL`, matches the customer in
`vicidial_auto_calls` with `call_type=IN`, and verifies the customer channel in
AMI `MeetmeList`. The backend accepts only agents saved in `vicidial_credentials`
whose portal user selected VICIdial. Global AI must be saved, enabled, have a
Gemini key, and allow **Inbound** or **Both**. An idle agent audio connection or
a direct phone test does not trigger AI.

The third leg is originated using Asterisk's [AMI Originate action](https://docs.asterisk.org/Latest_API/API_Documentation/AMI_Actions/Originate/)
into the existing [MeetMe conference](https://github.com/asterisk/asterisk/blob/18/apps/app_meetme.c).
No VICIdial Agent API password is needed by this worker. Existing agent login
and campaign/in-group routing are still required to deliver the customer.

## 1. Portal host: configure and deploy manually

In `/home/tpl/webportal-ui/.env`, add:

```dotenv
VICIDIAL_AI_ENABLED=true
VICIDIAL_AI_SERVER_IP=172.16.253.142
VICIDIAL_AI_SYNC_TOKEN=REPLACE_WITH_RANDOM_SECRET
```

Generate the sync secret locally with `openssl rand -hex 32`; put the same value
in the worker configuration, never in chat. Existing `GEMINI_API_KEY`,
`BACKEND_INTERNAL_TOKEN`, and FreeSWITCH ESL settings remain required.

Save the user's VICIdial agent mapping in user settings (agent `6666`, phone
`gs102` for the current lab), and choose VICIdial on that user's dialer. The
worker uses stored user mappings, not the legacy `VICIDIAL_AGENTS` fallback.

The unified app image copies backend source, so these commands are for you to
run when ready; no build/restart was performed by the implementation:

```bash
cd /home/tpl/webportal-ui
docker compose -f docker-compose.unified.yml build app
docker compose -f docker-compose.unified.yml up -d --no-deps app
docker compose -f docker-compose.unified.yml exec -T freeswitch sh -c 'fs_cli -p "$EVENT_SOCKET_PASSWORD" -x reloadxml'
```

FreeSWITCH's dialplan and scripts are already mounted, so a FreeSWITCH image
rebuild is not needed. It still needs its existing `mod_lua`, `mod_curl`,
`mod_audio_stream` and a working Gemini configuration.

The worker reaches Node at the portal's VM-network address `172.16.253.1:4000`.
The current FreeSWITCH UDP listener was verified at **192.168.18.31:5080**,
so the SIP peer uses that address, not the VM-network address. The source
configuration also sets `external_sip_port=5080`; an older comment mentioning
5082 is inaccurate. Confirm with `ip -br -4 addr` and `ss -lun` on the portal
host if its addresses change. Adjust the example SIP target if needed, and
confirm the VM can reach it with `sip show peer webportal-ai` after reload.
RTP addresses advertised by both PBXs
must be reachable across that private network. Keep this SIP/HTTP lab route
private; it requires no public IP or internet-facing port forwarding.

## 2. Copy worker files to VICIdial

From the portal host:

```bash
scp -r scripts/vicidial-ai vici@172.16.253.142:~/
```

On the VICIdial VM:

```bash
sudo useradd --system --home /var/lib/vicidial-ai --shell /usr/sbin/nologin vicidial-ai
sudo install -d /opt/vicidial-ai
sudo install -m 0755 ~/vicidial-ai/worker.py /opt/vicidial-ai/worker.py
sudo install -o root -g vicidial-ai -m 0640 ~/vicidial-ai/worker.ini.example /etc/vicidial-ai-worker.ini
sudo install -o root -g vicidial-ai -m 0640 ~/vicidial-ai/db.cnf.example /etc/vicidial-ai-db.cnf
sudo install -d -o vicidial-ai -g vicidial-ai /var/lib/vicidial-ai
```

If the service account already exists, skip `useradd`. Edit the two installed
configuration files with `sudoedit`. Use the shared sync secret, the actual
portal private address, and two separate passwords for DB and AMI.

Create a read-only local database account from `sudo mariadb` (replace the
placeholder with the password saved in `vicidial-ai-db.cnf`):

```sql
CREATE USER 'webportal_ai'@'localhost' IDENTIFIED BY 'REPLACE_DATABASE_PASSWORD';
GRANT SELECT ON asterisk.vicidial_live_agents TO 'webportal_ai'@'localhost';
GRANT SELECT ON asterisk.vicidial_auto_calls TO 'webportal_ai'@'localhost';
```

## 3. VICIdial: add AMI access and private SIP peer

Copy the templates to persistent custom files:

```bash
sudo install -m 0640 ~/vicidial-ai/manager.conf.example /etc/asterisk/manager-webportal-ai.conf
sudo install -m 0640 ~/vicidial-ai/sip.conf.example /etc/asterisk/sip-webportal-ai.conf
```

Set the real AMI secret in `manager-webportal-ai.conf` to match the worker.
Set the real portal private IP and FreeSWITCH SIP port in `sip-webportal-ai.conf`.
Ensure Asterisk's service user can read these files if it does not run as root.

Using `sudoedit`, append these include lines **once**:

* `/etc/asterisk/manager.conf`: `#include manager-webportal-ai.conf`
* `/etc/asterisk/sip.conf`: `#include sip-webportal-ai.conf`

Add this context in a persistent custom dialplan file included by
`/etc/asterisk/extensions.conf`:

```ini
[webportal-ai-reject]
exten => _.,1,Hangup()
```

Do not modify generated `sip-vicidial.conf`. Apply the new custom files:

```bash
sudo asterisk -rx 'manager reload'
sudo asterisk -rx 'sip reload'
sudo asterisk -rx 'dialplan reload'
sudo asterisk -rx 'sip show peer webportal-ai'
```

## 4. Check worker, then enable its service

```bash
sudo -u vicidial-ai python3 /opt/vicidial-ai/worker.py --once
```

Expect `Worker check passed; 0 verified inbound customer call(s).` when idle.
`--once` is a real worker poll: it can join AI if an eligible call is live.
Then install and start the service manually:

```bash
sudo install -m 0644 ~/vicidial-ai/vicidial-ai.service /etc/systemd/system/vicidial-ai.service
sudo systemctl daemon-reload
sudo systemctl enable --now vicidial-ai
sudo journalctl -u vicidial-ai -n 20 --no-pager
```

## 5. Verify an actual inbound call

1. Enable AI for **Inbound** or **Both**, save its voice, greeting and goal.
2. Connect the mapped portal user's VICIdial browser phone, log agent `6666`
   into the appropriate campaign/in-group, and accept its agent audio leg.
3. Route an internal test customer through that VICIdial in-group to the agent.
   A direct call to `gs102` alone does not create an inbound VICIdial customer.
4. The worker should log `AI leg requested`. Check `sudo asterisk -rx 'meetme list'`:
   the conference should contain the agent, customer, and `SIP/webportal-ai-...`.
5. Portal container logs should show `[vicidial-ai] voice connection` with
   `ready: true`. Verify both the human and customer hear AI and can speak to it.
6. End the customer call: only the AI leg should disappear; agent audio remains.
   Also test disabling AI mid-call and stopping the worker; AI should leave
   within approximately 20 seconds while the agent/customer remain connected.

If the worker reports zero calls during a live inbound call, inspect customer
identity/channel fields and MeetMe membership. This version deliberately
supports the current **single-server MeetMe** setup; cross-server or ConfBridge
calls need a different membership adapter. Do not weaken verification to match
only a phone, lead, or idle conference.

Failures stop only the AI leg. The worker persists attempted call identities
before originating and never automatically retries the same customer call
(including after restart or hold). Use a **new test customer call** after fixing
a connection issue. This prevents duplicate assistants and repeated greetings.
The browser's Connected label still describes SIP registration, not proof that
Gemini joined; use actual audio and the ready log above for acceptance.

For rollback, disable `VICIDIAL_AI_ENABLED` and recreate the app, or stop
`vicidial-ai` on the VM. Existing customer/agent calling remains independent.
