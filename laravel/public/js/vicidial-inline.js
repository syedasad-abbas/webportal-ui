(() => {
    const start = () => {
        const configElement = document.getElementById('vicidial-inline-config');
        if (!configElement) return;
        const config = JSON.parse(configElement.dataset.config || '{}');
        if (!config.enabled) return;

        const status = document.getElementById('vicidial-inline-status');
        const feedback = document.getElementById('vicidial-inline-feedback');
        const buttons = [...document.querySelectorAll('[data-vici-action]')];
        let registered = false;
        let audioConnected = false;
        let busy = false;

        const render = () => {
            const needsAudio = ['ready', 'dial', 'hangup', 'park', 'unpark', 'dtmf', 'conference', 'transfer', 'end_conference'];
            buttons.forEach((button) => {
                const action = button.dataset.viciAction;
                if (action === 'end_browser_call') {
                    button.disabled = busy || !audioConnected;
                    return;
                }
                button.disabled = busy || !config.apiReady ||
                    (action === 'connect' && !registered) ||
                    (needsAudio.includes(action) && (!registered || !audioConnected));
            });
        };

        const request = async (action, value, phoneCode) => {
            const response = await fetch(config.commandUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': config.csrf
                },
                body: JSON.stringify({ action, value, phone_code: phoneCode })
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok || !result.ok) {
                throw new Error(result.message || 'VICIdial did not accept the request.');
            }
            return result;
        };

        const leadText = document.getElementById('vicidial-inline-lead');
        document.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-vici-action]');
            if (!button || button.disabled || busy) return;
            const action = button.dataset.viciAction;
            const disposition = document.getElementById('vicidial-inline-disposition');
            if (action === 'disposition' && (!disposition || !disposition.value)) {
                feedback.textContent = 'Select a disposition first.';
                return;
            }
            let value;
            let phoneCode;
            if (action === 'disposition') value = disposition.value;
            if (action === 'dial') {
                value = document.getElementById('vicidial-inline-number')?.value.trim();
                phoneCode = document.getElementById('vicidial-inline-country')?.value.trim();
                if (!/^[0-9]{3,20}$/.test(value || '') || !/^[0-9]{1,4}$/.test(phoneCode || '')) {
                    feedback.textContent = 'Enter a phone number and country code using digits only.';
                    return;
                }
            }
            if (action === 'dtmf') {
                value = document.getElementById('vicidial-inline-dtmf')?.value.trim();
                if (!/^[0-9*#]{1,20}$/.test(value || '')) {
                    feedback.textContent = 'Enter up to 20 digits, * or #.';
                    return;
                }
            }
            if (['conference', 'transfer'].includes(action)) {
                value = document.getElementById('vicidial-inline-target')?.value;
                if (!value) { feedback.textContent = 'Select a destination first.'; return; }
            }
            if (action === 'transfer' && !window.confirm('Transfer this customer to the selected destination?')) return;
            if (action === 'logout' && !window.confirm('Request logout of your VICIdial agent session?')) return;
            if (action === 'end_browser_call' && !window.confirm('End this browser phone call? If this is your VICIdial agent audio connection, you will need to reconnect it before taking more calls.')) return;

            busy = true;
            feedback.textContent = '';
            render();
            try {
                if (action === 'end_browser_call') {
                    await new Promise((resolve, reject) => {
                        window.dispatchEvent(new CustomEvent('dialer:end-browser-call', { detail: { resolve, reject } }));
                    });
                    audioConnected = false;
                    feedback.textContent = 'Browser phone call ended.';
                    return;
                }
                const result = await request(action, value, phoneCode);
                if (action === 'lead' && leadText) {
                    leadText.textContent = result.lead
                        ? `Customer: ${result.lead.phone} · Lead ${result.lead.id}`
                        : 'No active customer lead.';
                }
                feedback.textContent = result.message || 'Request accepted by VICIdial.';
            } catch (error) {
                feedback.textContent = error.message;
            } finally {
                busy = false;
                render();
            }
        });

        window.addEventListener('dialer:sip-registered', () => {
            registered = true;
            if (status) status.textContent = 'Browser phone connected. Log in to your VICIdial campaign session.';
            render();
        });
        window.addEventListener('dialer:sip-registration-failed', () => {
            registered = false;
            audioConnected = false;
            if (status) status.textContent = 'Browser phone registration failed. Check the VICIdial phone account.';
            render();
        });
        window.addEventListener('dialer:sip-incoming', () => {
            if (status) status.textContent = 'VICIdial audio is ringing. Answer using the dialer call banner.';
        });
        window.addEventListener('dialer:sip-answered', () => {
            audioConnected = true;
            if (status) status.textContent = 'VICIdial agent audio connected. Use Ready for calls when logged into a campaign.';
            render();
        });
        window.addEventListener('dialer:sip-hangup', () => {
            audioConnected = false;
            if (status) status.textContent = 'VICIdial audio disconnected. Reconnect before receiving more calls.';
            render();
        });
        window.addEventListener('dialer:sip-disconnected', () => {
            registered = false;
            audioConnected = false;
            if (status) status.textContent = 'Browser phone disconnected. Pause the VICIdial session before reconnecting.';
            render();
        });

        render();
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
