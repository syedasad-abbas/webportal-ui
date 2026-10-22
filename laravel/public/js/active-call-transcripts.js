(() => {
    const root = document.getElementById('active-transcripts');
    if (!root) return;
    const list = root.querySelector('[data-call-list]');
    const status = root.querySelector('[data-feed-status]');
    const rows = new Map();
    let selected = null, stopped = false, pollTimer;
    const element = (tag, text, className) => {
        const node = document.createElement(tag);
        node.textContent = text;
        if (className) node.className = className;
        return node;
    };
    const remove = uuid => {
        const row = rows.get(uuid);
        if (row) row.container.remove();
        rows.delete(uuid);
        if (selected === uuid) selected = null;
    };
    const request = async url => {
        const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: AbortSignal.timeout(8000) });
        if (!response.ok) { const error = new Error('Request failed'); error.status = response.status; throw error; }
        return response.json();
    };
    const refreshTranscript = async uuid => {
        const row = rows.get(uuid);
        if (!row || row.loading) return;
        row.loading = true;
        try {
            const data = await request(root.dataset.transcriptUrl.replace('__UUID__', encodeURIComponent(uuid)));
            if (rows.get(uuid) !== row || selected !== uuid || stopped) return;
            row.content.textContent = data.transcript || 'No customer speech transcribed yet.';
        } catch (error) {
            if (rows.get(uuid) !== row || stopped) return;
            if (error.status === 404) remove(uuid);
            else row.content.textContent = 'Transcript temporarily unavailable.';
        } finally { row.loading = false; }
    };
    const reconcile = calls => {
        const live = new Set(calls.map(call => call.uuid));
        for (const uuid of rows.keys()) if (!live.has(uuid)) remove(uuid);
        for (const call of calls) {
            let row = rows.get(call.uuid);
            if (!row) {
                const container = element('div', '', 'border border-slate-200 rounded-lg p-3');
                container.dataset.callUuid = call.uuid;
                const button = element('button', '', 'w-full text-left');
                button.type = 'button';
                button.setAttribute('aria-expanded', 'false');
                const label = element('span', '', 'block text-sm font-medium');
                const timer = element('span', '', 'block text-xs text-gray-500');
                const content = element('div', 'Loading customer transcript…', 'mt-2 text-sm whitespace-pre-wrap');
                content.hidden = true;
                button.append(label, timer);
                container.append(button, content);
                list.append(container);
                row = { container, button, label, timer, content, loading: false };
                rows.set(call.uuid, row);
                button.addEventListener('click', () => {
                    selected = selected === call.uuid ? null : call.uuid;
                    for (const [uuid, item] of rows) {
                        item.content.hidden = uuid !== selected;
                        item.button.setAttribute('aria-expanded', String(uuid === selected));
                    }
                    if (selected) void refreshTranscript(selected);
                });
            }
            row.started = Number(call.started);
            row.label.textContent = `${call.caller || 'Customer'} → ${call.destination || ''}`;
        }
        status.textContent = rows.size ? 'Live • refreshes every 3 seconds' : 'No active customer calls at the moment.';
    };
    const poll = async () => {
        try {
            const data = await request(root.dataset.callsUrl);
            if (stopped) return;
            if (!Array.isArray(data.calls)) throw new Error('Invalid active call list');
            reconcile(data.calls);
            if (selected) void refreshTranscript(selected);
        } catch (error) {
            if (stopped) return;
            // An unavailable feed cannot attest that old rows are still live.
            for (const uuid of rows.keys()) remove(uuid);
            status.textContent = 'Active calls temporarily unavailable. Retrying…';
        } finally {
            if (!stopped) pollTimer = setTimeout(poll, 3000);
        }
    };
    const timer = setInterval(() => {
        for (const row of rows.values()) {
            const seconds = Math.max(0, Math.floor((Date.now() - row.started) / 1000));
            row.timer.textContent = Number.isFinite(seconds)
                ? `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')} · Live` : 'Live';
        }
    }, 1000);
    window.addEventListener('pagehide', () => { stopped = true; clearTimeout(pollTimer); clearInterval(timer); });
    void poll();
})();
