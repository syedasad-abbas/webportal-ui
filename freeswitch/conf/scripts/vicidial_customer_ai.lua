-- One SIP leg per customer. Do not use with a persistent idle agent channel.
local base = (os.getenv("BACKEND_URL") or "http://127.0.0.1:4000"):gsub("/+$", "")
local secret = os.getenv("BACKEND_INTERNAL_TOKEN") or ""
local uuid = session:get_uuid()
local function encode(s)
  return tostring(s):gsub("([^%w%-_%.~])", function(c) return string.format("%%%02X", c:byte()) end)
end
local api = freeswitch.API()
local body = "token=" .. encode(secret) .. "&uuid=" .. encode(uuid)
local function request(action)
  local result = api:execute("curl", base .. "/vicidial-ai/customer/" .. action
    .. " connect-timeout 2 timeout 4 content-type application/x-www-form-urlencoded post " .. body)
  return result and result:match('"ok"%s*:%s*true') ~= nil
end
if secret == "" or not request("authorize") then
  -- Log only bounded routing metadata, never tokens, SIP credentials or audio.
  local source = session:getVariable("sip_network_ip") or ""
  local agent = session:getVariable("sip_h_X-Vici-AI-Agent") or ""
  local customer = session:getVariable("sip_h_X-Vici-Customer-ID") or ""
  local destination = session:getVariable("destination_number") or ""
  if not source:match("^[%d%.:a-fA-F]+$") or #source > 45 then source = "missing-or-invalid" end
  if not agent:match("^%d+$") or #agent > 20 then agent = "missing-or-invalid" end
  freeswitch.consoleLog("warning", "[vicidial-customer-ai] authorization rejected"
    .. " uuid=" .. uuid .. " source=" .. source .. " agent=" .. agent
    .. " destination_matches=" .. tostring(destination == "vici-ai-customer")
    .. " customer_id_valid=" .. tostring(customer:match("^%d+%.%d+$") ~= nil)
    .. " internal_token_present=" .. tostring(secret ~= "") .. "\n")
  session:hangup("CALL_REJECTED")
  return
end
session:answer()
if not request("begin") then
  request("end")
  session:hangup("NORMAL_TEMPORARY_FAILURE")
  return
end
while session:ready() do
  session:sleep(2000)
  if session:ready() and not request("lease") then break end
end
request("end")
-- Only this dedicated AI leg; never a customer or agent UUID on Asterisk.
if session:ready() then session:hangup("NORMAL_TEMPORARY_FAILURE") end
