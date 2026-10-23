const name = 'remember_confirmed_phone';
const declaration = {
  name,
  description: 'Remember a phone number in this call only, immediately AFTER reading it back once and hearing explicit caller confirmation. Never call for a guess or unconfirmed number. Set correction only if the caller explicitly changed a previously confirmed number and confirmed the replacement. This does not verify ownership or save to CRM.',
  parameters: { type: 'OBJECT', properties: {
    phone: { type: 'STRING', description: 'Exact caller-confirmed digits, 7 to 15 digits; no formatting.' },
    correction: { type: 'BOOLEAN', description: 'True only for an explicitly confirmed replacement.' }
  }, required: ['phone'] }
};
function remember(session, args, cancel = clearTimeout) {
  if (!args || !/^[0-9]{7,15}$/.test(args.phone || '') || typeof args.phone !== 'string') {
    return { status: 'invalid', message: 'Do not invent digits. Clarify only the uncertain digits, without restarting confirmed details.' };
  }
  if (session.phoneConfirmed && args.phone !== session.phoneDigits && args.correction !== true) {
    return { status: 'already_confirmed', phone: session.phoneDigits, message: 'Retain this confirmed number. Ask again only if the caller requests a correction.' };
  }
  if (session.phoneFinalizeTimer) cancel(session.phoneFinalizeTimer);
  session.phoneFinalizeTimer = null;
  session.phoneDigits = args.phone;
  session.phoneConfirmed = true;
  session.phoneCollecting = false;
  session.phoneAwaitingConfirmation = false;
  session.phoneControlResponse = false;
  session.collectedInfo = session.collectedInfo || {};
  session.collectedInfo.phone = args.phone;
  return { status: 'confirmed', phone: args.phone, message: 'Number retained for this call. Continue to the next missing detail. Do not repeat it or ask for it again unless the caller requests a correction.' };
}
module.exports = { name, declaration, remember };
