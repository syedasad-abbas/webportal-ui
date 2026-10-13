(() => {
    const start = () => {
        const element = document.getElementById('vicidial-config');
        if (!element) return;
        const config = JSON.parse(element.textContent);
        const feedback = document.getElementById('vici-feedback');
        if (!window.DialerWebRTC) {
            feedback.textContent = 'The browser phone assets are unavailable. Ask your administrator to deploy the frontend assets.';
            return;
        }
        const phone = new window.DialerWebRTC(config.webrtc);
        const page = document.getElementById('vicidial-page');
        const phoneStatus = document.getElementById('vici-phone-status');
        const buttons = [...page.querySelectorAll('button')];
        let registered = false;
        let ringing = false;
        let audioConnected = false;
        let muted = false;
        let busy = false;
        let leadTimer;
        let polling = false;
        const render = () => {
            buttons.forEach(button => {
                const action = button.dataset.viciAction || button.dataset.viciLocal;
                button.disabled = busy ||
                    (button.dataset.viciAction && !config.apiReady) ||
                    (action === 'register' && registered) ||
                    (action === 'connect' && (!registered || ringing || audioConnected)) ||
                    (action === 'answer' && !ringing) ||
                    (['ready', 'mute'].includes(action) && !audioConnected);
            });
        };
        const request = async (action, value) => {
            const response = await fetch(config.commandUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                body: JSON.stringify({ action, value })
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok || !result.ok) throw new Error(result.message || 'The request failed. Check your login and the VICIdial agent screen before retrying.');
            return result;
        };
        const updateLead = result => {
            const label = document.getElementById('vici-lead');
            if (label) label.textContent = result.lead ? `Customer: ${result.lead.phone} · Lead ${result.lead.id}` : 'No active customer lead.';
        };
        const stopPolling = () => { window.clearInterval(leadTimer); leadTimer = null; };
        const startPolling = () => {
            stopPolling();
            if (!config.apiReady || !config.leadLookup) return;
            leadTimer = window.setInterval(async () => {
                if (polling || busy || document.hidden || !audioConnected) return;
                polling = true;
                try { updateLead(await request('lead')); }
                catch (error) {
                    stopPolling();
                    const label = document.getElementById('vici-lead');
                    if (label) label.textContent = `Customer lookup stopped: ${error.message}`;
                } finally { polling = false; }
            }, 5000);
        };
        page.addEventListener('click', async event => {
            const button = event.target.closest('button');
            if (!button || busy || button.disabled) return;
            busy = true;
            render();
            feedback.textContent = '';
            try {
                const action = button.dataset.viciAction;
                if (action) {
                    const value = action === 'disposition' ? document.getElementById('vici-disposition')?.value : undefined;
                    if (action === 'disposition' && !value) throw new Error('Select a disposition first.');
                    const result = await request(action, value);
                    if (action === 'lead') { updateLead(result); if (audioConnected) startPolling(); }
                    else feedback.textContent = result.message;
                } else {
                    switch (button.dataset.viciLocal) {
                        case 'register':
                            await phone.ensureClient();
                            if (!registered) phoneStatus.textContent = 'Registration sent. Waiting for VICIdial to accept the browser phone.';
                            break;
                        case 'answer': await phone.answerIncoming(); break;
                        case 'mute':
                            muted = !muted;
                            await phone.setMuted(muted);
                            button.textContent = muted ? 'Unmute microphone' : 'Mute microphone';
                            break;
                        case 'audio': await phone.resumeAudio(); break;
                    }
                }
            } catch (error) { feedback.textContent = error.message; }
            finally { busy = false; render(); }
        });
        window.addEventListener('dialer:sip-registered', () => {
            registered = true;
            phoneStatus.textContent = 'Browser phone registered with VICIdial. Ready to receive agent audio or an internal test call.';
            render();
        });
        window.addEventListener('dialer:sip-registration-failed', () => {
            registered = false;
            phoneStatus.textContent = 'Registration rejected. Check the phone username and Registration Password.';
            render();
        });
        window.addEventListener('dialer:sip-incoming', () => {
            registered = true;
            ringing = true;
            phoneStatus.textContent = 'Agent audio is ringing. Select Accept agent audio.';
            render();
        });
        window.addEventListener('dialer:sip-answered', () => {
            ringing = false;
            audioConnected = true;
            phoneStatus.textContent = config.apiReady
                ? 'Agent audio connected. Select Ready for calls when your VICIdial session is logged in.'
                : 'Test audio connected. API customer call controls are not configured.';
            render();
            startPolling();
        });
        const audioEnded = message => {
            ringing = false;
            audioConnected = false;
            stopPolling();
            phoneStatus.textContent = message;
            const label = document.getElementById('vici-lead');
            if (label) label.textContent = '';
            render();
        };
        window.addEventListener('dialer:sip-hangup', () => audioEnded('Agent audio ended. Pause in VICIdial and reconnect before receiving more calls.'));
        window.addEventListener('dialer:sip-disconnected', () => {
            registered = false;
            audioEnded('Browser phone disconnected. Check your connection and pause in VICIdial.');
        });
        window.addEventListener('dialer:audio-status', event => {
            document.getElementById('vici-media-status').textContent = event.detail?.message || '';
        });
        window.addEventListener('beforeunload', event => {
            if (audioConnected || ringing) { event.preventDefault(); event.returnValue = ''; }
        });
        window.addEventListener('pagehide', () => {
            stopPolling();
            // Best effort only; keep the VICIdial screen available for explicit pause/logout.
            if (registered && config.apiReady) fetch(config.commandUrl, {
                method: 'POST', keepalive: true, credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                body: JSON.stringify({ action: 'pause' })
            }).catch(() => {});
            void phone.disconnect();
        });
        render();
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
