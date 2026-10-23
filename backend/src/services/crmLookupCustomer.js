const name = 'lookup_crm_customer';
const declaration = {
  name,
  description: 'Look up a caller\'s previous appointment details from the test CRM by phone number. Returns the customer name, phone, appointment date, and doctor if a previous record exists. Use this when the caller asks about their existing appointment.',
  parameters: { type: 'OBJECT', properties: {
    phone: { type: 'STRING', description: 'Caller\'s phone number, digits only, 7 to 15 digits.' }
  }, required: ['phone'] }
};
async function lookup(session, args, persist) {
  if (!args || !/^[0-9]{7,15}$/.test(args.phone || '') || typeof args.phone !== 'string') {
    return { status: 'invalid', message: 'Provide the caller\'s phone number in digits only.' };
  }
  try {
    const result = await persist(args.phone, '', '');
    const customer = result.data || null;
    if (!customer) return { status: 'not_found', message: 'No CRM record found for this phone number.' };
    const notes = String(customer.notes || '');
    const apptMatch = notes.match(/Appointment Date:\s*([^\|]+)/i);
    const doctorMatch = notes.match(/Doctor:\s*([^\|]+)/i);
    return {
      status: 'found',
      customer_name: customer.name || '',
      phone: customer.phone || '',
      appointment_date: apptMatch ? apptMatch[1].trim() : 'not set',
      doctor_name: doctorMatch ? doctorMatch[1].trim() : 'not set',
      notes
    };
  } catch (_) {
    return { status: 'unavailable', message: 'CRM lookup failed. Do not invent appointment details.' };
  }
}
module.exports = { name, declaration, lookup };
