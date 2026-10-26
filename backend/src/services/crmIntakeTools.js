const demo = require('./crmDemoCall');
const config = require('../config');
const declarations = [
  demo.declaration,
  {name:'review_crm_customer',description:'Fetch enabled CRM values for the current script stage, from the customer record verified and linked to this active call by staff. The model cannot choose a customer. Never substitute a phone lookup if verification is required. Missing values require clarification or human review; do not invent them or claim a new credit pull.',parameters:{type:'OBJECT',properties:{stage:{type:'STRING',enum:['after_verification','debt_review','plan_review']}},required:['stage']}},
  {name:'get_crm_intake_fields',description:'Get the administrator-enabled caller intake fields for this call. This retrieves configuration, not a credit report or a previous customer identity.',parameters:{type:'OBJECT',properties:{}}},
  {name:'save_crm_intake',description:'Save only caller-provided, confirmed intake values for enabled fields. Requires remember_confirmed_phone first. Never include SSN, banking credentials, invented expenses, credit decisions, or inferred emotions. This does not pull credit, enroll, send email, schedule payments or sign contracts.',parameters:{type:'OBJECT',properties:{
    customer_name:{type:'STRING'}, confirmed:{type:'BOOLEAN'}, values:{type:'ARRAY',items:{type:'OBJECT',properties:{key:{type:'STRING'},value:{type:'STRING'}},required:['key','value']}}
  },required:['customer_name','confirmed','values']}}
];
async function request(data) {
  const token = config.internalTokens?.backendSync;
  if (!token || token.length < 32) throw Error('Internal CRM connection unavailable');
  const res=await fetch('http://127.0.0.1:8080/api/internal/crm-workflow', {method:'POST',redirect:'error',signal:AbortSignal.timeout(8000),headers:{'Authorization':`Bearer ${token}`,'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(data)});
  if (!res.ok) throw Error('CRM request rejected');
  return (await res.json()).data;
}
async function run(session, call, send=request) {
  let ownsLock = false;
  try {
    if(call.name===demo.declaration.name) return await demo.run(session,call.args||{},()=>send({operation:'demo',call_id:session.callId}),payload=>send({...payload,call_id:session.callId}));
    if((session.demoEver || session.demoRequested) && call.name!=='get_crm_intake_fields') return {status:'simulation_only',message:'Do not save fictional demo answers to a real customer record.'};
    if (call.name==='get_crm_intake_fields') {
      const data=await send({operation:'config',call_id:session.callId});
      session.crmIntakeFields=data.fields;
      return {status:'success',fields:data.fields,retrieval:data.retrieval||[],message:'Use review_crm_customer at the configured stage. Values come from the staff-verified customer for this call, never shared demo data.'};
    }
    const args=call.args||{};
    if (call.name==='review_crm_customer') {
      if (!['after_verification','debt_review','plan_review'].includes(args.stage)) return {status:'invalid'};
      return await send({operation:'review',call_id:session.callId,stage:args.stage});
    }
    if (session.crmIntakeSaving) return {status:'busy'};
    if (!session.phoneConfirmed || !/^[0-9]{7,15}$/.test(session.phoneDigits||'') || args.confirmed!==true || typeof args.customer_name!=='string' || !args.customer_name.trim() || args.customer_name.length>200 || !Array.isArray(args.values) || !args.values.length || args.values.length>20) return {status:'invalid',message:'Use confirmed phone and caller-provided values only.'};
    const allowed=session.crmIntakeFields||{}; const intake={};
    for (const item of args.values) {
      if (!Object.hasOwn(allowed,item.key) || Object.hasOwn(intake,item.key) || typeof item.value!=='string' || item.value.length>2000) return {status:'invalid',message:'Fetch enabled fields first; only those fields can be saved.'};
      intake[item.key]=item.value;
    }
    session.crmIntakeSaving=true; ownsLock=true;
    const data=await send({operation:'intake',call_id:session.callId,phone:session.phoneDigits,name:args.customer_name.trim(),intake});
    return data.saved ? {status:'saved',message:'Intake recorded. No financial or provider action was executed.'} : {status:'unavailable'};
  } catch (_) { return {status:'unavailable',message:'CRM access or save failed. Do not invent results or claim success.'}; }
  finally { if (ownsLock) session.crmIntakeSaving=false; }
}
module.exports={declarations,run};
