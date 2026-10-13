(() => {
    const start = () => {
        const configElement = document.getElementById('ai-assist-config');
        const submit = document.getElementById('ai-assist-submit');
        if (!configElement || !submit) return;

        const config = JSON.parse(configElement.dataset.config || '{}');
        const question = document.getElementById('ai-assist-question');
        const callNotes = document.getElementById('ai-assist-notes');
        const status = document.getElementById('ai-assist-status');
        const answer = document.getElementById('ai-assist-answer');

        submit.addEventListener('click', async () => {
            if (!question.value.trim()) {
                question.focus();
                status.textContent = 'Enter a question or response request.';
                return;
            }

            submit.disabled = true;
            status.textContent = 'Preparing suggestion…';
            answer.classList.add('hidden');
            answer.textContent = '';
            try {
                const response = await fetch(config.url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrf
                    },
                    body: JSON.stringify({ question: question.value.trim(), callNotes: callNotes.value.trim() })
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok || !result.ok || !result.suggestion) {
                    throw new Error(result.message || 'AI assistance is unavailable.');
                }
                answer.textContent = result.suggestion;
                answer.classList.remove('hidden');
                status.textContent = 'Suggestion ready. Verify details before using it.';
            } catch (error) {
                status.textContent = error.message;
            } finally {
                submit.disabled = false;
            }
        });
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();