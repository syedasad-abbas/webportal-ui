@extends('backend.layouts.app')

@php
    $webrtcConfig = $webrtcConfig ?? [
        'wsUrl' => config('services.webrtc.ws'),
        'domain' => config('services.webrtc.domain'),
        'username' => null,
        'password' => null,
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

                @can('campaign.play')
                <div>
                    <h3 class="text-base font-medium text-gray-800 dark:text-white/90">
                        {{ __('WebPhone dialer') }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Launch real PSTN calls with live controls, recording, and status badges.') }}
                    </p>
                </div>


                 <div class="flex items-end gap-2">
                    <div>
                    <label class="block text-xs font-semibold text-gray-800 dark:text-gray-100">{{ __('Campaign') }}</label>
                    <select
                        id="campaign_id"
                        class="h-11 min-w-[230px] rounded-lg border px-3 pr-10 text-sm text-gray-800 dark:text-white/90 dark:bg-gray-900 dark:border-gray-700 truncate">
                        <option value="">{{ __('Select') }}</option>
                        @foreach($campaigns as $c)
                        <option value="{{ $c->id }}" @selected(optional($run)->campaign_id === $c->id)>
                            {{ $c->list_name }} ({{ $c->list_id }})
                        </option>
                        @endforeach
                    </select>
                    </div>

                    <div>
                    <label class="block text-xs font-semibold text-gray-800 dark:text-gray-100">{{ __('Agent') }}</label>
                    @php
                        $selectedAgent = optional($run)->agent;
                        $agentMatches = false;
                    @endphp
                    <select
                        id="agent_name"
                        class="h-11 min-w-[230px] rounded-lg border px-3 pr-10 text-sm text-gray-800 dark:text-white/90 dark:bg-gray-900 dark:border-gray-700"
                    >
                        <option value="">{{ __('Select agent') }}</option>
                        @foreach(($agents ?? collect()) as $agent)
                            @php
                                $value = $agent->external_name ?: $agent->email;
                                $label = $agent->external_name ? "{$agent->external_name} ({$agent->email})" : $agent->email;
                                $isSelected = $selectedAgent && $value === $selectedAgent;
                                $agentMatches = $agentMatches || $isSelected;
                            @endphp
                            <option value="{{ $value }}" @selected($isSelected)>
                                {{ $label }}
                            </option>
                        @endforeach
                        @if($selectedAgent && ! $agentMatches)
                            <option value="{{ $selectedAgent }}" selected>{{ $selectedAgent }}</option>
                        @endif
                    </select>
                    </div>

                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-100">{{ __('Actions') }}</span>
                        <div class="flex items-center gap-2">
                            <span class="flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-xs font-semibold text-indigo-900 shadow-sm dark:border-indigo-500/40 dark:bg-indigo-900/30 dark:text-indigo-100">
                                <i class="bi bi-gear-fill text-base"></i>
                                {{ __('Campaign') }}
                            </span>
                            <div class="relative">
                                <button type="button" id="campaign-action-filter" data-dropdown-toggle="campaign-action-dropdown"
                                    class="btn-primary flex items-center justify-center gap-2 rounded-full px-5 py-2 text-sm">
                                    <i class="bi bi-sliders text-base"></i>
                                    <span>{{ __('Filter') }}</span>
                                    <i class="bi bi-chevron-down text-xs"></i>
                                </button>
                                <div id="campaign-action-dropdown"
                                    class="z-30 hidden w-64 rounded-lg border border-gray-100 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                    <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                                        <li>
                                            <button type="button" class="flex w-full items-center justify-between rounded-md px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" data-campaign-action="start">
                                                <span>{{ __('Start campaign') }}</span>
                                                <i class="bi bi-play-fill text-indigo-600 dark:text-white"></i>
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="flex w-full items-center justify-between rounded-md px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" data-campaign-action="restart">
                                                <span>{{ __('Restart Failed Campaign') }}</span>
                                                <i class="bi bi-arrow-repeat text-indigo-600 dark:text-white"></i>
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="flex w-full items-center justify-between rounded-md px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white" data-campaign-action="stop">
                                                <span>{{ __('Stop campaign') }}</span>
                                                <i class="bi bi-stop-circle text-indigo-600 dark:text-white"></i>
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="sr-only">
                    <button type="button" id="btnStartCampaign" class="btn-primary">
                        {{ __('Start campaign') }}
                    </button>
                    <button type="button" id="btnRestartFailedCampaign" class="btn-default">
                        {{ __('Restart Failed Campaign') }}
                    </button>
                    <button type="button" id="btnStopCampaign" class="btn-danger">
                        {{ __('Stop campaign') }}
                    </button>
                </div>
                <div class="mt-2">
                    <span id="campaignModeBadge" class="inline-flex items-center rounded-full border border-gray-300 bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        {{ __('Mode: All Leads') }}
                    </span>
                </div>
                </div>
                @endcan


                @if (!empty($webrtcError))
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500 dark:bg-amber-800 dark:text-white">
                        {{ $webrtcError }}
                    </div>
                @endif

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

    let callUuid = null;
    let pollHandle = null;
    let callActive = false;
    let conferenceName = null;
    let browserAudioActive = false;
    let webRtcClient = null;
    let browserAudioConnecting = false;
    let browserAudioRetryTimer = null;
    let browserAudioRetryCount = 0;
    let hangupInProgress = false;
    let isMuted = false;
    let callControlsEnabled = false;

    let callConnectedAt = null;
    let timerHandle = null;

    let manualDialLocked = false;
    let campaignSubmission = false;

    const refreshStartButton = () => {
        if (!startCallButton) {
            return;
        }
        startCallButton.disabled = manualDialLocked || callActive;
    };

    const lockManualDial = () => {
        manualDialLocked = true;
        refreshStartButton();
    };

    const unlockManualDial = () => {
        manualDialLocked = false;
        refreshStartButton();
    };

    const showManualDialLocked = () => {
        if (!alertBox) return;
        alertBox.textContent = 'Campaign is running. Stop it to dial manually.';
        alertBox.classList.remove('hidden');
    };

    refreshStartButton();

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

    const setDestination = (value) => {
        syncDisplay(value || '');
    };

    // Allow paste into readonly display
    const sanitizePhone = (value) => (value || '').toString().replace(/[^\d+*#]/g, '');
    const applyPastedValue = (text) => syncDisplay(sanitizePhone(text));

    if (displayInput) {
        displayInput.addEventListener('input', (e) => {
            if (callActive || manualDialLocked) {
                if (manualDialLocked && !callActive) {
                    showManualDialLocked();
                }
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
            if (!callActive && !manualDialLocked) {
                applyPastedValue(text);
            } else if (!callActive && manualDialLocked) {
                showManualDialLocked();
            }
        });

        document.addEventListener('paste', (e) => {
            if (document.activeElement !== displayInput) return;
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text');
            if (!callActive && !manualDialLocked) {
                applyPastedValue(text);
            } else if (!callActive && manualDialLocked) {
                showManualDialLocked();
            }
        });

        displayInput.addEventListener('click', () => displayInput.focus());
    }

    // ===== Live call state =====

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

    const getCallDurationSeconds = () => {
        if (!callConnectedAt) return null;
        return Math.max(0, Math.floor((Date.now() - callConnectedAt) / 1000));
    };

    const startTimer = (initialSeconds = 0) => {
        const baseSeconds = Number.isFinite(Number(initialSeconds)) ? Math.max(0, Number(initialSeconds)) : 0;
        if (timerHandle) {
            if (!callConnectedAt && baseSeconds > 0) {
                callConnectedAt = Date.now() - (baseSeconds * 1000);
            }
            return;
        }
        callConnectedAt = Date.now() - (baseSeconds * 1000);
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

    const updateActionButtons = () => {
        actionButtons.forEach((btn) => {
            const action = btn.dataset.action;
            let disabled = !callControlsEnabled || !callActive || hangupInProgress;
            if (!disabled) {
                if (action === 'mute') {
                    disabled = isMuted;
                } else if (action === 'unmute') {
                    disabled = !isMuted;
                }
            }
            btn.disabled = disabled;
        });
    };

    const setControls = (enabled) => {
        callControlsEnabled = enabled;
        updateActionButtons();
    };

    const isConnectedStatus = (normalized) => (
        normalized === 'in_call' ||
        normalized === 'incall' ||
        normalized === 'in-call' ||
        normalized === 'active' ||
        normalized === 'answered' ||
        normalized === 'connected' ||
        normalized === 'bridged'
    );

    const isTerminalStatus = (normalized) => (
        normalized === 'ended' ||
        normalized === 'completed' ||
        normalized === 'failed'
    );

    const setStatus = (status, sipStatus = null, sipReason = null, durationSeconds = 0) => {
        const normalized = (status || '').toLowerCase();
        const sipCode = sipStatus !== null && sipStatus !== undefined && !Number.isNaN(Number(sipStatus))
            ? Number(sipStatus)
            : null;

        const labelMap = {
            queued: 'Trying',
            trying: 'Trying',
            ringing: 'Ringing',
            in_call: 'In Call',
            incall: 'In Call',
            'in-call': 'In Call',
            active: 'In Call',
            answered: 'In Call',
            connected: 'In Call',
            bridged: 'In Call',
            completed: 'Bye',
            ended: 'Bye',
            failed: 'Bye'
        };

        let label = labelMap[normalized] || 'Ready';
        if (sipCode && sipCode >= 400) {
            label = `Error ${sipCode}${sipReason ? ` ${sipReason}` : ''}`;
        } else if (normalized === 'ringing' && sipCode && sipCode >= 180 && sipCode < 200) {
            label = `Ringing${sipReason ? ` (${sipReason})` : ''}`;
        } else if ((normalized === 'trying' || normalized === 'queued') && sipCode && sipCode < 180) {
            label = `Trying${sipReason ? ` (${sipReason})` : ''}`;
        } else if ((normalized === 'ended' || normalized === 'completed') && (!sipCode || sipCode < 400)) {
            label = 'Bye';
        }

        if (statusEl) {
            statusEl.textContent = label;
            statusEl.classList.remove('bg-amber-100','text-amber-800','dark:bg-amber-500/30','dark:text-amber-100','bg-blue-100','text-blue-800','dark:bg-blue-500/30','dark:text-blue-100','bg-green-100','text-green-800','dark:bg-green-500/30','dark:text-green-100','bg-red-100','text-red-800','dark:bg-red-500/30','dark:text-red-100','bg-gray-100','text-gray-800','dark:bg-gray-700','dark:text-gray-200');
            if (label.startsWith('Trying')) {
                statusEl.classList.add('bg-amber-100','text-amber-800','dark:bg-amber-500/30','dark:text-amber-100');
            } else if (label.startsWith('Ringing')) {
                statusEl.classList.add('bg-blue-100','text-blue-800','dark:bg-blue-500/30','dark:text-blue-100');
            } else if (label === 'In Call') {
                statusEl.classList.add('bg-green-100','text-green-800','dark:bg-green-500/30','dark:text-green-100');
            } else if (label === 'Bye') {
                statusEl.classList.add('bg-gray-100','text-gray-800','dark:bg-gray-700','dark:text-gray-200');
            } else if (label.startsWith('Error')) {
                statusEl.classList.add('bg-red-100','text-red-800','dark:bg-red-500/30','dark:text-red-100');
            }
        }

        if (isConnectedStatus(normalized)) {
            startTimer(durationSeconds);
            if (conferenceName && webRtcClient && !browserAudioActive && !browserAudioConnecting && !hangupInProgress) {
                connectBrowserAudio();
            }
        }

        if (isTerminalStatus(normalized)) {
            stopTimer();
            disconnectBrowserAudio();
            applyMuteState(false);
            if (typeof handleCampaignCallComplete === 'function') {
                handleCampaignCallComplete(normalized);
            }
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
            browserAudioRetryCount = 0;
            if (browserAudioRetryTimer) {
                clearTimeout(browserAudioRetryTimer);
                browserAudioRetryTimer = null;
            }
            updateBrowserAudioStatus('Browser audio connected');
        } catch (error) {
            console.error('Failed to connect browser audio', error);
            browserAudioActive = false;
            browserAudioRetryCount += 1;
            const errorMessage = error && error.message ? String(error.message) : '';
            updateBrowserAudioStatus('Browser audio unavailable', true);
            showError('Unable to connect browser audio.');
            if (errorMessage) {
                console.warn(`[dialer] browser audio join failed: ${errorMessage}`);
            }
            if (callActive && conferenceName && browserAudioRetryCount < 4) {
                const delayMs = 1200 * browserAudioRetryCount;
                browserAudioRetryTimer = setTimeout(() => {
                    browserAudioRetryTimer = null;
                    connectBrowserAudio();
                }, delayMs);
            }
        } finally {
            browserAudioConnecting = false;
        }
    };

    const disconnectBrowserAudio = async () => {
        if (browserAudioRetryTimer) {
            clearTimeout(browserAudioRetryTimer);
            browserAudioRetryTimer = null;
        }
        browserAudioRetryCount = 0;
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

    const applyMuteState = async (muted) => {
        isMuted = muted;
        updateActionButtons();
        if (webRtcClient && typeof webRtcClient.setMuted === 'function') {
            try {
                await webRtcClient.setMuted(muted);
            } catch (error) {
                console.error('Failed to toggle microphone mute', error);
            }
        }
        if (browserAudioActive) {
            updateBrowserAudioStatus(muted ? 'Microphone muted' : 'Browser audio connected');
        }
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
                refreshStartButton();
                stopTimer();
                return;
            }

            const data = await response.json();
            console.log('[pollStatus] status:', data.status, data);
            if (hangupInProgress) return;
            if (data.conferenceName) {
                conferenceName = data.conferenceName;
            }
            setStatus(data.status, data.sipStatus, data.sipReason, data.durationSeconds);

            const currentStatus = (data.status || '').toLowerCase();
            if (currentStatus === 'in_call' || currentStatus === 'ringing' || currentStatus === 'queued' || currentStatus === 'trying' || isConnectedStatus(currentStatus)) {
                callActive = true;
                setControls(true);
            }

            if (isTerminalStatus(currentStatus)) {
                clearInterval(pollHandle);
                callActive = false;
                setControls(false);
                refreshStartButton();
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
            refreshStartButton();
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
            const isMuteAction = action === 'mute' || action === 'unmute';
            if (isMuteAction && !callActive) return;

            if (alertEl) alertEl.classList.add('hidden');

            try {
                if (action === 'hangup') {
                    hangupInProgress = true;
                    setControls(false);
                } else if (isMuteAction) {
                    button.disabled = true;
                }
                const requestOptions = {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                };

                if (action === 'hangup') {
                    const payload = {};
                    const durationSeconds = getCallDurationSeconds();
                    if (durationSeconds !== null) {
                        payload.durationSeconds = durationSeconds;
                    }
                    requestOptions.body = JSON.stringify(payload);
                }

                const response = await fetch(`/admin/dialer/calls/${callUuid}/${action}`, requestOptions);

                if (!response.ok) {
                    let data = {};
                    try { data = await response.json(); } catch (e) {}
                    showError(data.message || `HTTP ${response.status}`);
                    if (action === 'hangup') {
                        hangupInProgress = false;
                        setControls(callActive);
                    } else if (isMuteAction) {
                        updateActionButtons();
                    }
                    return;
                }

                if (action === 'hangup') {
                    setStatus('completed');
                    clearInterval(pollHandle);
                    callActive = false;
                    setControls(false);
                    refreshStartButton();
                    stopTimer();
                    await disconnectBrowserAudio();
                    await applyMuteState(false);
                } else if (isMuteAction) {
                    await applyMuteState(action === 'mute');
                }
            } catch (e) {
                showError('Network error while updating the call.');
                if (action === 'hangup') {
                    hangupInProgress = false;
                    setControls(callActive);
                } else if (isMuteAction) {
                    updateActionButtons();
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

                if (!callActive && !manualDialLocked) {
                    syncDisplay(`${hiddenInput.value || ''}+`);
                    playTone('0');
                } else if (!callActive && manualDialLocked) {
                    showManualDialLocked();
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
            if (manualDialLocked) {
                showManualDialLocked();
                return;
            }
            syncDisplay(`${hiddenInput.value || ''}${value}`);
            playTone(value);
        });
    });

    if (clearButton) {
        clearButton.addEventListener('click', () => {
            if (callActive) return;
            if (manualDialLocked) {
                showManualDialLocked();
                return;
            }
            syncDisplay('');
        });
    }

    if (backspaceButton) {
        backspaceButton.addEventListener('click', () => {
            if (callActive) return;
            if (manualDialLocked) {
                showManualDialLocked();
                return;
            }
            const current = hiddenInput.value || '';
            syncDisplay(current.slice(0, -1));
        });
    }

    // ===== Campaign automation =====
    const campaignSelect = document.getElementById('campaign_id');
    const agentInput = document.getElementById('agent_name');
    const btnStartCampaign = document.getElementById('btnStartCampaign');
    const btnRestartFailedCampaign = document.getElementById('btnRestartFailedCampaign');
    const btnStopCampaign = document.getElementById('btnStopCampaign');
    const campaignActionSelect = document.getElementById('campaign_action_select');
    const campaignModeBadge = document.getElementById('campaignModeBadge');
    const campaignRoutes = {
        start: '{{ route('admin.dialer.campaign.start') }}',
        restartFailed: '{{ route('admin.dialer.campaign.restart_failed') }}',
        stop: '{{ route('admin.dialer.campaign.stop') }}',
        next: '{{ route('admin.dialer.campaign.next') }}',
    };

    const campaignState = {
        running: false,
        currentLeadId: null,
        fetchingNext: false,
        leadScope: 'all',
    };

    const updateCampaignModeBadge = () => {
        if (!campaignModeBadge) return;
        const failedOnly = campaignState.leadScope === 'failed';
        campaignModeBadge.textContent = failedOnly ? 'Mode: Failed Only' : 'Mode: All Leads';
        campaignModeBadge.classList.remove(
            'border-gray-300','bg-gray-100','text-gray-700',
            'dark:border-gray-700','dark:bg-gray-800','dark:text-gray-200',
            'border-amber-300','bg-amber-100','text-amber-800',
            'dark:border-amber-700','dark:bg-amber-900/40','dark:text-amber-200'
        );
        if (failedOnly) {
            campaignModeBadge.classList.add(
                'border-amber-300','bg-amber-100','text-amber-800',
                'dark:border-amber-700','dark:bg-amber-900/40','dark:text-amber-200'
            );
        } else {
            campaignModeBadge.classList.add(
                'border-gray-300','bg-gray-100','text-gray-700',
                'dark:border-gray-700','dark:bg-gray-800','dark:text-gray-200'
            );
        }
    };
    updateCampaignModeBadge();

    const campaignAlert = (message = '') => {
        if (!alertBox) return;
        if (!message) {
            alertBox.classList.add('hidden');
            alertBox.textContent = '';
            return;
        }
        alertBox.textContent = message;
        alertBox.classList.remove('hidden');
    };

    const buildUrl = (base, params = {}) => {
        const url = new URL(base, window.location.origin);
        Object.entries(params).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                url.searchParams.set(key, value);
            }
        });
        return url.toString();
    };

    const campaignRequest = async (url, { method = 'GET', body = null, params = null } = {}) => {
        const target = params ? buildUrl(url, params) : url;
        const headers = {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        };
        const options = { method, headers };
        if (body) {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }

        const response = await fetch(target, options);
        let data = {};
        try { data = await response.json(); } catch (error) {}

        if (!response.ok || data.ok === false) {
            throw new Error(data.message || `HTTP ${response.status}`);
        }

        return data;
    };

    const submitDialerForm = () => {
        if (!form) return;
        campaignSubmission = true;
        try {
            form.requestSubmit();
        } finally {
            campaignSubmission = false;
        }
    };

    const dialCampaignLead = (lead) => {
        if (!lead || !lead.phone) {
            campaignAlert('Campaign started but no leads were returned.');
            campaignState.running = false;
            campaignState.currentLeadId = null;
            campaignState.leadScope = 'all';
            updateCampaignModeBadge();
            unlockManualDial();
            return;
        }

        campaignState.currentLeadId = lead.id;
        campaignState.running = true;
        lockManualDial();
        campaignAlert('');
        setDestination(lead.phone);
        submitDialerForm();
    };

    const fetchNextLead = async ({ lastLeadId, lastLeadStatus } = {}) => {
        if (!campaignState.running || campaignState.fetchingNext) {
            return;
        }

        campaignState.fetchingNext = true;
        try {
            const data = await campaignRequest(campaignRoutes.next, {
                params: {
                    last_lead_id: lastLeadId,
                    last_lead_status: lastLeadStatus,
                    lead_scope: campaignState.leadScope || 'all'
                }
            });

            if (data.next?.phone) {
                dialCampaignLead(data.next);
            } else {
                campaignAlert('Campaign completed. No more leads available.');
                campaignState.running = false;
                campaignState.currentLeadId = null;
                campaignState.leadScope = 'all';
                updateCampaignModeBadge();
                setDestination('');
                unlockManualDial();
            }
        } catch (error) {
            campaignAlert(error.message || 'Unable to fetch next lead.');
            campaignState.running = false;
            campaignState.currentLeadId = null;
            campaignState.leadScope = 'all';
            updateCampaignModeBadge();
            unlockManualDial();
        } finally {
            campaignState.fetchingNext = false;
        }
    };

    const startCampaignFlow = async () => {
        if (!campaignSelect || !agentInput) return;
        if (campaignState.running) {
            campaignAlert('Campaign is already running.');
            return;
        }
        const campaignId = campaignSelect.value;
        const agent = (agentInput.value || '').trim();

        if (!campaignId || !agent) {
            campaignAlert('Select campaign and enter agent name.');
            return;
        }

        campaignState.running = true;
        campaignState.leadScope = 'all';
        updateCampaignModeBadge();
        lockManualDial();

        try {
            const data = await campaignRequest(campaignRoutes.start, {
                method: 'POST',
                body: {
                    campaign_id: campaignId,
                    agent: agent
                }
            });

            if (data.next?.phone) {
                dialCampaignLead(data.next);
            } else {
                campaignAlert('Campaign started but no leads were returned.');
                campaignState.running = false;
                campaignState.currentLeadId = null;
                unlockManualDial();
            }
        } catch (error) {
            campaignAlert(error.message || 'Unable to start campaign.');
            campaignState.running = false;
            campaignState.leadScope = 'all';
            updateCampaignModeBadge();
            unlockManualDial();
        }
    };

    const restartFailedCampaignFlow = async () => {
        if (!campaignSelect || !agentInput) return;
        if (campaignState.running) {
            campaignAlert('Campaign is already running.');
            return;
        }

        const campaignId = campaignSelect.value;
        const agent = (agentInput.value || '').trim();
        if (!campaignId || !agent) {
            campaignAlert('Select campaign and enter agent name.');
            return;
        }

        campaignState.running = true;
        campaignState.leadScope = 'failed';
        updateCampaignModeBadge();
        lockManualDial();

        try {
            const data = await campaignRequest(campaignRoutes.restartFailed, {
                method: 'POST',
                body: {
                    campaign_id: campaignId,
                    agent: agent
                }
            });

            if (data.next?.phone) {
                dialCampaignLead(data.next);
            } else {
                campaignAlert('No failed leads available for this campaign.');
                campaignState.running = false;
                campaignState.currentLeadId = null;
                campaignState.leadScope = 'all';
                updateCampaignModeBadge();
                unlockManualDial();
            }
        } catch (error) {
            campaignAlert(error.message || 'Unable to restart failed campaign.');
            campaignState.running = false;
            campaignState.leadScope = 'all';
            updateCampaignModeBadge();
            unlockManualDial();
        }
    };

    const stopCampaignFlow = async () => {
        try {
            await campaignRequest(campaignRoutes.stop, { method: 'POST' });
            campaignState.running = false;
            campaignState.currentLeadId = null;
            campaignState.leadScope = 'all';
            updateCampaignModeBadge();
            campaignAlert('');
            setDestination('');
            unlockManualDial();
        } catch (error) {
            campaignAlert(error.message || 'Unable to stop campaign.');
        }
    };

    const handleCampaignCallComplete = (status) => {
        if (!campaignState.running || !campaignState.currentLeadId) {
            return;
        }

        const finalStatus = status === 'completed' ? 'called' : 'failed';
        const finishedLeadId = campaignState.currentLeadId;
        campaignState.currentLeadId = null;
        fetchNextLead({
            lastLeadId: finishedLeadId,
            lastLeadStatus: finalStatus
        });
    };

    document.querySelectorAll('[data-campaign-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const action = button.getAttribute('data-campaign-action');
            const handlerMap = {
                start: btnStartCampaign,
                restart: btnRestartFailedCampaign,
                stop: btnStopCampaign,
            };
            handlerMap[action]?.click();
        });
    });

    btnStartCampaign?.addEventListener('click', startCampaignFlow);
    btnRestartFailedCampaign?.addEventListener('click', restartFailedCampaignFlow);
    btnStopCampaign?.addEventListener('click', stopCampaignFlow);

    // ===== Start call (inline; no popup) =====
    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (manualDialLocked && !campaignSubmission) {
            showManualDialLocked();
            return;
        }

        // hide alert
        alertBox.classList.add('hidden');
        alertBox.textContent = '';

        await disconnectBrowserAudio();
        conferenceName = null;
        callUuid = null;

        // show live session
        if (liveSession) liveSession.classList.remove('hidden');
        if (callIdBadge) callIdBadge.classList.add('hidden');
        if (alertEl) alertEl.classList.add('hidden');

        setStatus('trying');
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
                refreshStartButton();
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
                await applyMuteState(false);
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
                refreshStartButton();
                setStatus('ended');
                await disconnectBrowserAudio();
            }
        } catch (error) {
            alertBox.textContent = 'Network error while queuing the call.';
            alertBox.classList.remove('hidden');
            refreshStartButton();
            setStatus('ended');
            showError('Network error while queuing the call.');
            await disconnectBrowserAudio();
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
