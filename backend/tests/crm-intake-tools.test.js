const {test}=require('node:test'); const assert=require('node:assert/strict');
const fs=require('node:fs');const vm=require('node:vm');const path=require('node:path');
const context={module:{exports:{}},require:name=>name==='./crmDemoCall'?require('../src/services/crmDemoCall'):({}),Object};
vm.runInNewContext(fs.readFileSync(path.join(__dirname,'../src/services/crmIntakeTools.js'),'utf8'),context);
const {run}=context.module.exports;
const args={confirmed:true,customer_name:'Test',values:[{key:'pain_statement',value:'My exact words'}]};
const session=()=>({callId:'123',phoneConfirmed:true,phoneDigits:'15559876543',crmIntakeFields:{pain_statement:'Exact words'}});
test('intake sends exact caller words and only reports confirmed saves',async()=>{
 const s=session();let payload;
 assert.equal((await run(s,{name:'save_crm_intake',args},async p=>{payload=p;return {saved:true};})).status,'saved');
 assert.equal(payload.intake.pain_statement,'My exact words');
});
test('unconfirmed phone and disabled fields cannot be saved',async()=>{
 const s=session();s.phoneConfirmed=false;
 const noWrite=()=>assert.fail('unexpected write');
 assert.equal((await run(s,{name:'save_crm_intake',args},noWrite)).status,'invalid');
 assert.equal((await run(session(),{name:'save_crm_intake',args:{...args,values:[{key:'account_number',value:'secret'}]}},noWrite)).status,'invalid');
});
test('CRM failure is reported without fabricated success',async()=>{
 assert.equal((await run(session(),{name:'save_crm_intake',args},async()=>{throw Error('403');})).status,'unavailable');
});
test('review uses current call ID, never model-provided identity',async()=>{
 let payload;const s=session();
 const result=await run(s,{name:'review_crm_customer',args:{stage:'debt_review',customer_id:999,phone:'99999999999'}},async p=>{payload=p;return {status:'verification_required'};});
 assert.equal(payload.call_id,s.callId);assert.equal(payload.operation,'review');assert.equal(payload.customer_id,undefined);assert.equal(payload.phone,undefined);assert.equal(result.status,'verification_required');
});
test('invalid review stage does not fetch records',async()=>{
 assert.equal((await run(session(),{name:'review_crm_customer',args:{stage:'anything'}},()=>assert.fail('unexpected request'))).status,'invalid');
});
