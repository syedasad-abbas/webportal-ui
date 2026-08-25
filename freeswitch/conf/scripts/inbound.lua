-- inbound.lua
-- Called from the public dialplan when an external call arrives at a DID.
-- Reports the call to the backend (which starts round-robin agent dispatch),
-- then parks the caller in a conference. The agent leg originated by the
-- backend lands in the same conference, bridging caller <-> agent.

local BACKEND_URL = os.getenv("BACKEND_URL") or "http://127.0.0.1:4000/freeswitch/inbound"
local INTERNAL_TOKEN = os.getenv("BACKEND_INTERNAL_TOKEN") or "sync-secret"

local uuid = session:getVariable("uuid") or ""
local did = session:getVariable("destination_number") or ""
local caller = session:getVariable("caller_id_number") or ""

freeswitch.consoleLog("info", string.format("[inbound.lua] uuid=%s did=%s caller=%s\n", uuid, did, caller))

-- Build the POST body (url-encoded).
local body = string.format(
  "uuid=%s&did=%s&callerIdNumber=%s&token=%s",
  uuid, did, caller, INTERNAL_TOKEN
)

local conference = "in-" .. uuid

-- Use mod_curl via the API to POST to the backend.
local api = freeswitch.API()
local curl_cmd = string.format(
  "curl %s content-type application/x-www-form-urlencoded post %s",
  BACKEND_URL, body
)
local response = api:executeString(curl_cmd)
freeswitch.consoleLog("info", "[inbound.lua] backend response: " .. tostring(response) .. "\n")

-- The backend is the source of truth for allowed DIDs. Do not answer an
-- unknown, deleted, or inactive number.
local accepted = response and string.match(response, '"ok"%s*:%s*true')
if not accepted then
  freeswitch.consoleLog("warning", "[inbound.lua] rejecting unconfigured or inactive DID " .. did .. "\n")
  session:hangup("UNALLOCATED_NUMBER")
  return
end

-- Try to extract the conference name from the JSON response (fallback to default).
if response then
  local conf = string.match(response, '"conference"%s*:%s*"([^"]+)"')
  if conf and #conf > 0 then
    conference = conf
  end
end

freeswitch.consoleLog("info", "[inbound.lua] parking caller in conference " .. conference .. "\n")

-- Answer and join the conference; caller hears hold music until an agent joins.
session:answer()
session:execute("conference", conference .. "@default")

-- When the caller leaves (hangs up), tell the backend to stop hunting for agents.
local stop_cmd = string.format(
  "curl %s/inbound/%s/hangup content-type application/x-www-form-urlencoded post token=%s",
  BACKEND_URL, uuid, INTERNAL_TOKEN
)
api:executeString(stop_cmd)
