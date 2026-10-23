const config = require('../config');

/**
 * VICIdial API integration.
 * Provides call disposition updates and customer lookup via VICIdial's
 * non-agent API (vicidial non_agent_api.php).
 *
 * Requires VICIDIAL_API_URL and VICIDIAL_API_USER / VICIDIAL_API_PASS
 * to be configured. Fails closed when not configured.
 */
class VicidialApiService {
  constructor() {
    this.baseUrl = optionalEnv(process.env.VICIDIAL_API_URL, null);
    this.user = optionalEnv(process.env.VICIDIAL_API_USER, null);
    this.pass = optionalEnv(process.env.VICIDIAL_API_PASS, null);
    this.sourceIp = optionalEnv(process.env.VICIDIAL_API_SOURCE_IP, null);
    this.enabled = !!(this.baseUrl && this.user && this.pass);
  }

  async request(params, signal) {
    if (!this.enabled) {
      throw new Error('VICIDIAL_API not configured');
    }
    const url = new URL(this.baseUrl);
    url.searchParams.set('user', this.user);
    url.searchParams.set('pass', this.pass);
    url.searchParams.set('source', this.sourceIp || '');
    Object.entries(params).forEach(([k, v]) => {
      if (v !== undefined && v !== null) url.searchParams.set(k, String(v));
    });

    const res = await fetch(url.toString(), { signal, headers: { Accept: 'text/plain' } });
    const text = await res.text();
    if (!res.ok) throw new Error(`VICIDIAL API HTTP ${res.status}: ${text.slice(0, 200)}`);
    return text.trim();
  }

  async setDisposition(callId, disposition, notes = '') {
    return this.request({
      function: 'vicidial_closer_log',
      call_id: callId,
      status: disposition,
      user: this.user,
      notes: notes || ''
    });
  }

  async updateCallLog(callId, fields) {
    const params = { function: 'vicidial_closer_log', call_id: callId };
    if (fields.disposition) params.status = fields.disposition;
    if (fields.notes) params.notes = fields.notes;
    if (fields.user) params.user = fields.user;
    return this.request(params);
  }

  async lookupCustomerByPhone(phone) {
    const digits = String(phone || '').replace(/\D/g, '');
    if (!digits) return null;
    try {
      const text = await this.request({
        function: 'vicidial_list',
        search: digits,
        columns: 'first_name,last_name,phone_number,address1,city,state,postal_code'
      });
      return text ? { raw: text } : null;
    } catch (err) {
      console.warn('[vicidial-api] customer lookup failed', { phone: digits, error: err.message });
      return null;
    }
  }

  isEnabled() {
    return this.enabled;
  }
}

const optionalEnv = (value, fallback = null) => {
  if (typeof value !== 'string') return fallback;
  const trimmed = value.trim();
  return trimmed.length > 0 ? trimmed : fallback;
};

module.exports = { VicidialApiService };
