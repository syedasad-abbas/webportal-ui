# Concurrent Call Flow (Multi-User)

This diagram shows how multiple users on different machines can log in and place calls concurrently, and how the backend handles parallel call sessions.

```
User A Browser                                 User B Browser
-----------------                              -----------------
Login (Laravel)                                Login (Laravel)
   |                                               |
Open /admin/dialer                                 Open /admin/dialer
   |                                               |
GET dialer page                                    GET dialer page
   |                                               |
Laravel builds per-user WebRTC config              Laravel builds per-user WebRTC config
  - wsUrl, domain                                  - wsUrl, domain
  - sip_username/password                          - sip_username/password
   |                                               |
SIP.js register (WSS)                              SIP.js register (WSS)
   |                                               |
FreeSWITCH sofia/internal                           FreeSWITCH sofia/internal
  - registers 1000                                 - registers 1001
   |                                               |
Dial destination                                    Dial destination
   |                                               |
POST /admin/dialer/dial                             POST /admin/dialer/dial
   |                                               |
Laravel -> Backend /calls                           Laravel -> Backend /calls
   |                                               |
Backend originate (unique UUID)                    Backend originate (unique UUID)
   |                                               |
FreeSWITCH PSTN leg (carrier)                      FreeSWITCH PSTN leg (carrier)
   |                                               |
Conference: call-<uuid>                            Conference: call-<uuid>
   |                                               |
SIP.js joins call-<uuid>                            SIP.js joins call-<uuid>
   |                                               |
Audio flows per user                               Audio flows per user


Backend handling of concurrency
-------------------------------
1) Each originate uses a unique UUID and conference name:
   - call-<uuid> isolates audio per call session.
2) Call state is tracked per UUID in call_logs:
   - Each user polls /calls/:uuid for status.
3) No shared SIP registration:
   - Each user has a distinct sip_username/password.
4) Parallel calls are independent:
   - FreeSWITCH handles multiple channels concurrently.
   - Backend keeps per-call state and controls (mute, dtmf, hangup).
```
