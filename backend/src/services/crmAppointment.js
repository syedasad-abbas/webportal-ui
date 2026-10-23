const name = 'save_confirmed_appointment';
const declaration = {
  name,
  description: 'Save caller-confirmed appointment details to the test CRM. Call only after the caller agrees to the name, date/time and doctor. Requires a phone already retained with remember_confirmed_phone. This records details, not external calendar availability or a reserved slot. Never claim success until status is saved.',
  parameters: { type: 'OBJECT', properties: {
    customer_name: { type: 'STRING' },
    appointment_date: { type: 'STRING', description: 'Caller-agreed date and time, including timezone when known. Never invent missing details.' },
    doctor_name: { type: 'STRING' },
    confirmed: { type: 'BOOLEAN', description: 'True only after explicit caller agreement.' }
  }, required: ['customer_name', 'appointment_date', 'doctor_name', 'confirmed'] }
};
async function save(session, args, persist) {
  if (!args || args.confirmed !== true || !session.phoneConfirmed || !/^[0-9]{7,15}$/.test(session.phoneDigits || '') ||
      ['customer_name', 'appointment_date', 'doctor_name'].some(k => typeof args[k] !== 'string' || !args[k].trim() || args[k].length > 200)) {
    return { status: 'invalid', message: 'Save requires caller-confirmed phone and appointment details. Ask only for missing details; do not repeat confirmed information.' };
  }
  if (session.appointmentSaving) return { status: 'busy', message: 'Save already in progress; do not claim success yet.' };
  const key = JSON.stringify([session.phoneDigits, args.customer_name.trim(), args.appointment_date.trim(), args.doctor_name.trim()]);
  if (session.savedAppointmentKey === key) return { status: 'saved', message: 'These details are already recorded in CRM, not an external calendar reservation.' };
  session.appointmentSaving = true;
  try {
    const notes = `Appointment Date: ${args.appointment_date.trim()} | Doctor: ${args.doctor_name.trim()}`;
    const result = await persist(session.phoneDigits, args.customer_name.trim(), notes);
    if (!result || !result.data || !result.data.id) throw new Error('Save not confirmed');
    session.collectedInfo = { ...session.collectedInfo, name: args.customer_name.trim(), phone: session.phoneDigits,
      appointmentDate: args.appointment_date.trim(), doctorName: args.doctor_name.trim() };
    session.savedAppointmentKey = key;
    return { status: 'saved', message: 'Appointment details recorded in CRM. This does not reserve an external calendar slot.' };
  } catch (_) {
    return { status: 'unavailable', message: 'CRM did not confirm saving. Do not say the appointment is booked.' };
  } finally { session.appointmentSaving = false; }
}
module.exports = { name, declaration, save };
