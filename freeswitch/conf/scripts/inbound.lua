-- Called from the public dialplan when an external call arrives at a DID.
-- AI-enabled calls answer immediately. Other calls remain unanswered while
-- browser agents are rung round-robin and are bridged only after answering.

local BACKEND_BASE_URL = os.getenv("BACKEND_URL") or "http://127.0.0.1:4000"
BACKEND_BASE_URL = string.gsub(BACKEND_BASE_URL, "/+$", "")
local INBOUND_URL = BACKEND_BASE_URL .. "/freeswitch/inbound"
local INTERNAL_TOKEN = os.getenv("BACKEND_INTERNAL_TOKEN") or "sync-secret"

local function url_encode(value)
  return string.gsub(tostring(value or ""), "([^%w%-_%.~])", function(char)
    return string.format("%%%02X", string.byte(char))
  end)
end

local uuid = session:getVariable("uuid") or ""
local did = session:getVariable("destination_number") or ""
local caller = session:getVariable("caller_id_number") or ""

freeswitch.consoleLog("info", string.format("[inbound.lua] uuid=%s did=%s caller=%s\n", uuid, did, caller))

local body = string.format(
  "uuid=%s&did=%s&callerIdNumber=%s&token=%s",
  url_encode(uuid), url_encode(did), url_encode(caller), url_encode(INTERNAL_TOKEN)
)

local conference = "in-" .. uuid
local ai_mode = false
local api = freeswitch.API()
local curl_cmd = string.format(
  "curl %s content-type application/x-www-form-urlencoded post %s",
  INBOUND_URL, body
)
local response = api:executeString(curl_cmd)
freeswitch.consoleLog("info", "[inbound.lua] backend response: " .. tostring(response) .. "\n")

local accepted = response and string.match(response, '"ok"%s*:%s*true')
if not accepted then
  freeswitch.consoleLog("warning", "[inbound.lua] rejecting unconfigured or inactive DID " .. did .. "\n")
  session:hangup("UNALLOCATED_NUMBER")
  return
end

if response then
  local conf = string.match(response, '"conference"%s*:%s*"([^"]+)"')
  if conf and #conf > 0 then
    conference = conf
  end
  ai_mode = string.match(response, '"ai"%s*:%s*true') ~= nil
end

if ai_mode then
  session:answer()
  freeswitch.consoleLog("info", "[inbound.lua] parking caller for AI audio " .. uuid .. "\n")
  session:execute("park")
else
  session:ringReady()
  freeswitch.consoleLog("info", "[inbound.lua] waiting for browser agent " .. uuid .. "\n")
  session:execute("park")
end

local stop_cmd = string.format(
  "curl %s/%s/hangup content-type application/x-www-form-urlencoded post token=%s",
  INBOUND_URL, url_encode(uuid), url_encode(INTERNAL_TOKEN)
)
api:executeString(stop_cmd)
