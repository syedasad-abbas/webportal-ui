const declaration = {name:'demo_call_step',description:'Follow the explicitly staff-enabled simulation in order. Use current before speaking. Complete only after the tester answers. credit_lookup fetches the fictional report during hold. For budget_value pass the actual amount (zero is valid); budget_review requires the caller monthly_income. For exclusions pass requested creditor names, or an empty array for none. For pain_quote and dream_quote, pass the caller exact words as quote, including their second sentence, never a summary; wait silently for the result. No SSN or banking data.',parameters:{type:'OBJECT',properties:{action:{type:'STRING',enum:['current','complete']},step_id:{type:'STRING'},confirmed:{type:'BOOLEAN'},quote:{type:'STRING'},amount:{type:'NUMBER'},monthly_income:{type:'NUMBER'},accounts:{type:'ARRAY',items:{type:'STRING'}}},required:['action']}};

// Require three seconds of quiet AFTER the tool request as well as after the
// last detected caller speech. Inbound audio continues to update this timestamp.
async function waitForQuiet(session, {now=Date.now,sleep=ms=>new Promise(r=>setTimeout(r,ms))}={}) {
  const started=now();
  while (now()-Math.max(started,session.lastCallerSpeechAt||0)<3000) {
    if(session.demoClosed) return false;
    if(now()-started>=30000) return false;
    await sleep(100);
  }
  return !session.demoClosed;
}
async function run(session,args,load,effect=async()=>({status:'unavailable'}),quiet=waitForQuiet) {
  if(session.demoStepBusy) return {status:'busy'};
  session.demoStepBusy=true;
  try {
    const data=await load();
    if(data.status!=='demo') {session.crmDemo=null;return data;}
    session.demoEver=true;
    const scenario=data.scenario;
    if(!scenario || !Array.isArray(scenario.steps)||!scenario.steps.length) return {status:'unavailable'};
    const version=JSON.stringify(scenario);
    if(!session.crmDemo || session.crmDemo.version!==version) session.crmDemo={version,index:0,completed:[]};
    const state=session.crmDemo, step=scenario.steps[state.index];
    if(args.action==='complete'){
      if(state.completed.includes(args.step_id)) return {status:'already_completed',simulated:true,next_step:step||null};
      if(args.confirmed!==true || step?.id!==args.step_id) return {status:'invalid',message:'Finish the current step and obtain the tester response first.'};
      let result=step.result;
      if(step.effect==='credit_lookup') {
        const report=await effect({operation:'demo_credit'});
        if(report.status!=='report_ready') return report;
        state.report=report;
        result=report;
      }
      if(step.effect==='budget_value') {
        const allowed=['food','utilities','phone','car_payment','car_insurance','housing','other'];
        if(!allowed.includes(step.field) || !Number.isFinite(args.amount) || args.amount<0 || args.amount>10000000) return {status:'invalid',message:'Use the actual caller-provided amount, including zero; do not invent missing expenses.'};
        state.budget={...state.budget,[step.field]:args.amount};
        result={status:'captured',field:step.field,amount:args.amount};
      }
      if(step.effect==='budget_review') {
        if(!Number.isFinite(args.monthly_income)||args.monthly_income<=0) return {status:'invalid',message:'Use previously confirmed monthly income. If missing, clarify once; never assume a default.'};
        const review=await effect({operation:'demo_budget',budget:{...state.budget,monthly_income:args.monthly_income}});
        if(review.status!=='budget_ready') return review;
        state.budgetReview=review;result=review;
      }
      if(step.effect==='comparison_preview') {
        const preview=await effect({operation:'demo_comparison'});
        if(preview.status!=='preview_ready') return preview;
        state.comparison=preview;result=preview;
      }
      if(step.effect==='exclusions') {
        if(!Array.isArray(args.accounts)||args.accounts.length>30||args.accounts.some(a=>typeof a!=='string'||!a.trim())) return {status:'invalid'};
        const saved=await effect({operation:'demo_exclusions',accounts:args.accounts});
        if(saved.status!=='saved')return saved;
        result=saved;
      }
      if(['pain_quote','dream_quote'].includes(step.effect)) {
        if(typeof args.quote!=='string'||!args.quote.trim()||args.quote.length>2000) return {status:'invalid',message:'Provide the actual caller words, not a summary or a dummy answer.'};
        session.demoQuiet=true;
        try {
          // Stop only AI playback, never hang up/redirect any participant.
          if(session.beginDemoQuiet) await session.beginDemoQuiet();
          const speechAt=session.lastCallerSpeechAt||0;
          if(!await quiet(session)) return {status:'waiting',message:'Keep listening; retry with the full answer once the caller finishes.'};
          if((session.lastCallerSpeechAt||0)>speechAt) return {status:'answer_extended',message:'Caller continued speaking. Include their additional exact words and retry. Do not speak yet.'};
          const saved=await effect({operation:step.effect==='pain_quote'?'demo_pain':'demo_dream',quote:args.quote});
          if(saved.status!=='saved') return saved;
          state[step.effect]=args.quote;
          result=saved;
        } finally {session.demoQuiet=false;}
      }
      if(session.demoClosed) return {status:'ended'};
      state.completed.push(args.step_id);state.index++;
      return {status:state.index===scenario.steps.length?'demo_complete':'step_complete',simulated:true,result,next_step:scenario.steps[state.index]||null};
    }
    if(args.action!=='current')return {status:'invalid'};
    return {status:step?'current_step':'demo_complete',simulated:true,step:step||null,values:scenario.values,report:state.report,budget:state.budget,budget_review:state.budgetReview,comparison:state.comparison,pain_quote:state.pain_quote,dream_quote:state.dream_quote,completed_steps:state.completed,
      instruction:'Disclose this is a fictional demo. Ask one question at a time. Remember supplied answers and never ask a confirmed detail again. Resume interrupted steps. Report and enrollment values must come from credit_lookup, not guesses. Stop after the last configured step.'};
  } finally {session.demoStepBusy=false;}
}
module.exports={declaration,run,waitForQuiet};
