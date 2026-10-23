const {test} = require('node:test');
const assert = require('node:assert/strict');
const {remember} = require('../src/services/confirmedPhone');
test('confirmation retains exact digits and cancels repeat timer', () => {
  const session = {phoneDigits:'03001234567',phoneCollecting:true,phoneAwaitingConfirmation:true,phoneFinalizeTimer:123};
  const cancelled = [];
  assert.equal(remember(session,{phone:'03001234567'},id=>cancelled.push(id)).status,'confirmed');
  assert.deepEqual(cancelled,[123]);
  assert.equal(session.phoneCollecting,false);
  assert.equal(session.phoneAwaitingConfirmation,false);
  assert.equal(session.phoneConfirmed,true);
  assert.equal(session.collectedInfo.phone,'03001234567');
  assert.equal(remember(session,{phone:'03001234567'}).status,'confirmed');
});
test('confirmed number cannot be replaced by an unrequested number', () => {
  const session = {};
  remember(session,{phone:'15559876543'});
  assert.equal(remember(session,{phone:'19995551234'}).status,'already_confirmed');
  assert.equal(session.phoneDigits,'15559876543');
  remember(session,{phone:'19995551234',correction:true});
  assert.equal(session.phoneDigits,'19995551234');
});
test('invalid or missing digits never mark a phone confirmed', () => {
  for (const phone of ['', '123', '1'.repeat(16), 'not a number', 12345678]) {
    const session = {};
    assert.equal(remember(session,{phone}).status,'invalid');
    assert.equal(session.phoneConfirmed,undefined);
  }
});
