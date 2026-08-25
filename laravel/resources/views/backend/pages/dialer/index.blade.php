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
    {{ __('Dialer') }} | {{ config('app.name') }}
@endsection

@section('admin-content')
<div class="connectpro-dialer min-h-full bg-[#06111f] p-3 text-white sm:p-6">
    <div class="mx-auto max-w-[1220px] space-y-4">

        <div class="flex items-center justify-between lg:hidden">
            <button type="button" @click.stop="sidebarToggle = true" class="flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-lg dark:border-[#2a4055] dark:bg-[#091827] dark:text-slate-200" aria-label="{{ __('Open navigation') }}">
                <i class="bi bi-list text-2xl"></i>
            </button>
            <div class="flex min-w-0 items-center gap-2 text-sm text-slate-300">
                <span class="h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>
                <span class="truncate">{{ __('ConnectPro mobile dialer') }}</span>
            </div>
        </div>

        @if (!empty($webrtcError))
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">{{ $webrtcError }}</div>
        @endif

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ __('Keypad') }}</h1>
                <p class="mt-1 flex items-center gap-2 text-sm text-slate-300"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>{{ __('Ready') }}</p>
            </div>
            <div class="connectpro-sip-badge flex max-w-full items-center gap-3 rounded-2xl border border-[#2a4055] bg-[#091827] px-3 py-2 shadow-lg shadow-black/10 sm:px-4" title="{{ __('Web phone account') }}: {{ $webrtcConfig['username'] ?? '—' }}@{{ $webrtcConfig['domain'] ?? '—' }}">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600/15 text-blue-400 ring-1 ring-inset ring-blue-500/25">
                    <i class="bi bi-headset text-xl"></i>
                </span>
                <span class="min-w-0">
                    <span class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                        {{ __('Web phone') }}
                        <span id="web-phone-state-dot" class="h-1.5 w-1.5 rounded-full bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.65)]"></span>
                    </span>
                    <span class="mt-0.5 block truncate text-sm font-semibold text-slate-100">
                        {{ __('Extension') }} {{ $webrtcConfig['username'] ?? '—' }} · <span id="web-phone-state">{{ __('Connecting') }}</span>
                    </span>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[380px_minmax(0,1fr)]">
            <section class="rounded-2xl border border-[#2a4055] bg-[#091827] p-3 shadow-2xl sm:p-5">
                <div class="mb-5 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-white">{{ __('Keypad') }}</h2>
                    </div>
                    <div id="call-status" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-[#365068] text-slate-300"><i class="bi bi-arrow-clockwise"></i></div>
                </div>

                <form id="dialer-form" method="POST" action="{{ route('admin.dialer.dial') }}">
                    @csrf
                    <div class="relative rounded-xl border border-[#365068] bg-[#0b1b2c] px-11 py-3 sm:px-12 sm:py-3.5">
                        <i class="bi bi-phone absolute left-4 top-1/2 -translate-y-1/2 text-xl text-slate-300"></i>
                        <input type="text" id="dialpad-display" placeholder="{{ __('Enter number or name') }}" inputmode="tel" autocomplete="tel" class="w-full border-0 bg-transparent p-0 text-left text-lg font-normal tracking-normal text-white outline-none ring-0 placeholder:text-slate-400 focus:border-0 focus:ring-0">
                        <input type="hidden" name="destination" id="dialpad-input" required>
                        <button type="button" id="dialpad-clear" class="sr-only" title="{{ __('Clear') }}">{{ __('Clear') }}</button>
                        <button type="button" id="dialpad-backspace" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-2 text-xl text-slate-300 hover:text-white" title="{{ __('Delete') }}"><i class="bi bi-x-lg"></i></button>
                    </div>

                    <div class="dialpad-grid mt-5" aria-label="Dial pad">
                        @php($keys = [['1',''],['2','ABC'],['3','DEF'],['4','GHI'],['5','JKL'],['6','MNO'],['7','PQRS'],['8','TUV'],['9','WXYZ'],['*',''],['0','+'],['#','']])
                        @foreach($keys as [$k,$sub])
                            <button type="button" class="dialpad-key" data-value="{{ $k }}">
                                <div class="leading-none">{{ $k }}</div><div class="mt-1 text-[10px]">{{ $sub ?: ' ' }}</div>
                            </button>
                        @endforeach
                    </div>

                    <div class="connectpro-call-actions mt-5 grid grid-cols-2 gap-3">
                        <button type="submit" class="flex items-center justify-center gap-2 rounded-xl bg-emerald-600 py-3.5 font-semibold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-60"><i class="bi bi-telephone-fill text-xl"></i><span>{{ __('Call') }}</span></button>
                        <button type="button" class="flex items-center justify-center gap-2 rounded-xl bg-red-500 py-3.5 font-semibold text-white shadow-lg shadow-red-600/20 transition hover:bg-red-400 disabled:cursor-not-allowed disabled:opacity-50" data-action="hangup" disabled><i class="bi bi-telephone-x-fill text-xl"></i><span>{{ __('Hangup') }}</span></button>
                    </div>
                </form>

                <div id="dialer-alert" class="mt-4 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"></div>

                <div id="live-call-session" class="mt-4 border-t border-[#2a4055] pt-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Call controls') }}</p>
                            <div class="hidden mt-1 font-mono text-lg text-white" id="call-timer-badge"><span id="call-timer">00:00</span></div>
                        </div>
                        <div class="hidden text-[10px] text-gray-400" id="call-id-badge"></div>
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-2">
                        <button type="button" class="connectpro-control" data-action="mute" disabled><i class="bi bi-mic-mute text-lg"></i><span>{{ __('Mute') }}</span></button>
                        <button type="button" class="connectpro-control" data-action="unmute" disabled><i class="bi bi-mic text-lg"></i><span>{{ __('Unmute') }}</span></button>
                        <button type="button" class="connectpro-control" disabled><i class="bi bi-grid-3x3-gap text-lg"></i><span>{{ __('Keypad') }}</span></button>
                    </div>
                    <div id="call-alert" class="mt-3 hidden rounded-xl bg-red-50 px-3 py-2 text-xs text-red-700 dark:bg-red-500/10 dark:text-red-300"></div>
                    <div class="mt-3 hidden text-xs text-gray-400" id="browser-audio-status"></div>
                    <audio id="dialer-audio" class="hidden" autoplay playsinline></audio>
                </div>
            </section>

            <aside id="contact-workspace-panel" class="min-w-0 rounded-2xl border border-[#2a4055] bg-[#091827] p-3 shadow-2xl sm:p-5">
                <div class="relative mb-5">
                    <label for="contact-search" class="mb-2 block text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Contacts') }}</label>
                    @if (! $contactPermissions['view'])
                        <p class="mb-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-xs text-amber-300">{{ __('Your role does not have permission to view the global contact book.') }}</p>
                    @endif
                    <div class="grid gap-2 sm:grid-cols-[1fr_auto]">
                      <div class="relative">
                        <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input id="contact-search" type="search" autocomplete="off" @disabled(! $contactPermissions['view']) placeholder="{{ __('Search name, company, phone…') }}" class="w-full rounded-xl border border-[#365068] bg-[#0b1b2c] py-2.5 pl-10 pr-3 text-sm text-white outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 disabled:opacity-50">
                      </div>
                      <select id="contact-label-filter" @disabled(! $contactPermissions['view']) class="rounded-xl border border-[#365068] bg-[#0b1b2c] px-3 py-2.5 text-sm text-white outline-none focus:border-blue-500 disabled:opacity-50">
                          <option value="">{{ __('All labels') }}</option>
                      </select>
                    </div>
                    <div id="contact-search-results" class="absolute inset-x-0 top-full z-20 mt-2 hidden max-h-64 overflow-y-auto rounded-xl border border-[#365068] bg-[#0b1b2c] p-2 shadow-2xl"></div>
                </div>

                <div class="border-b border-[#2a4055] pb-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="flex items-center gap-2 text-xl font-semibold text-white"><i class="bi bi-people-fill text-blue-500"></i>{{ __('Customer') }}</h2>
                        <button id="contact-flag-toggle" type="button" disabled class="flex h-9 w-9 items-center justify-center rounded-full border border-[#365068] text-slate-400 transition hover:border-amber-400 hover:text-amber-400 disabled:cursor-not-allowed disabled:opacity-40" title="{{ __('Flag contact') }}" aria-label="{{ __('Flag contact') }}"><i class="bi bi-flag"></i></button>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-3 sm:flex-nowrap sm:gap-4">
                        <div id="customer-avatar" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-blue-600 text-lg font-bold text-white">?</div>
                        <div class="min-w-0 flex-1">
                            <p id="customer-name" class="truncate text-lg font-semibold text-white">{{ __('No customer selected') }}</p>
                            <p id="customer-company" class="truncate text-sm text-blue-400">{{ __('Call workspace') }}</p>
                        </div>
                        <p id="customer-phone" class="w-full break-all pl-[68px] text-sm text-slate-200 sm:w-auto sm:shrink-0 sm:pl-0"><i class="bi bi-telephone mr-2"></i>—</p>
                    </div>
                    <div id="contact-labels" class="mt-4 flex flex-wrap gap-2"></div>
                    <div class="mt-3 flex gap-2">
                        <input id="contact-label-input" type="text" maxlength="30" disabled placeholder="{{ __('Add label') }}" class="min-w-0 flex-1 rounded-lg border border-[#365068] bg-[#0b1b2c] px-3 py-2 text-xs text-white outline-none placeholder:text-slate-400 focus:border-blue-500 disabled:opacity-50">
                        <button id="contact-label-add" type="button" disabled class="rounded-lg border border-blue-500/50 px-3 py-2 text-xs font-semibold text-blue-400 hover:bg-blue-500/10 disabled:opacity-40">{{ __('Add') }}</button>
                    </div>
                </div>

                <div class="contact-tabs -mx-3 flex overflow-x-auto border-b border-[#2a4055] px-3 sm:-mx-5 sm:px-5" role="tablist" aria-label="{{ __('Contact workspace') }}">
                    <button type="button" data-contact-tab="notes" class="contact-tab-active flex shrink-0 items-center gap-2 border-b-2 px-3 py-3 text-xs font-semibold sm:text-sm" role="tab" aria-selected="true"><i class="bi bi-file-earmark-text"></i>{{ __('Notes & Comments') }}</button>
                    <button type="button" data-contact-tab="activity" class="flex shrink-0 items-center gap-2 border-b-2 border-transparent px-3 py-3 text-xs font-semibold text-slate-400 sm:text-sm" role="tab" aria-selected="false"><i class="bi bi-activity"></i>{{ __('Activity Log') }}</button>
                    <button type="button" data-contact-tab="history" class="flex shrink-0 items-center gap-2 border-b-2 border-transparent px-3 py-3 text-xs font-semibold text-slate-400 sm:text-sm" role="tab" aria-selected="false"><i class="bi bi-clock-history"></i>{{ __('Call History') }}</button>
                    <button type="button" data-contact-tab="info" class="flex shrink-0 items-center gap-2 border-b-2 border-transparent px-3 py-3 text-xs font-semibold text-slate-400 sm:text-sm" role="tab" aria-selected="false"><i class="bi bi-person"></i>{{ __('Contact Info') }}</button>
                </div>

                <div data-contact-tab-panel="notes" class="pt-5">
                    <div class="flex items-center gap-2 text-lg font-semibold text-white"><i class="bi bi-chat-left-text text-slate-200"></i>{{ __('Notes & Comments') }}</div>
                </div>

                <div data-contact-tab-panel="info" class="mt-4 hidden rounded-xl border border-[#263b50] bg-[#102338] p-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <input id="contact-name-input" type="text" maxlength="255" placeholder="{{ __('Contact name') }}" class="rounded-lg border border-[#365068] bg-[#091827] px-3 py-2.5 text-sm text-white outline-none placeholder:text-slate-400 focus:border-blue-500">
                        <input id="contact-company-input" type="text" maxlength="255" placeholder="{{ __('Company') }}" class="rounded-lg border border-[#365068] bg-[#091827] px-3 py-2.5 text-sm text-white outline-none placeholder:text-slate-400 focus:border-blue-500">
                        <input id="contact-phone-input" type="tel" maxlength="40" placeholder="{{ __('Phone number') }}" class="rounded-lg border border-[#365068] bg-[#091827] px-3 py-2.5 text-sm text-white outline-none placeholder:text-slate-400 focus:border-blue-500">
                        <input id="contact-email-input" type="email" maxlength="255" placeholder="{{ __('Email') }}" class="rounded-lg border border-[#365068] bg-[#091827] px-3 py-2.5 text-sm text-white outline-none placeholder:text-slate-400 focus:border-blue-500">
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <span id="contact-feedback" class="text-xs text-slate-400"></span>
                        <button id="contact-save" type="button" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-500">{{ __('Save contact') }}</button>
                    </div>
                </div>

                <div data-contact-tab-panel="activity" class="mt-4 hidden rounded-xl border border-[#263b50] bg-[#102338]">
                    <div class="flex items-center justify-between border-b border-[#263b50] px-4 py-3">
                        <div>
                            <h3 class="font-semibold text-white">{{ __('Activity Log') }}</h3>
                            <p class="mt-1 text-xs text-gray-400">{{ __('Timestamped contact, label, flag, and comment changes') }}</p>
                        </div>
                        <button id="contact-activity-refresh" type="button" disabled class="flex h-9 w-9 items-center justify-center rounded-full border border-[#365068] text-slate-400 hover:border-blue-500 hover:text-blue-400 disabled:opacity-40" title="{{ __('Refresh activity') }}"><i class="bi bi-arrow-clockwise"></i></button>
                    </div>
                    <div id="contact-activity" class="max-h-[32rem] space-y-3 overflow-y-auto p-4"><p class="text-sm text-slate-400">{{ __('Select a saved contact to view activity.') }}</p></div>
                </div>

                <div data-contact-tab-panel="history" class="mt-4 hidden rounded-xl border border-[#263b50] bg-[#102338]">
                    <div class="flex items-center justify-between border-b border-[#263b50] px-4 py-3">
                        <div>
                            <h3 class="font-semibold text-white">{{ __('Call History') }}</h3>
                            <p class="mt-1 text-xs text-gray-400">{{ __('All inbound and outbound calls with this contact') }}</p>
                        </div>
                        <button id="contact-call-history-refresh" type="button" disabled class="flex h-9 w-9 items-center justify-center rounded-full border border-[#365068] text-slate-400 hover:border-blue-500 hover:text-blue-400 disabled:opacity-40" title="{{ __('Refresh call history') }}"><i class="bi bi-arrow-clockwise"></i></button>
                    </div>
                    <div id="contact-call-history" class="max-h-[32rem] space-y-3 overflow-y-auto p-4"><p class="text-sm text-slate-400">{{ __('Select a saved contact to view call history.') }}</p></div>
                </div>

                <div data-contact-tab-panel="notes" class="mt-4 rounded-xl border border-[#263b50] bg-[#102338]">
                    <div class="border-b border-[#263b50] px-4 py-3">
                        <h3 class="font-semibold text-white">{{ __('Contact comments') }}</h3>
                        <p class="mt-1 text-xs text-gray-400">{{ __('Shared history for this contact across calls') }}</p>
                    </div>
                    <div id="contact-comments" class="max-h-72 space-y-2 overflow-y-auto p-4"><p class="text-sm text-slate-400">{{ __('Save or select a contact to view comments.') }}</p></div>
                    <div class="border-t border-[#263b50] p-3">
                        <div class="flex items-end gap-2">
                            <textarea id="contact-comment-input" rows="2" maxlength="2000" disabled placeholder="{{ __('Add a comment…') }}" class="min-w-0 flex-1 resize-none rounded-xl border border-[#365068] bg-[#091827] px-3 py-2.5 text-sm text-white outline-none placeholder:text-slate-400 focus:border-blue-500 disabled:opacity-50"></textarea>
                            <button id="contact-comment-add" type="button" disabled class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white hover:bg-blue-500 disabled:opacity-40" title="{{ __('Add comment') }}"><i class="bi bi-send-fill"></i></button>
                        </div>
                    </div>
                </div>

            </aside>
        </div>

        <div id="incoming-call-banner" class="fixed inset-0 z-[100] hidden bg-black/60 p-4 backdrop-blur-sm">
            <div class="flex min-h-full items-center justify-center">
                <div class="w-full max-w-md rounded-3xl border border-gray-200 bg-white p-8 text-center shadow-2xl dark:border-gray-700 dark:bg-[#111827]">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600 dark:text-blue-400">{{ __('Incoming call') }}</p>
                    <div class="relative mx-auto mt-6 flex h-24 w-24 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                        <span class="absolute inset-0 animate-ping rounded-full bg-blue-500/15"></span><i class="bi bi-telephone-inbound relative text-4xl"></i>
                    </div>
                    <h3 id="incoming-caller" class="mt-5 text-2xl font-bold text-gray-900 dark:text-white">—</h3>
                    <p id="incoming-did" class="mt-1 text-sm text-gray-400"></p>
                    <div class="mt-7 grid grid-cols-2 gap-3">
                        <button type="button" id="incoming-accept" class="rounded-2xl bg-emerald-600 py-3.5 font-semibold text-white shadow-lg shadow-emerald-600/20 hover:bg-emerald-500"><i class="bi bi-telephone-fill mr-2"></i>{{ __('Accept') }}</button>
                        <button type="button" id="incoming-decline" class="rounded-2xl bg-red-600 py-3.5 font-semibold text-white shadow-lg shadow-red-600/20 hover:bg-red-500"><i class="bi bi-telephone-x-fill mr-2"></i>{{ __('Decline') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="dialer-webrtc-config" data-config='@json($webrtcConfig)' class="hidden" aria-hidden="true"></div>
        <div id="dialer-inbound-socket" data-config='@json($inboundSocket ?? [])' class="hidden" aria-hidden="true"></div>
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
    const actionButtons = document.querySelectorAll('[data-action]');
    const callIdBadge = document.getElementById('call-id-badge');
    const callTimerBadge = document.getElementById('call-timer-badge');
    const callTimerEl = document.getElementById('call-timer');
    const browserAudioStatus = document.getElementById('browser-audio-status');
    const webrtcConfigEl = document.getElementById('dialer-webrtc-config');
    const remoteAudioEl = document.getElementById('dialer-audio');
    const webPhoneStateEl = document.getElementById('web-phone-state');
    const webPhoneStateDotEl = document.getElementById('web-phone-state-dot');
    const customerPhoneEl = document.getElementById('customer-phone');
    const customerNameEl = document.getElementById('customer-name');
    const customerCompanyEl = document.getElementById('customer-company');
    const customerAvatarEl = document.getElementById('customer-avatar');
    const contactSearchEl = document.getElementById('contact-search');
    const contactSearchResultsEl = document.getElementById('contact-search-results');
    const contactLabelFilterEl = document.getElementById('contact-label-filter');
    const contactFlagBtn = document.getElementById('contact-flag-toggle');
    const contactLabelsEl = document.getElementById('contact-labels');
    const contactLabelInput = document.getElementById('contact-label-input');
    const contactLabelAddBtn = document.getElementById('contact-label-add');
    const contactNameInput = document.getElementById('contact-name-input');
    const contactCompanyInput = document.getElementById('contact-company-input');
    const contactPhoneInput = document.getElementById('contact-phone-input');
    const contactEmailInput = document.getElementById('contact-email-input');
    const contactSaveBtn = document.getElementById('contact-save');
    const contactFeedbackEl = document.getElementById('contact-feedback');
    const contactCommentsEl = document.getElementById('contact-comments');
    const contactCommentInput = document.getElementById('contact-comment-input');
    const contactCommentAddBtn = document.getElementById('contact-comment-add');
    const contactTabButtons = document.querySelectorAll('[data-contact-tab]');
    const contactTabPanels = document.querySelectorAll('[data-contact-tab-panel]');
    const contactActivityEl = document.getElementById('contact-activity');
    const contactActivityRefreshBtn = document.getElementById('contact-activity-refresh');
    const contactCallHistoryEl = document.getElementById('contact-call-history');
    const contactCallHistoryRefreshBtn = document.getElementById('contact-call-history-refresh');

    if (!form) return;

    const csrfToken = form.querySelector('input[name="_token"]').value;
    const startCallButton = form.querySelector('button[type="submit"]');
    const contactsUrl = @json(route('admin.dialer.contacts.index'));
    const contactPermissions = @json($contactPermissions);

    let activeContact = null;
    let contactSearchTimer = null;
    let dialContactLookupTimer = null;
    let lastContactLookupPhone = '';
    let activeContactTab = 'notes';
    let availableContactLabels = [];

    const normalizeContactPhone = (value = '') => String(value).replace(/\D+/g, '');
    const escapeContactText = (value = '') => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const contactInitials = (name = '') => {
        const parts = String(name).trim().split(/\s+/).filter(Boolean);
        return (parts.slice(0, 2).map((part) => part[0]).join('') || '?').toUpperCase();
    };

    const setContactFeedback = (message = '', error = false) => {
        if (!contactFeedbackEl) return;
        contactFeedbackEl.textContent = message;
        contactFeedbackEl.classList.toggle('text-red-400', error);
        contactFeedbackEl.classList.toggle('text-slate-400', !error);
    };

    const renderContactComments = (comments = []) => {
        if (!contactCommentsEl) return;
        if (!comments.length) {
            contactCommentsEl.innerHTML = `<p class="text-sm text-slate-400">${activeContact ? '{{ __('No comments yet.') }}' : '{{ __('Save or select a contact to view comments.') }}'}</p>`;
            return;
        }
        contactCommentsEl.innerHTML = comments.map((comment) => {
            const author = comment.user?.external_name || comment.user?.email || '{{ __('User') }}';
            const timestamp = comment.created_at ? new Date(comment.created_at).toLocaleString() : '';
            return `<article class="rounded-xl border border-[#263b50] bg-[#091827] px-3 py-2.5">
                <div class="flex items-center justify-between gap-3 text-xs">
                    <strong class="truncate text-slate-200">${escapeContactText(author)}</strong>
                    <time class="shrink-0 text-slate-500">${escapeContactText(timestamp)}</time>
                </div>
                <p class="mt-1 whitespace-pre-wrap break-words text-sm leading-5 text-slate-300">${escapeContactText(comment.body)}</p>
            </article>`;
        }).join('');
    };

    const renderContactLabels = () => {
        if (!contactLabelsEl) return;
        const labels = activeContact?.labels || [];
        contactLabelsEl.innerHTML = labels.map((label) => `<button type="button" data-contact-label="${escapeContactText(label)}" ${contactPermissions.labels ? '' : 'disabled'} class="inline-flex items-center gap-1.5 rounded-full border border-blue-500/40 bg-blue-500/10 px-2.5 py-1 text-xs font-semibold text-blue-300 disabled:cursor-default" title="${contactPermissions.labels ? '{{ __('Remove label') }}' : '{{ __('Label') }}'}">${escapeContactText(label)} ${contactPermissions.labels ? '<i class="bi bi-x"></i>' : ''}</button>`).join('');
        contactLabelsEl.querySelectorAll('[data-contact-label]').forEach((button) => {
            button.addEventListener('click', () => updateActiveContact({
                labels: labels.filter((label) => label !== button.dataset.contactLabel)
            }));
        });
    };

    const renderContact = (contact, fallbackPhone = '') => {
        activeContact = contact || null;
        const phone = contact?.phone || fallbackPhone || '';
        if (customerNameEl) customerNameEl.textContent = contact?.name || '{{ __('Unknown caller') }}';
        if (customerCompanyEl) customerCompanyEl.textContent = contact?.company || (contact ? '{{ __('Contact') }}' : '{{ __('Not saved as contact') }}');
        if (customerPhoneEl) customerPhoneEl.innerHTML = `<i class="bi bi-telephone mr-2"></i>${escapeContactText(phone || '—')}`;
        if (customerAvatarEl) customerAvatarEl.textContent = contactInitials(contact?.name || '');
        if (contactNameInput) contactNameInput.value = contact?.name || '';
        if (contactCompanyInput) contactCompanyInput.value = contact?.company || '';
        if (contactPhoneInput) contactPhoneInput.value = phone;
        if (contactEmailInput) contactEmailInput.value = contact?.email || '';
        if (contactFlagBtn) {
            contactFlagBtn.disabled = !contact || !contactPermissions.edit;
            contactFlagBtn.classList.toggle('border-amber-400', Boolean(contact?.is_flagged));
            contactFlagBtn.classList.toggle('bg-amber-400/10', Boolean(contact?.is_flagged));
            contactFlagBtn.classList.toggle('text-amber-400', Boolean(contact?.is_flagged));
            const icon = contactFlagBtn.querySelector('i');
            if (icon) icon.className = contact?.is_flagged ? 'bi bi-flag-fill' : 'bi bi-flag';
        }
        if (contactLabelInput) contactLabelInput.disabled = !contact || !contactPermissions.labels;
        if (contactLabelAddBtn) contactLabelAddBtn.disabled = !contact || !contactPermissions.labels;
        if (contactCommentInput) contactCommentInput.disabled = !contact || !contactPermissions.comment;
        if (contactCommentAddBtn) contactCommentAddBtn.disabled = !contact || !contactPermissions.comment;
        if (contactActivityRefreshBtn) contactActivityRefreshBtn.disabled = !contact || !contactPermissions.view;
        if (contactCallHistoryRefreshBtn) contactCallHistoryRefreshBtn.disabled = !contact || !contactPermissions.view;
        if (contactSaveBtn) contactSaveBtn.disabled = contact ? !contactPermissions.edit : !contactPermissions.create;
        if (contactSaveBtn) contactSaveBtn.textContent = contact ? '{{ __('Update contact') }}' : '{{ __('Save contact') }}';
        renderContactLabels();
        renderContactComments(contact?.comments || []);
        if (activeContactTab === 'activity') loadContactActivity();
        if (activeContactTab === 'history') loadContactCallHistory();
    };

    const contactRequest = async (url, options = {}) => {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                ...(options.headers || {})
            }
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const validationMessage = data.errors ? Object.values(data.errors).flat()[0] : null;
            throw new Error(validationMessage || data.message || `HTTP ${response.status}`);
        }
        return data;
    };

    const updateContactLabelOptions = (labels = []) => {
        availableContactLabels = labels;
        if (!contactLabelFilterEl) return;
        const selected = contactLabelFilterEl.value;
        contactLabelFilterEl.innerHTML = `<option value="">{{ __('All labels') }}</option>${labels.map((label) => `<option value="${escapeContactText(label)}">${escapeContactText(label)}</option>`).join('')}`;
        contactLabelFilterEl.value = labels.includes(selected) ? selected : '';
    };

    const refreshGlobalContactLabels = async () => {
        if (!contactPermissions.view) return;
        try {
            const data = await contactRequest(contactsUrl);
            updateContactLabelOptions(data.labels || []);
        } catch (error) {
            // A contact save already succeeded at this point. Keep the workspace
            // usable and let the next search/load retry the filter refresh.
        }
    };

    const renderContactActivity = (activity = []) => {
        if (!contactActivityEl) return;
        if (!activeContact) {
            contactActivityEl.innerHTML = '<p class="text-sm text-slate-400">{{ __('Select a saved contact to view activity.') }}</p>';
            return;
        }
        if (!activity.length) {
            contactActivityEl.innerHTML = '<p class="text-sm text-slate-400">{{ __('No contact changes recorded yet.') }}</p>';
            return;
        }
        contactActivityEl.innerHTML = activity.map((item) => {
            const timestamp = item.created_at ? new Date(item.created_at).toLocaleString() : '';
            const actor = item.user?.external_name || item.user?.email || '{{ __('Deleted user') }}';
            const action = String(item.action || '').replaceAll('_', ' ');
            const labelAction = String(item.action || '').startsWith('label_');
            const commentAction = String(item.action || '').startsWith('comment_');
            const icon = labelAction ? 'bi-tag' : (commentAction ? 'bi-chat-left-text' : (String(item.action || '').startsWith('flag_') ? 'bi-flag' : 'bi-person-check'));
            const palette = labelAction
                ? { border: 'border-amber-500/50', dot: 'bg-amber-500', text: 'text-amber-400' }
                : (commentAction
                    ? { border: 'border-violet-500/50', dot: 'bg-violet-500', text: 'text-violet-400' }
                    : { border: 'border-blue-500/50', dot: 'bg-blue-500', text: 'text-blue-400' });
            return `<article class="relative border-l-2 ${palette.border} pl-4">
                <span class="absolute -left-[7px] top-1 flex h-3 w-3 rounded-full ${palette.dot}"></span>
                <div class="rounded-xl border border-[#263b50] bg-[#091827] p-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <strong class="flex items-center gap-2 text-sm capitalize text-slate-100"><i class="bi ${icon} ${palette.text}"></i>${escapeContactText(action)}</strong>
                            <p class="mt-1 text-xs text-slate-400">{{ __('by') }} ${escapeContactText(actor)}</p>
                        </div>
                        <time class="text-[11px] text-slate-500">${escapeContactText(timestamp)}</time>
                    </div>
                    <p class="mt-2 text-xs leading-5 text-slate-300">${escapeContactText(item.description || '')}</p>
                </div>
            </article>`;
        }).join('');
    };

    const loadContactActivity = async () => {
        if (!activeContact) {
            renderContactActivity([]);
            return;
        }
        if (contactActivityRefreshBtn) contactActivityRefreshBtn.disabled = true;
        if (contactActivityEl) contactActivityEl.innerHTML = '<p class="text-sm text-slate-400">{{ __('Loading activity…') }}</p>';
        try {
            const data = await contactRequest(`${contactsUrl}/${activeContact.id}/activity`);
            renderContactActivity(data.activity || []);
        } catch (error) {
            if (contactActivityEl) contactActivityEl.innerHTML = `<p class="text-sm text-red-400">${escapeContactText(error.message || '{{ __('Unable to load activity') }}')}</p>`;
        } finally {
            if (contactActivityRefreshBtn) contactActivityRefreshBtn.disabled = !activeContact || !contactPermissions.view;
        }
    };

    const renderContactCallHistory = (calls = []) => {
        if (!contactCallHistoryEl) return;
        if (!activeContact) {
            contactCallHistoryEl.innerHTML = '<p class="text-sm text-slate-400">{{ __('Select a saved contact to view call history.') }}</p>';
            return;
        }
        if (!calls.length) {
            contactCallHistoryEl.innerHTML = '<p class="text-sm text-slate-400">{{ __('No calls recorded for this contact yet.') }}</p>';
            return;
        }
        contactCallHistoryEl.innerHTML = calls.map((item) => {
            const inbound = item.direction === 'inbound';
            const timestamp = item.created_at ? new Date(item.created_at).toLocaleString() : '';
            const duration = Number(item.duration_seconds || 0);
            const durationText = duration > 0 ? `${Math.floor(duration / 60)}m ${duration % 60}s` : '';
            const status = String(item.status || '{{ __('Unknown') }}').replaceAll('_', ' ');
            const number = inbound ? (item.caller_id || item.destination) : (item.destination || item.caller_id);
            const agent = item.user?.external_name || item.user?.email || '';
            return `<article class="relative border-l-2 ${inbound ? 'border-emerald-500/50' : 'border-blue-500/50'} pl-4">
                <span class="absolute -left-[7px] top-1 flex h-3 w-3 rounded-full ${inbound ? 'bg-emerald-500' : 'bg-blue-500'}"></span>
                <div class="rounded-xl border border-[#263b50] bg-[#091827] p-3">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div><strong class="flex items-center gap-2 text-sm text-slate-100"><i class="bi ${inbound ? 'bi-telephone-inbound text-emerald-400' : 'bi-telephone-outbound text-blue-400'}"></i>${inbound ? '{{ __('Inbound call') }}' : '{{ __('Outbound call') }}'}</strong><p class="mt-1 break-all text-xs text-slate-400">${escapeContactText(number || '—')}</p></div>
                        <time class="text-[11px] text-slate-500">${escapeContactText(timestamp)}</time>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px]"><span class="rounded-full bg-slate-700/60 px-2 py-1 capitalize text-slate-300">${escapeContactText(status)}</span>${durationText ? `<span class="text-slate-400"><i class="bi bi-clock mr-1"></i>${durationText}</span>` : ''}${agent ? `<span class="text-slate-400"><i class="bi bi-person mr-1"></i>${escapeContactText(agent)}</span>` : ''}</div>
                    ${item.notes ? `<p class="mt-2 line-clamp-3 whitespace-pre-wrap text-xs leading-5 text-slate-300">${escapeContactText(item.notes)}</p>` : ''}
                </div>
            </article>`;
        }).join('');
    };

    const loadContactCallHistory = async () => {
        if (!activeContact) return renderContactCallHistory([]);
        if (contactCallHistoryRefreshBtn) contactCallHistoryRefreshBtn.disabled = true;
        if (contactCallHistoryEl) contactCallHistoryEl.innerHTML = '<p class="text-sm text-slate-400">{{ __('Loading call history…') }}</p>';
        try {
            const data = await contactRequest(`${contactsUrl}/${activeContact.id}/call-history`);
            renderContactCallHistory(data.calls || []);
        } catch (error) {
            if (contactCallHistoryEl) contactCallHistoryEl.innerHTML = `<p class="text-sm text-red-400">${escapeContactText(error.message || '{{ __('Unable to load call history') }}')}</p>`;
        } finally {
            if (contactCallHistoryRefreshBtn) contactCallHistoryRefreshBtn.disabled = !activeContact || !contactPermissions.view;
        }
    };

    const activateContactTab = (tab) => {
        activeContactTab = tab;
        contactTabButtons.forEach((button) => {
            const active = button.dataset.contactTab === tab;
            button.setAttribute('aria-selected', active ? 'true' : 'false');
            button.classList.toggle('contact-tab-active', active);
            button.classList.toggle('border-transparent', !active);
            button.classList.toggle('text-slate-400', !active);
        });
        contactTabPanels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.contactTabPanel !== tab));
        if (tab === 'activity') loadContactActivity();
        if (tab === 'history') loadContactCallHistory();
    };

    contactTabButtons.forEach((button) => button.addEventListener('click', () => activateContactTab(button.dataset.contactTab)));
    contactActivityRefreshBtn?.addEventListener('click', loadContactActivity);
    contactCallHistoryRefreshBtn?.addEventListener('click', loadContactCallHistory);

    const updateActiveContact = async (changes) => {
        if (!activeContact) return;
        try {
            setContactFeedback('{{ __('Saving…') }}');
            const data = await contactRequest(`${contactsUrl}/${activeContact.id}`, {
                method: 'PATCH',
                body: JSON.stringify(changes)
            });
            renderContact(data.contact);
            await refreshGlobalContactLabels();
            setContactFeedback('{{ __('Saved') }}');
        } catch (error) {
            setContactFeedback(error.message || '{{ __('Unable to save contact') }}', true);
        }
    };

    const lookupContactByPhone = async (phone, force = false) => {
        if (!contactPermissions.view) return null;
        const normalized = normalizeContactPhone(phone);
        if (!normalized) {
            lastContactLookupPhone = '';
            renderContact(null, phone);
            return null;
        }
        if (!force && normalized === lastContactLookupPhone && activeContact?.phone_normalized === normalized) {
            return activeContact;
        }
        lastContactLookupPhone = normalized;
        try {
            const data = await contactRequest(`${contactsUrl}?phone=${encodeURIComponent(phone)}`);
            updateContactLabelOptions(data.labels || availableContactLabels);
            const contact = data.contacts?.[0] || null;
            renderContact(contact, phone);
            return contact;
        } catch (error) {
            setContactFeedback('{{ __('Contact lookup failed') }}', true);
            return null;
        }
    };

    const renderContactSearchResults = (contacts = []) => {
        if (!contactSearchResultsEl) return;
        if (!contacts.length) {
            contactSearchResultsEl.innerHTML = '<p class="px-3 py-2 text-sm text-slate-400">{{ __('No contacts found') }}</p>';
        } else {
            contactSearchResultsEl.innerHTML = contacts.map((contact) => `<button type="button" data-contact-id="${contact.id}" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left hover:bg-white/5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">${contactInitials(contact.name)}</span>
                <span class="min-w-0 flex-1"><strong class="block truncate text-sm text-white">${escapeContactText(contact.name)}</strong><span class="block truncate text-xs text-slate-400">${escapeContactText(contact.company || contact.phone)}</span><span class="mt-1 flex flex-wrap gap-1">${(contact.labels || []).map((label) => `<span class="rounded-full bg-blue-500/10 px-1.5 py-0.5 text-[10px] text-blue-300">${escapeContactText(label)}</span>`).join('')}</span></span>
                ${contact.is_flagged ? '<i class="bi bi-flag-fill ml-auto text-amber-400"></i>' : ''}
            </button>`).join('');
            contactSearchResultsEl.querySelectorAll('[data-contact-id]').forEach((button) => {
                button.addEventListener('click', () => {
                    const contact = contacts.find((item) => String(item.id) === button.dataset.contactId);
                    if (contact) {
                        renderContact(contact);
                        if (displayInput && !callActive) displayInput.value = contact.phone;
                        if (hiddenInput && !callActive) hiddenInput.value = contact.phone;
                    }
                    contactSearchResultsEl.classList.add('hidden');
                    contactSearchEl.value = '';
                });
            });
        }
        contactSearchResultsEl.classList.remove('hidden');
    };

    const runContactSearch = async () => {
        if (!contactPermissions.view) return;
        const search = contactSearchEl?.value.trim() || '';
        const label = contactLabelFilterEl?.value || '';
        if (!search && !label) {
            contactSearchResultsEl?.classList.add('hidden');
            return;
        }
        try {
            const query = new URLSearchParams();
            if (search) query.set('search', search);
            if (label) query.set('label', label);
            const data = await contactRequest(`${contactsUrl}?${query.toString()}`);
            updateContactLabelOptions(data.labels || []);
            if (label && contactLabelFilterEl) contactLabelFilterEl.value = label;
            renderContactSearchResults(data.contacts || []);
        } catch (error) {
            setContactFeedback('{{ __('Contact search failed') }}', true);
        }
    };

    contactSearchEl?.addEventListener('input', () => {
        window.clearTimeout(contactSearchTimer);
        const search = contactSearchEl.value.trim();
        if (!search && !contactLabelFilterEl?.value) {
            contactSearchResultsEl?.classList.add('hidden');
            return;
        }
        contactSearchTimer = window.setTimeout(runContactSearch, 250);
    });
    contactLabelFilterEl?.addEventListener('change', runContactSearch);

    document.addEventListener('click', (event) => {
        if (!contactSearchEl?.contains(event.target) && !contactSearchResultsEl?.contains(event.target)) {
            contactSearchResultsEl?.classList.add('hidden');
        }
    });

    contactSaveBtn?.addEventListener('click', async () => {
        try {
            setContactFeedback('{{ __('Saving…') }}');
            const payload = {
                name: contactNameInput?.value.trim() || '',
                company: contactCompanyInput?.value.trim() || null,
                phone: contactPhoneInput?.value.trim() || displayInput?.value.trim() || '',
                email: contactEmailInput?.value.trim() || null,
                is_flagged: Boolean(activeContact?.is_flagged)
            };
            if (contactPermissions.labels) payload.labels = activeContact?.labels || [];
            const data = activeContact
                ? await contactRequest(`${contactsUrl}/${activeContact.id}`, { method: 'PATCH', body: JSON.stringify(payload) })
                : await contactRequest(contactsUrl, { method: 'POST', body: JSON.stringify(payload) });
            lastContactLookupPhone = data.contact.phone_normalized;
            renderContact(data.contact);
            await refreshGlobalContactLabels();
            setContactFeedback('{{ __('Contact saved') }}');
        } catch (error) {
            setContactFeedback(error.message || '{{ __('Unable to save contact') }}', true);
        }
    });

    contactFlagBtn?.addEventListener('click', () => updateActiveContact({ is_flagged: !activeContact?.is_flagged }));
    contactLabelAddBtn?.addEventListener('click', () => {
        const label = contactLabelInput?.value.trim();
        if (!activeContact || !label) return;
        const labels = [...(activeContact.labels || []), label];
        contactLabelInput.value = '';
        updateActiveContact({ labels });
    });
    contactLabelInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            contactLabelAddBtn?.click();
        }
    });

    contactCommentAddBtn?.addEventListener('click', async () => {
        const body = contactCommentInput?.value.trim();
        if (!activeContact || !body) return;
        try {
            contactCommentAddBtn.disabled = true;
            const data = await contactRequest(`${contactsUrl}/${activeContact.id}/comments`, {
                method: 'POST',
                body: JSON.stringify({ body })
            });
            contactCommentInput.value = '';
            activeContact.comments = [data.comment, ...(activeContact.comments || [])];
            renderContactComments(activeContact.comments);
            setContactFeedback('{{ __('Comment added') }}');
        } catch (error) {
            setContactFeedback(error.message || '{{ __('Unable to add comment') }}', true);
        } finally {
            contactCommentAddBtn.disabled = !activeContact || !contactPermissions.comment;
        }
    });

    displayInput?.addEventListener('input', () => {
        window.clearTimeout(dialContactLookupTimer);
        const phone = displayInput.value;
        if (normalizeContactPhone(phone).length < 3) return;
        dialContactLookupTimer = window.setTimeout(() => lookupContactByPhone(phone), 350);
    });

    renderContact(null);
    if (contactPermissions.view) {
        contactRequest(contactsUrl)
            .then((data) => updateContactLabelOptions(data.labels || []))
            .catch(() => {});
    }

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
    let directSipActive = false;

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
        const normalized = normalizeContactPhone(value);
        if (!activeContact || activeContact.phone_normalized !== normalized) {
            renderContact(null, value);
        }
        window.clearTimeout(dialContactLookupTimer);
        if (normalized.length >= 3) {
            dialContactLookupTimer = window.setTimeout(() => lookupContactByPhone(value), 350);
        }
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

    const updateWebPhoneState = (state, status = 'ready') => {
        if (webPhoneStateEl) webPhoneStateEl.textContent = state;
        if (!webPhoneStateDotEl) return;
        webPhoneStateDotEl.classList.remove(
            'bg-amber-400', 'bg-emerald-400', 'bg-red-400',
            'shadow-[0_0_8px_rgba(251,191,36,0.65)]',
            'shadow-[0_0_8px_rgba(52,211,153,0.8)]',
            'shadow-[0_0_8px_rgba(248,113,113,0.75)]'
        );
        const classes = status === 'error'
            ? ['bg-red-400', 'shadow-[0_0_8px_rgba(248,113,113,0.75)]']
            : (status === 'pending'
                ? ['bg-amber-400', 'shadow-[0_0_8px_rgba(251,191,36,0.65)]']
                : ['bg-emerald-400', 'shadow-[0_0_8px_rgba(52,211,153,0.8)]']);
        webPhoneStateDotEl.classList.add(...classes);
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
                updateWebPhoneState('{{ __('Connecting') }}', 'pending');
                updateBrowserAudioStatus('Browser audio idle');
                webRtcClient.ensureClient().then(() => {
                    updateWebPhoneState('{{ __('Ready') }}');
                    updateBrowserAudioStatus('Browser audio ready');
                }).catch((error) => {
                    console.error('Failed to register WebRTC client', error);
                    updateWebPhoneState('{{ __('Unavailable') }}', 'error');
                    updateBrowserAudioStatus('Browser audio unavailable', true);
                });
            } else {
                updateWebPhoneState('{{ __('Not configured') }}', 'error');
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
            if (!callUuid && directSipActive) {
                if (action === 'mute' || action === 'unmute') {
                    await applyMuteState(action === 'mute');
                } else if (action === 'hangup') {
                    await webRtcClient?.leaveConference();
                    directSipActive = false;
                    callActive = false;
                    browserAudioActive = false;
                    setControls(false);
                    setStatus('ended');
                    stopTimer();
                    updateBrowserAudioStatus('Browser audio ready');
                    refreshStartButton();
                }
                return;
            }
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
            if (callActive) {
                playTone(value);
                if (alertEl) alertEl.classList.add('hidden');

                try {
                    if (directSipActive) {
                        await webRtcClient?.sendDtmf(value);
                    } else if (callUuid) {
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

        lookupContactByPhone(payload.destination);

        startCallButton.disabled = true;

        try {
            // Local outbound audio test. This deliberately bypasses the carrier;
            // all normal destinations continue through the backend call flow.
            if (payload.destination === '9196') {
                const client = ensureWebRtcClient();
                if (!client) {
                    throw new Error('Browser audio is not configured for this user.');
                }

                await client.joinConference('9196');
                directSipActive = true;
                browserAudioActive = true;
                callActive = true;
                setStatus('in_call');
                setControls(true);
                startTimer();
                updateBrowserAudioStatus('Echo test connected · speak to hear your voice');
                return;
            }

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
            const errorMessage = error?.message ? String(error.message) : '';
            const isEchoTest = payload.destination === '9196';
            const message = isEchoTest
                ? `Echo test failed${errorMessage ? `: ${errorMessage}` : '. Check the Web phone connection and microphone permission.'}`
                : `Network error while queuing the call${errorMessage ? `: ${errorMessage}` : '.'}`;
            alertBox.textContent = message;
            alertBox.classList.remove('hidden');
            refreshStartButton();
            setStatus('ended');
            showError(message);
            await disconnectBrowserAudio();
        }
    });

    // ===== Inbound (round-robin) incoming-call handling =====
    const inboundSocketEl = document.getElementById('dialer-inbound-socket');
    const incomingBanner = document.getElementById('incoming-call-banner');
    const incomingCallerEl = document.getElementById('incoming-caller');
    const incomingDidEl = document.getElementById('incoming-did');
    const incomingAcceptBtn = document.getElementById('incoming-accept');
    const incomingDeclineBtn = document.getElementById('incoming-decline');

    let inboundCall = null; // { callUuid, conference, callerIdNumber, did }

    const showIncomingBanner = (show) => {
        if (!incomingBanner) return;
        incomingBanner.classList.toggle('hidden', !show);
    };

    const hideIncoming = () => {
        inboundCall = null;
        showIncomingBanner(false);
    };

    const acceptInbound = async () => {
        if (!inboundCall) return;
        const call = inboundCall;
        showIncomingBanner(false);

        if (call.directSip) {
            try {
                ensureWebRtcClient();
                await webRtcClient?.answerIncoming();
                callActive = true;
                directSipActive = true;
                browserAudioActive = true;
                if (liveSession) liveSession.classList.remove('hidden');
                setStatus('in_call');
                setControls(true);
                updateBrowserAudioStatus('Incoming browser audio connected');
            } catch (error) {
                showError(error?.message || 'Unable to answer incoming call.');
            }
            inboundCall = null;
            return;
        }

        callUuid = call.callUuid;
        conferenceName = call.conference || null;
        if (callIdBadge) {
            callIdBadge.textContent = `Call ID · ${callUuid}`;
            callIdBadge.classList.remove('hidden');
        }
        if (liveSession) liveSession.classList.remove('hidden');

        callActive = true;
        await applyMuteState(false);
        setControls(true);
        setStatus('in_call');
        ensureWebRtcClient();
        if (conferenceName && webRtcClient) {
            connectBrowserAudio();
        }
        pollStatus();
        if (pollHandle) clearInterval(pollHandle);
        pollHandle = setInterval(pollStatus, 1000);
        inboundCall = null;
    };

    const declineInbound = async () => {
        if (!inboundCall) return;
        if (inboundCall.directSip) {
            hideIncoming();
            try {
                await webRtcClient?.declineIncoming();
            } catch (e) {
                console.warn('[inbound] SIP decline failed', e);
            }
            return;
        }
        const uuid = inboundCall.callUuid;
        hideIncoming();
        try {
            await fetch(`/admin/dialer/calls/${uuid}/decline`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
        } catch (e) {
            console.warn('[inbound] decline failed', e);
        }
    };

    if (incomingAcceptBtn) incomingAcceptBtn.addEventListener('click', acceptInbound);
    if (incomingDeclineBtn) incomingDeclineBtn.addEventListener('click', declineInbound);

    window.addEventListener('dialer:sip-incoming', (event) => {
        if (callActive || inboundCall) return;
        inboundCall = {
            directSip: true,
            callerIdNumber: event.detail?.callerIdNumber || 'Unknown',
            did: null
        };
        if (incomingCallerEl) incomingCallerEl.textContent = inboundCall.callerIdNumber;
        if (incomingDidEl) incomingDidEl.textContent = '· SIP';
        lookupContactByPhone(inboundCall.callerIdNumber);
        showIncomingBanner(true);
    });
    window.addEventListener('dialer:sip-hangup', () => {
        if (inboundCall?.directSip) hideIncoming();
        if (!callUuid && callActive) {
            directSipActive = false;
            callActive = false;
            browserAudioActive = false;
            setControls(false);
            setStatus('ended');
            stopTimer();
            updateBrowserAudioStatus('Browser audio idle');
            refreshStartButton();
        }
    });

    const initInboundSocket = () => {
        if (!window.io || !inboundSocketEl) return;
        let cfg = {};
        try {
            cfg = JSON.parse(inboundSocketEl.dataset.config || '{}');
        } catch (e) {
            console.warn('[inbound] invalid socket config', e);
            return;
        }
        if (!cfg.url || !cfg.userId) return;

        const socket = window.io(cfg.url, {
            transports: ['websocket', 'polling'],
            auth: { userId: cfg.userId }
        });
        socket.on('connect', () => {
            socket.emit('identify', cfg.userId);
        });
        socket.on('incoming.call', (payload) => {
            if (!payload || !payload.callUuid) return;
            // Don't interrupt an active call.
            if (callActive) return;
            inboundCall = {
                callUuid: payload.callUuid,
                conference: payload.conference || null,
                callerIdNumber: payload.callerIdNumber || null,
                did: payload.did || null
            };
            if (incomingCallerEl) incomingCallerEl.textContent = payload.callerIdNumber || 'Unknown';
            if (incomingDidEl) incomingDidEl.textContent = payload.did ? `· DID ${payload.did}` : '';
            lookupContactByPhone(payload.callerIdNumber || payload.did || '');
            showIncomingBanner(true);
        });
        socket.on('incoming.call.cancel', (payload) => {
            if (!inboundCall || !payload || payload.callUuid !== inboundCall.callUuid) return;
            hideIncoming();
        });
    };
    initInboundSocket();
    // initial
    setControls(false);
    if (!webRtcClient) {
        updateBrowserAudioStatus('');
    }
});

</script>
@endpush
