const { test } = require('node:test');
const assert = require('node:assert/strict');
const { save } = require('../src/services/crmAppointment');
const args = {customer_name:'Test', appointment_date:'2026-11-01 10:00', doctor_name:'Doctor Test', confirmed:true};
const session = () => ({phoneConfirmed:true, phoneDigits:'15559876543', collectedInfo:{}});
test('confirmed appointment persists once and retains details', async () => {
  const s=session(); let writes=0;
  const persist=async (phone,name,notes)=>{writes++;assert.equal(phone,s.phoneDigits);assert.match(notes,/Appointment Date: 2026-11-01/);return {data:{id:1}};};
  assert.equal((await save(s,args,persist)).status,'saved');
  assert.equal(s.collectedInfo.appointmentDate,args.appointment_date);
  assert.equal((await save(s,args,persist)).status,'saved');
  assert.equal(writes,1);
});
test('unconfirmed or incomplete details never write', async () => {
  const persist=()=>{throw Error('must not write');};
  assert.equal((await save(session(),{...args,confirmed:false},persist)).status,'invalid');
  assert.equal((await save({},args,persist)).status,'invalid');
  assert.equal((await save(session(),{...args,appointment_date:''},persist)).status,'invalid');
});
test('CRM failures never mark appointment saved and permit retry', async () => {
  const s=session();
  assert.equal((await save(s,args,async()=>{throw Error('offline');})).status,'unavailable');
  assert.equal(s.savedAppointmentKey,undefined);
  assert.equal((await save(s,args,async()=>null)).status,'unavailable');
  assert.equal((await save(s,args,async()=>({data:{id:1}}))).status,'saved');
});
test('concurrent duplicate does not issue a second write', async () => {
  const s=session();let resolve;
  const pending=save(s,args,()=>new Promise(r=>{resolve=r;}));
  assert.equal((await save(s,args,()=>assert.fail('duplicate'))).status,'busy');
  resolve({data:{id:1}});await pending;
});
