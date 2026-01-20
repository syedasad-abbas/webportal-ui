@extends('backend.layouts.app')

@php
    $webrtcConfig = [
        'wsUrl' => config('services.webrtc.ws'),
        'domain' => config('services.webrtc.domain'),
        'username' => config('services.webrtc.username'),
        'password' => config('services.webrtc.password'),
        'iceServers' => config('services.webrtc.ice_servers'),
    ];
@endphp

@section('title')
    {{ __('WebPhone dialer') }} | {{ config('app.name') }}
@endsection

@section('admin-content')
<div class="p-4 mx-auto max-w-7xl md:p-6">
    <x-breadcrumbs :breadcrumbs="[
        'title' => __('WebPhone dialer'),
        'items' => [
            [
                'label' => __('Dashboard'),
                'url' => route('admin.dashboard'),
            ],
        ],
    ]" />

    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="p-5 space-y-6 border-t border-gray-100 dark:border-gray-800 sm:p-6">

                <div>
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        {{ __('WebPhone dialer') }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Launch real PSTN calls with live controls, recording, and status badges.') }}
                    </p>
                </div>

                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-300">
                    {{ __('Ready · Calls run inline with mute/unmute, hang up, DTMF, and status badges.') }}
                </div>

                <form id="dialer-form" method="POST" action="{{ route('admin.dialer.dial') }}" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        {{-- Dialpad Destination (inline) --}}
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ __('Destination Number') }}
                            </label>

                            <div class="mt-2 dialpad">
                                <div class="dialpad-display flex gap-2">
                                    <input
                                        type="text"
                                        id="dialpad-display"
                                        placeholder="{{ __('Type or paste a number') }}"
                                        inputmode="tel"
                                        autocomplete="tel"
                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                    >
                                    <input type="hidden" name="destination" id="dialpad-input" required>
                                </div>

                                <div class="dialpad-grid mt-4" aria-label="Dial pad">
                                    @php
                                        $keys = [
                                            ['1',''], ['2','ABC'], ['3','DEF'],
                                            ['4','GHI'], ['5','JKL'], ['6','MNO'],
                                            ['7','PQRS'], ['8','TUV'], ['9','WXYZ'],
                                            ['*',''], ['0','+'], ['#',''],
                                        ];
                                    @endphp

                                    @foreach($keys as [$k,$sub])
                                        <button
                                            type="button"
                                            class="dialpad-key"
                                            data-value="{{ $k }}"
                                        >
                                            <div class="leading-none">{{ $k }}</div>
                                            <div class="mt-1 text-[10px]">{{ $sub ?: ' ' }}</div>
                                        </button>
                                    @endforeach
                                </div>

                                <div class="dialpad-actions mt-3">
                                    <button type="button" class="btn-default" id="dialpad-clear">{{ __('Clear') }}</button>
                                    <button type="button" class="btn-default" id="dialpad-backspace">{{ __('Delete') }}</button>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mt-6 flex justify-start gap-4">
                        <button type="submit" class="btn-primary">{{ __('Start call') }}</button>
                        <a href="{{ route('admin.dashboard') }}" class="btn-default">{{ __('Cancel') }}</a>
                    </div>
                </form>

                <div id="dialer-alert"
                     class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300">
                </div>

                {{-- Embedded Live Call Session (inline; no popup) --}}
                <div id="live-call-session" class="hidden mt-6 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                    <div class="mb-4">
                        <h3 class="text-base font-medium text-gray-800 dark:text-white/90">{{ __('Live call session') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Monitor status and control the call in real time.') }}</p>
                    </div>

                    <div id="call-status" class="inline-flex items-center rounded-full border border-gray-200 px-3 py-1 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-200">
                        {{ __('Connecting') }}
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" class="btn-default" data-action="mute" disabled>{{ __('Mute') }}</button>
                        <button type="button" class="btn-default" data-action="unmute" disabled>{{ __('Unmute') }}</button>
                        <button type="button" class="btn-danger" data-action="hangup" disabled>{{ __('Hang up') }}</button>
                    </div>

                    <div id="call-alert" class="hidden mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300"></div>

                    <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800">
                        <div class="hidden text-xs text-gray-500 dark:text-gray-400" id="call-id-badge"></div>
                        <div class="hidden mt-2 text-xs text-gray-500 dark:text-gray-400" id="call-timer-badge">
                            {{ __('Duration') }} · <span id="call-timer">00:00</span>
                        </div>
                        <div class="hidden mt-2 text-xs text-gray-500 dark:text-gray-400" id="browser-audio-status"></div>
                        <audio id="dialer-audio" class="hidden" autoplay playsinline></audio>
                    </div>
                </div>

            </div>
        </div>
        <div id="dialer-webrtc-config" data-config='@json($webrtcConfig)' class="hidden" aria-hidden="true"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('dialer-form');
    const alertBox = document.getElementById('dialer-alert');

    const displayInput = document.getElementById('dialpad-display');
    const hiddenInput = document.getElementById('dialpad-input');
    const dialpadButtons = document.querySelectorAll('.dialpad-key');
    const clearButton = document.getElementById('dialpad-clear');
    const backspaceButton = document.getElementById('dialpad-backspace');

    // Live session UI
    const liveSession = document.getElementById('live-call-session');
    const statusEl = document.getElementById('call-status');
    const alertEl = document.getElementById('call-alert');
    const actionButtons = document.querySelectorAll('#live-call-session [data-action]');
    const callIdBadge = document.getElementById('call-id-badge');
    const callTimerBadge = document.getElementById('call-timer-badge');
    const callTimerEl = document.getElementById('call-timer');
    const browserAudioStatus = document.getElementById('browser-audio-status');
    const webrtcConfigEl = document.getElementById('dialer-webrtc-config');
    const remoteAudioEl = document.getElementById('dialer-audio');

    if (!form) return;

    const csrfToken = form.querySelector('input[name="_token"]').value;
    const startCallButton = form.querySelector('button[type="submit"]');

    // ===== DTMF local tone =====
    const dtmfMap = {
        '1': [697, 1209],
        '2': [697, 1336],
        '3': [697, 1477],
        '4': [770, 1209],
        '5': [770, 1336],
        '6': [770, 1477],
        '7': [852, 1209],
        '8': [852, 1336],
        '9': [852, 1477],
        '*': [941, 1209],
        '0': [941, 1336],
        '#': [941, 1477]
    };

    let toneContext = null;
    let toneGain = null;
    let toneOscillators = [];

    const ensureToneContext = () => {
        if (!toneContext) {
            toneContext = new (window.AudioContext || window.webkitAudioContext)();
            toneGain = toneContext.createGain();
            toneGain.gain.value = 0.12;
            toneGain.connect(toneContext.destination);
        }
    };

    const stopTone = () => {
        toneOscillators.forEach((osc) => {
            try { osc.stop(); } catch (e) {}
        });
        toneOscillators = [];
    };

    const playTone = async (value) => {
        const freqs = dtmfMap[value];
        if (!freqs) return;

        ensureToneContext();
        try {
            if (toneContext.state === 'suspended') {
                await toneContext.resume();
            }
        } catch (e) {
            return;
        }

        stopTone();
        toneOscillators = freqs.map((freq) => {
            const osc = toneContext.createOscillator();
            osc.type = 'sine';
            osc.frequency.value = freq;
            osc.connect(toneGain);
            osc.start();
            return osc;
        });

        setTimeout(stopTone, 120);
    };

    const syncDisplay = (value) => {
        if (displayInput) displayInput.value = value;
        if (hiddenInput) hiddenInput.value = value;
    };

    // Allow paste into readonly display
    const sanitizePhone = (value) => (value || '').toString().replace(/[^\d+*#]/g, '');
    const applyPastedValue = (text) => syncDisplay(sanitizePhone(text));

    if (displayInput) {
        displayInput.addEventListener('input', (e) => {
            if (callActive) {
                syncDisplay(hiddenInput.value || '');
                return;
            }
            const cleaned = sanitizePhone(e.target.value);
            if (cleaned !== e.target.value) {
                e.target.value = cleaned;
            }
            syncDisplay(cleaned);
        });

        displayInput.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            if (!callActive) applyPastedValue(text);
        });

        document.addEventListener('paste', (e) => {
            if (document.activeElement !== displayInput) return;
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            if (!callActive) applyPastedValue(text);
        });

        displayInput.addEventListener('click', () => displayInput.focus());
    }

    // ===== Live call state =====
    let callUuid = null;
    let pollHandle = null;
    let callActive = false;
    let conferenceName = null;
    let browserAudioActive = false;
    let webRtcClient = null;
    let browserAudioConnecting = false;
    let hangupInProgress = false;

    // timer state
    let callConnectedAt = null;
    let timerHandle = null;

    const formatDuration = (seconds) => {
        const s = Math.max(0, Number(seconds) || 0);
        const mm = Math.floor(s / 60);
        const ss = s % 60;
        const mmStr = mm < 10 ? `0${mm}` : `${mm}`;
        const ssStr = ss < 10 ? `0${ss}` : `${ss}`;
        return `${mmStr}:${ssStr}`;
    };

    const updateTimer = () => {
        if (!callConnectedAt) return;
        const seconds = Math.floor((Date.now() - callConnectedAt) / 1000);
        if (callTimerEl) callTimerEl.textContent = formatDuration(seconds);
    };

    const startTimer = () => {
        if (timerHandle) return;
        callConnectedAt = callConnectedAt || Date.now();
        if (callTimerBadge) callTimerBadge.classList.remove('hidden');
        updateTimer();
        timerHandle = setInterval(updateTimer, 1000);
    };

    const stopTimer = () => {
        if (timerHandle) {
            clearInterval(timerHandle);
            timerHandle = null;
        }
        callConnectedAt = null;
        if (callTimerEl) callTimerEl.textContent = '00:00';
        if (callTimerBadge) callTimerBadge.classList.add('hidden');
    };

    const setControls = (enabled) => {
        actionButtons.forEach((btn) => {
            btn.disabled = !enabled;
        });
    };

    const setStatus = (status, sipStatus = null, sipReason = null) => {
        const normalized = (status || '').toLowerCase();
        const sipText = sipStatus ? `SIP ${sipStatus}${sipReason ? ` ${sipReason}` : ''}` : null;

        const labelMap = {
            queued: 'Trying',
            ringing: 'Ringing',
            in_call: 'Answered',
            completed: 'Completed',
            ended: 'Ended'
        };

        if (statusEl) {
            if ((normalized === 'ended' || normalized === 'completed') && sipText) {
                statusEl.textContent = sipText;
            } else {
                statusEl.textContent = labelMap[normalized] || 'Unknown';
            }
        }

        if (normalized === 'in_call') {
            startTimer();
            if (conferenceName && webRtcClient && !browserAudioActive && !browserAudioConnecting && !hangupInProgress) {
                connectBrowserAudio();
            }
        }

        if (normalized === 'ended' || normalized === 'completed') {
            stopTimer();
            disconnectBrowserAudio();
            conferenceName = null;
            callUuid = null;
        }
    };

    const showError = (message) => {
        if (!alertEl) return;
        alertEl.textContent = message || 'Unable to update call.';
        alertEl.classList.remove('hidden');
    };

    const initWebRtcClient = () => {
        if (!window.DialerWebRTC || !webrtcConfigEl || !remoteAudioEl) {
            return null;
        }
        try {
            const config = JSON.parse(webrtcConfigEl.dataset.config || '{}');
            if (!config.wsUrl || !config.domain || !config.username || !config.password) {
                return null;
            }
            config.remoteAudioSelector = '#dialer-audio';
            return new window.DialerWebRTC(config);
        } catch (error) {
            console.error('Invalid WebRTC config', error);
            return null;
        }
    };

    const updateBrowserAudioStatus = (text, hasError = false) => {
        if (!browserAudioStatus) return;
        if (!text) {
            browserAudioStatus.classList.add('hidden');
            browserAudioStatus.textContent = '';
            return;
        }
        browserAudioStatus.textContent = text;
        browserAudioStatus.classList.remove('hidden');
        if (hasError) {
            browserAudioStatus.classList.add('text-red-600', 'dark:text-red-300');
        } else {
            browserAudioStatus.classList.remove('text-red-600', 'dark:text-red-300');
        }
    };

    const connectBrowserAudio = async () => {
        if (!webRtcClient || !conferenceName || browserAudioActive || browserAudioConnecting || hangupInProgress) {
            return;
        }
        browserAudioConnecting = true;
        updateBrowserAudioStatus('Connecting browser audio…');
        try {
            await webRtcClient.joinConference(conferenceName);
            browserAudioActive = true;
            updateBrowserAudioStatus('Browser audio connected');
        } catch (error) {
            console.error('Failed to connect browser audio', error);
            browserAudioActive = false;
            updateBrowserAudioStatus('Browser audio unavailable', true);
            showError('Unable to connect browser audio.');
        } finally {
            browserAudioConnecting = false;
        }
    };

    const disconnectBrowserAudio = async () => {
        if (!webRtcClient) return;
        try {
            await webRtcClient.leaveConference();
        } catch (error) {
            console.error('Failed to disconnect browser audio', error);
        }
        browserAudioActive = false;
        browserAudioConnecting = false;
        updateBrowserAudioStatus(webRtcClient ? 'Browser audio idle' : '');
    };

    const ensureWebRtcClient = () => {
        if (!webRtcClient) {
            webRtcClient = initWebRtcClient();
            if (webRtcClient) {
                updateBrowserAudioStatus('Browser audio idle');
                webRtcClient.ensureClient().then(() => {
                    updateBrowserAudioStatus('Browser audio ready');
                }).catch((error) => {
                    console.error('Failed to register WebRTC client', error);
                    updateBrowserAudioStatus('Browser audio unavailable', true);
                });
            } else {
                updateBrowserAudioStatus('');
            }
        }
        return webRtcClient;
    };

    ensureWebRtcClient();

    const pollStatus = async () => {
        if (hangupInProgress) return;
        try {
            const response = await fetch(`/admin/dialer/calls/${callUuid}/status`, {
                headers: { 'Accept': 'application/json' }
            });

            if (!response.ok) {
                setStatus('ended');
                showError(`HTTP ${response.status}`);
                callActive = false;
                setControls(false);
                startCallButton.disabled = false;
                stopTimer();
                return;
            }

            const data = await response.json();
            if (hangupInProgress) return;
            if (data.conferenceName) {
                conferenceName = data.conferenceName;
            }
            setStatus(data.status, data.sipStatus, data.sipReason);

            if (data.status === 'in_call' || data.status === 'ringing' || data.status === 'queued') {
                callActive = true;
                setControls(true);
            }

            if (data.status === 'ended' || data.status === 'completed') {
                clearInterval(pollHandle);
                callActive = false;
                setControls(false);
                startCallButton.disabled = false;
                stopTimer();
                disconnectBrowserAudio();
                conferenceName = null;
                callUuid = null;
            }
        } catch (e) {
            setStatus('ended');
            showError('Network error while updating the call.');
            callActive = false;
            setControls(false);
            startCallButton.disabled = false;
            stopTimer();
            disconnectBrowserAudio();
        }
    };

    // Live call actions
    actionButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const action = button.dataset.action;
            if (!callUuid) return;
            if (hangupInProgress) return;

            if (alertEl) alertEl.classList.add('hidden');

            try {
                if (action === 'hangup') {
                    hangupInProgress = true;
                    setControls(false);
                }
                const response = await fetch(`/admin/dialer/calls/${callUuid}/${action}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                if (!response.ok) {
                    let data = {};
                    try { data = await response.json(); } catch (e) {}
                    showError(data.message || `HTTP ${response.status}`);
                    if (action === 'hangup') {
                        hangupInProgress = false;
                        setControls(callActive);
                    }
                    return;
                }

                if (action === 'hangup') {
                    setStatus('completed');
                    clearInterval(pollHandle);
                    callActive = false;
                    setControls(false);
                    startCallButton.disabled = false;
                    stopTimer();
                    disconnectBrowserAudio();
                }
            } catch (e) {
                showError('Network error while updating the call.');
                if (action === 'hangup') {
                    hangupInProgress = false;
                    setControls(callActive);
                }
            }
        });
    });

    // ===== Dialpad logic =====
    const LONG_PRESS_MS = 500;
    let longPressTimer = null;
    let longPressActive = false;

    dialpadButtons.forEach((button) => {
        const value = button.dataset.value || '';

        const handlePress = () => {
            if (value !== '0') return;
            longPressActive = false;
            clearTimeout(longPressTimer);
            longPressTimer = setTimeout(() => {
                longPressActive = true;

                if (!callActive) {
                    syncDisplay(`${hiddenInput.value || ''}+`);
                    playTone('0');
                }
            }, LONG_PRESS_MS);
        };

        const handleRelease = () => {
            if (value !== '0') return;
            clearTimeout(longPressTimer);
        };

        button.addEventListener('mousedown', handlePress);
        button.addEventListener('touchstart', handlePress, { passive: true });
        button.addEventListener('mouseup', handleRelease);
        button.addEventListener('mouseleave', handleRelease);
        button.addEventListener('touchend', handleRelease);
        button.addEventListener('touchcancel', handleRelease);

        button.addEventListener('click', async () => {
            if (value === '0' && longPressActive) {
                longPressActive = false;
                return;
            }

            // During call: send DTMF
            if (callActive && callUuid) {
                playTone(value);
                if (alertEl) alertEl.classList.add('hidden');

                try {
                    const response = await fetch(`/admin/dialer/calls/${callUuid}/dtmf`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ digits: value })
                    });

                    if (!response.ok) {
                        let data = {};
                        try { data = await response.json(); } catch (e) {}
                        showError(data.message || `HTTP ${response.status}`);
                    }
                } catch (e) {
                    showError('Network error while sending DTMF.');
                }
                return;
            }

            // Before call: build number
            syncDisplay(`${hiddenInput.value || ''}${value}`);
            playTone(value);
        });
    });

    if (clearButton) {
        clearButton.addEventListener('click', () => {
            if (callActive) return;
            syncDisplay('');
        });
    }

    if (backspaceButton) {
        backspaceButton.addEventListener('click', () => {
            if (callActive) return;
            const current = hiddenInput.value || '';
            syncDisplay(current.slice(0, -1));
        });
    }

    // ===== Start call (inline; no popup) =====
    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        // hide alert
        alertBox.classList.add('hidden');
        alertBox.textContent = '';

        disconnectBrowserAudio();
        conferenceName = null;
        callUuid = null;

        // show live session
        if (liveSession) liveSession.classList.remove('hidden');
        if (callIdBadge) callIdBadge.classList.add('hidden');
        if (alertEl) alertEl.classList.add('hidden');

        setStatus('queued');
        stopTimer();
        hangupInProgress = false;

        const payload = {
            destination: hiddenInput ? hiddenInput.value : ''
        };

        startCallButton.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                let error = {};
                try { error = await response.json(); } catch (e) {}
                alertBox.textContent = error.message || `HTTP ${response.status}`;
                alertBox.classList.remove('hidden');
                startCallButton.disabled = false;
                setStatus('ended');
                showError(`HTTP ${response.status}`);
                return;
            }

            const data = await response.json();
            if (data.callUuid) {
                callUuid = data.callUuid;
                conferenceName = data.conference || null;

                if (callIdBadge) {
                    callIdBadge.textContent = `Call ID · ${callUuid}`;
                    callIdBadge.classList.remove('hidden');
                }

                callActive = true;
                setControls(true);
                if (conferenceName && webRtcClient) {
                    connectBrowserAudio();
                }

                // poll status
                pollStatus();
                if (pollHandle) clearInterval(pollHandle);
                pollHandle = setInterval(pollStatus, 1000);
            } else {
                alertBox.textContent = 'Call queued but no call identifier returned.';
                alertBox.classList.remove('hidden');
                startCallButton.disabled = false;
                setStatus('ended');
                disconnectBrowserAudio();
            }
        } catch (error) {
            alertBox.textContent = 'Network error while queuing the call.';
            alertBox.classList.remove('hidden');
            startCallButton.disabled = false;
            setStatus('ended');
            showError('Network error while queuing the call.');
            disconnectBrowserAudio();
        }
    });

    // initial
    setControls(false);
    if (!webRtcClient) {
        updateBrowserAudioStatus('');
    }
});
</script>
@endpush
