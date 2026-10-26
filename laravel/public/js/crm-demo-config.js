(() => {
 const button=document.getElementById('crm-demo-fill'), input=document.getElementById('crm-demo-json'), status=document.getElementById('crm-demo-status');
 for (const button of [document.getElementById('crm-demo-fill'),document.getElementById('crm-demo-intro')]) button?.addEventListener('click',async()=>{
   button.disabled=true;
   try {
     const current=input.value.trim()?JSON.parse(input.value):{};
     if(!current || Array.isArray(current) || typeof current!=='object')throw Error('Scenario must be a JSON object.');
     const res=await fetch(button.dataset.template,{credentials:'same-origin'});
     if(!res.ok)throw Error('Cannot load demo examples.');
     const example=await res.json();
     current.values=current.values||{};
     for(const [key,value] of Object.entries(example.values)) if(current.values[key]===undefined || current.values[key]===null || current.values[key]==='')current.values[key]=value;
     if(button.id==='crm-demo-intro') {
       current.steps=example.steps;
       current.credit_record=current.credit_record||example.credit_record;
       current.term_table=current.term_table||example.term_table;
     } else if(!current.steps?.length)current.steps=example.steps;
     input.value=JSON.stringify(current,null,2);status.textContent=button.id==='crm-demo-intro'?'Intro-to-pain steps selected. Review the fictional record and term table, then Save.':'Missing examples added. Existing values and steps preserved. Review and Save to apply.';
   }catch(error){status.textContent=error.message;}finally{button.disabled=false;}
 });
})();
