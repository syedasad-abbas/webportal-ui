-- Direct Gemini AI call bridge.
-- Answers the SIP channel, asks the backend to attach Gemini Live, and keeps
-- the same channel parked until the caller hangs up.

local BACKEND_BASE_URL = os.getenv("BACKEND_URL") or "http://127.0.0.1:4000"
BACKEND_BASE_URL = string.gsub(BACKEND_BASE_URL, "/+$", "")
local AI_AGENT_URL = BACKEND_BASE_URL .. "/freeswitch/ai-agent"

local function url_encode(value)
  return string.gsub(tostring(value or ""), "([^%w%-_%.~])", function(char)
    return string.format("%%%02X", string.byte(char))
  end)
end

local arguments = argv or {}
local uuid = arguments[1] or session:getVariable("uuid") or ""
local token = arguments[2] or os.getenv("BACKEND_INTERNAL_TOKEN") or "sync-secret"

freeswitch.consoleLog("info", "[ai-agent] answering channel " .. uuid .. "\n")
session:answer()

local body = string.format(
  "uuid=%s&token=%s",
  url_encode(uuid), url_encode(token)
)
local api = freeswitch.API()
local curl_cmd = string.format(
  "curl %s content-type application/x-www-form-urlencoded post %s",
  AI_AGENT_URL, body
)
local response = api:executeString(curl_cmd)
freeswitch.consoleLog("info", "[ai-agent] backend response: " .. tostring(response) .. "\n")

local accepted = response and string.match(response, '"ok"%s*:%s*true')
local skipped = response and string.match(response, '"skipped"%s*:%s*true')
if not accepted or skipped then
  freeswitch.consoleLog("warning", "[ai-agent] backend did not start AI audio for " .. uuid .. "\n")
  session:hangup("NORMAL_TEMPORARY_FAILURE")
  return
end

freeswitch.consoleLog("info", "[ai-agent] parking live media channel " .. uuid .. "\n")
session:execute("park")
freeswitch.consoleLog("info", "[ai-agent] media channel ended " .. uuid .. "\n")
