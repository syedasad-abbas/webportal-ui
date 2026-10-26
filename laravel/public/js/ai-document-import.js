(() => {
    const start = () => {
        const root = document.getElementById('ai-document-import');
        if (!root) return;
        const get = (suffix) => document.getElementById(`ai-document-${suffix}`);
        const fileInput = get('file'), analyze = get('analyze'), apply = get('apply');
        const status = get('status'), preview = get('preview');
        const targets = { role: 'ai-agent-role', greeting: 'ai-agent-greeting', goal: 'ai-agent-goal', knowledgeInstructions: 'ai-agent-knowledge', salesScript: 'ai-agent-sales-script' };
        const limits = { role: 200, greeting: 1000, goal: 2000, knowledgeInstructions: 10000, salesScript: 50000 };
        let busy = false;
        fileInput.addEventListener('change', () => { preview.hidden = true; status.textContent = ''; });
        analyze.addEventListener('click', async () => {
            if (busy) return;
            preview.hidden = true;
            const file = fileInput.files?.[0];
            if (!file || !/\.(pdf|jpe?g|png)$/i.test(file.name) || file.size <= 0 || file.size > 2 * 1024 * 1024) {
                status.textContent = 'Choose a PDF, JPG or PNG file up to 2 MB.';
                return;
            }
            busy = true; analyze.disabled = true; fileInput.disabled = true;
            status.textContent = 'Reading the file. Your saved settings will not change.';
            try {
                const data = new FormData(); data.append('document', file);
                const response = await fetch(root.dataset.url, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: data
                });
                const body = await response.json().catch(() => ({}));
                if (!response.ok || !body.ok) throw new Error(body.errors?.document?.[0] || body.message || 'Unable to read the file. Check its size and try again.');
                for (const [key, max] of Object.entries(limits)) {
                    if (typeof body.draft?.[key] !== 'string' || body.draft[key].length > max) throw new Error('Invalid draft returned. Try again.');
                }
                for (const key of Object.keys(targets)) get(key).value = body.draft[key];
                preview.hidden = false;
                status.textContent = 'Draft ready. Review it, then apply it to the form.';
            } catch (error) { status.textContent = error.message; }
            finally { busy = false; analyze.disabled = false; fileInput.disabled = false; }
        });
        apply.addEventListener('click', () => {
            if (busy || preview.hidden) return;
            if (!window.confirm('Replace the corresponding form fields with this draft? You can review them before Save settings.')) return;
            for (const [key, id] of Object.entries(targets)) {
                const text = get(key).value.trim();
                if (text.length > limits[key]) { status.textContent = 'A draft field is too long. Shorten it before applying.'; return; }
            }
            for (const [key, id] of Object.entries(targets)) {
                const text = get(key).value.trim();
                const target = document.getElementById(id);
                if (text && target) { target.value = text; target.dispatchEvent(new Event('input', { bubbles: true })); }
            }
            preview.hidden = true;
            status.textContent = 'Draft applied to the form. Click Save settings to use it for new calls.';
        });
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
