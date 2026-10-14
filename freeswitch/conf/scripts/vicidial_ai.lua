-- A dedicated third leg in the existing VICIdial MeetMe conference.
-- Losing the worker/backend lease hangs up ONLY this leg.
local base = (os.getenv("BACKEND_URL") or "http://127.0.0.1:4000"):gsub("/+$", "")
local secret = os.getenv("BACKEND_INTERNAL_TOKEN") or ""
local reservation = (argv or {})[1] or ""
local uuid = session:get_uuid()
local source = session:getVariable("sip_network_ip") or session:getVariable("network_addr") or ""
local function encode(s)
  return tostring(s):gsub("([^%w%-_%.~])", function(c) return string.format("%%%02X", c:byte()) end)
end
if secret == "" or #reservation ~= 64 or reservation:find("[^a-f0-9]") then
  session:hangup("CALL_REJECTED")
  return
end
local body = "token=" .. encode(secret) .. "&reservation=" .. encode(reservation)
  .. "&uuid=" .. encode(uuid) .. "&source=" .. encode(source)
local api = freeswitch.API()
local function check(action)
  local result = api:execute("curl", base .. "/vicidial-ai/" .. action
    .. " connect-timeout 2 timeout 4 content-type application/x-www-form-urlencoded post " .. body)
  return result and result:match('"ok"%s*:%s*true') ~= nil
end
if not check("claim") then session:hangup("CALL_REJECTED"); return end
session:answer()
if not check("start") then session:hangup("NORMAL_TEMPORARY_FAILURE"); return end
while session:ready() do
  session:sleep(2000)
  if session:ready() and not check("lease") then break end
end
if session:ready() then session:hangup("NORMAL_CLEARING") end
