const { createService, TOOL } = require('./crmReportService');
function createHandler(service = createService()) {
  const states = new WeakMap();
  const stateFor = session => {
    if (!states.has(session)) states.set(session, { pending: new Map(), seen: new Set() });
    return states.get(session);
  };
  return {
    tools() { const declaration = service.declaration(); return declaration ? [{ functionDeclarations: [declaration] }] : []; },
    cancel(session, ids) {
      const state = states.get(session);
      if (!state) return;
      for (const [id, job] of state.pending) if (!ids || ids.includes(id)) {
        job.abort(); state.pending.delete(id);
      }
    },
    handle(session, message, isCurrent) {
      if (message.toolCallCancellation?.ids) this.cancel(session, message.toolCallCancellation.ids);
      const calls = message.toolCall?.functionCalls;
      if (!Array.isArray(calls)) return;
      const state = stateFor(session);
      const socket = session.gemini;
      const send = (id, name, response) => {
        if (isCurrent() && session.gemini === socket && socket.readyState === 1) {
          try { socket.send(JSON.stringify({ toolResponse: { functionResponses: [{ id, name, response }] } })); } catch { /* Socket closed during send. */ }
        }
      };
      for (const call of calls.slice(0, 10)) {
        if (typeof call.id !== 'string' || !call.id || state.seen.has(call.id)) continue;
        if (state.seen.size >= 20) { send(call.id, call.name, { status: 'limit', message: 'Report request limit reached for this call.' }); continue; }
        state.seen.add(call.id);
        if (call.name !== TOOL || !service.declaration()) {
          send(call.id, call.name, { status: 'unavailable', message: 'This tool is not available.' }); continue;
        }
        if (state.pending.size) { send(call.id, call.name, { status: 'busy', message: 'A report request is already running.' }); continue; }
        const controller = new AbortController();
        state.pending.set(call.id, controller);
        Promise.resolve().then(() => {
          controller.signal.throwIfAborted();
          return service.run(session.callId, call.args, controller.signal);
        }).then(result => {
          if (!controller.signal.aborted) send(call.id, call.name, result);
        }).catch(() => {
          if (!controller.signal.aborted) send(call.id, call.name, { status: 'unavailable', message: 'Report retrieval failed. Do not invent results.' });
        }).finally(() => { if (state.pending.get(call.id) === controller) state.pending.delete(call.id); });
      }
    }
  };
}
module.exports = { createHandler };
