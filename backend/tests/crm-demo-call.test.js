const {test}=require('node:test');const assert=require('node:assert/strict');
const {run}=require('../src/services/crmDemoCall');
const scenario=require('../../laravel/public/demo/clarity-scenario.json');
const load=async()=>({status:'demo',scenario});
test('full configured demo completes all steps in order with simulated results',async()=>{
 const s={};
 for(const step of scenario.steps){
  const current=await run(s,{action:'current'},load);assert.equal(current.step.id,step.id);assert.equal(current.simulated,true);
  const done=await run(s,{action:'complete',step_id:step.id,confirmed:true},load);assert.equal(done.simulated,true);assert.equal(done.result,step.result);
 }
 assert.equal((await run(s,{action:'current'},load)).status,'demo_complete');
 assert.equal(s.crmDemo.completed.length,scenario.steps.length);
});
test('skipping, unconfirmed completion, interruptions and duplicate completions do not advance wrongly',async()=>{
 const s={};await run(s,{action:'current'},load);
 assert.equal((await run(s,{action:'complete',step_id:scenario.steps[1].id,confirmed:true},load)).status,'invalid');
 assert.equal((await run(s,{action:'complete',step_id:scenario.steps[0].id,confirmed:false},load)).status,'invalid');
 assert.equal((await run(s,{action:'current'},load)).step.id,scenario.steps[0].id);
 await run(s,{action:'complete',step_id:scenario.steps[0].id,confirmed:true},load);
 assert.equal((await run(s,{action:'complete',step_id:scenario.steps[0].id,confirmed:true},load)).status,'already_completed');
 assert.equal(s.crmDemo.index,1);
});
test('disabled calls get no dummy values and separate calls have independent progress',async()=>{
 const disabled={};assert.equal((await run(disabled,{action:'current'},async()=>({status:'disabled'}))).status,'disabled');assert.equal(disabled.demoEver,undefined);
 const a={},b={};await run(a,{action:'complete',step_id:scenario.steps[0].id,confirmed:true},load);
 assert.equal((await run(b,{action:'current'},load)).step.id,scenario.steps[0].id);
});
test('values are taken from configuration rather than a runtime constant',async()=>{
 const edited=JSON.parse(JSON.stringify(scenario));edited.values.monthly_payment='333';
 assert.equal((await run({},{action:'current'},async()=>({status:'demo',scenario:edited}))).values.monthly_payment,'333');
});

const intro=require('../../laravel/public/demo/clarity-intro-scenario.json');
const {waitForQuiet}=require('../src/services/crmDemoCall');
test('hold fetches a report; failure does not advance; success retains returned data',async()=>{
 const scenario={values:{},steps:intro.steps.filter(s=>s.effect==='credit_lookup')};
 const load=async()=>({status:'demo',scenario});const s={};let operation;
 assert.equal((await run(s,{action:'complete',step_id:'hold',confirmed:true},load,async()=>({status:'unavailable'}))).status,'unavailable');
 assert.equal(s.crmDemo.index,0);
 const report={status:'report_ready',accounts_count:2,debts_total:20000};
 await run(s,{action:'complete',step_id:'hold',confirmed:true},load,async p=>{operation=p.operation;return report;});
 assert.equal(operation,'demo_credit');assert.equal(s.crmDemo.report,report);assert.equal(s.crmDemo.index,1);
});
test('quiet window is three seconds and resets when caller continues',async()=>{
 let time=10000;const s={lastCallerSpeechAt:10000};
 assert.equal(await waitForQuiet(s,{now:()=>time,sleep:async ms=>{time+=ms;if(time===12000)s.lastCallerSpeechAt=time;}}),true);
 assert.equal(time,15000);
 assert.equal(await waitForQuiet({demoClosed:true},{now:()=>0,sleep:()=>assert.fail()}),false);
});
test('pain quote is saved unchanged after quiet and only once',async()=>{
 const load=async()=>({status:'demo',scenario:{values:{},steps:intro.steps.filter(s=>s.effect==='pain_quote')}});
 const s={lastCallerSpeechAt:1};const events=[];s.beginDemoQuiet=async()=>events.push('stop AI playback');
 const args={action:'complete',step_id:'pain',confirmed:true,quote:'I lost my job.  Then my car broke down.'};
 let saved;
 const effect=async p=>{events.push('save');saved=p;return {status:'saved'};};
 const quiet=async s=>{assert.equal(s.demoQuiet,true);events.push('quiet');return true;};
 assert.equal((await run(s,args,load,effect,quiet)).status,'demo_complete');
 assert.equal(saved.quote,args.quote);assert.deepEqual(events,['stop AI playback','quiet','save']);assert.equal(s.demoQuiet,false);
 assert.equal((await run(s,args,load,effect,quiet)).status,'already_completed');assert.equal(events.length,3);
});
test('additional speech or failed persistence cannot complete pain step',async()=>{
 const load=async()=>({status:'demo',scenario:{values:{},steps:intro.steps.filter(s=>s.effect==='pain_quote')}});
 const s={lastCallerSpeechAt:1};const args={action:'complete',step_id:'pain',confirmed:true,quote:'First sentence.'};
 assert.equal((await run(s,args,load,()=>assert.fail('stale quote saved'),async()=>{s.lastCallerSpeechAt=2;return true;})).status,'answer_extended');
 assert.equal(s.crmDemo.index,0);
 assert.equal((await run(s,args,load,async()=>({status:'unavailable'}),async()=>true)).status,'unavailable');
 assert.equal(s.crmDemo.index,0);
});
