// Read-only event subscription: retain final diagnostics after channels disappear.
const net = require('net');
const config = require('../config');
const outcomes = new Map();
const ttl = 5 * 60 * 1000;
let started = false;

const remember = (event) => {
  const uuid = event['Unique-ID'];
  if (event['Event-Name'] !== 'CHANNEL_HANGUP_COMPLETE' || !uuid || event['Call-Direction'] === 'inbound') return;
  const raw = [event.variable_sip_term_status, event.variable_sip_invite_failure_status]
    .find((value) => /^[1-6]\d{2}$/.test(String(value)));
  const sipStatus = raw ? Number(raw) : null;
  outcomes.set(uuid, {
    sipStatus,
    sipReason: event.variable_sip_term_phrase || event.variable_sip_invite_failure_phrase || null,
    hangupCause: event['Hangup-Cause'] || null,
    expires: Date.now() + ttl
  });
  // Bound memory even when unrelated SIP traffic is high.
  while (outcomes.size > 2000) outcomes.delete(outcomes.keys().next().value);
};
const get = (uuid) => {
  const value = outcomes.get(uuid);
  if (!value) return null;
  if (value.expires <= Date.now()) { outcomes.delete(uuid); return null; }
  const { expires, ...diagnostics } = value;
  return diagnostics;
};

const start = () => {
  if (started) return;
  started = true;
  const connect = () => {
    const socket = new net.Socket();
    let buffer = Buffer.alloc(0);
    let authed = false;
    const handshake = setTimeout(() => socket.destroy(), 5000);
    socket.on('error', (error) => console.warn('[call outcomes] connection failed:', error.message));
    socket.on('close', () => {
      clearTimeout(handshake);
      setTimeout(connect, 2000).unref();
    });
    socket.on('data', (chunk) => {
      buffer = Buffer.concat([buffer, chunk]);
      while (true) {
        const end = buffer.indexOf('\n\n');
        if (end < 0) return;
        const header = buffer.subarray(0, end).toString();
        const length = Number(header.match(/Content-Length:\s*(\d+)/i)?.[1] || 0);
        if (buffer.length < end + 2 + length) return;
        const body = buffer.subarray(end + 2, end + 2 + length).toString();
        buffer = buffer.subarray(end + 2 + length);
        if (header.includes('Content-Type: auth/request')) {
          socket.write(`auth ${config.freeswitch.password}\n\n`);
        } else if (header.includes('Content-Type: command/reply')) {
          if (!header.includes('Reply-Text: +OK')) { socket.destroy(); return; }
          if (!authed) {
            authed = true;
            socket.write('event json CHANNEL_HANGUP_COMPLETE\n\n');
          } else {
            clearTimeout(handshake);
          }
        } else if (header.includes('Content-Type: text/event-json')) {
          try { remember(JSON.parse(body)); } catch (error) {
            console.warn('[call outcomes] invalid event:', error.message);
          }
        } else if (header.includes('Content-Type: text/disconnect-notice')) {
          socket.destroy();
          return;
        }
      }
    });
    socket.connect(config.freeswitch.port, config.freeswitch.host);
  };
  connect();
};
module.exports = { start, get, remember };
