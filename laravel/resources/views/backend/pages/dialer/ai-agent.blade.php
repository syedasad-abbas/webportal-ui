@extends('backend.layouts.app')

@section('title', __('AI Agent') . ' | ' . config('app.name'))

@push('styles')
<style>
/*
 * The shared stylesheet forces a fixed palette on every element inside
 * .connectpro-communication-page in light mode (inputs become #f8fbff, any
 * .rounded-xl becomes a white surface, headings become #0f172a). Those rules
 * are more specific than the single utilities used below, so this page states
 * its own palette with ID selectors, the same way the dialer page restyles its
 * history and activity panels for light mode.
 */
html:not(.dark) #ai-agent-page { background: #f5f8fc; color: #0f172a; }
html:not(.dark) #ai-agent-page h1,
html:not(.dark) #ai-agent-page h2 { color: #0f172a; }
html:not(.dark) #ai-agent-page .ai-label { color: #64748b; }
html:not(.dark) #ai-agent-page .ai-hint { color: #64748b; }
html:not(.dark) #ai-agent-page .ai-card { background: #fff; border-color: #d7e2ed; }
html:not(.dark) #ai-agent-page .ai-divide { border-color: #d7e2ed; }
html:not(.dark) #ai-agent-page input,
html:not(.dark) #ai-agent-page select,
html:not(.dark) #ai-agent-page textarea { color: #0f172a; background: #f8fbff; border-color: #cbd8e6; }
html:not(.dark) #ai-agent-page .ai-readonly { color: #475569; background: #eef3f9; border-color: #d7e2ed; }
html:not(.dark) #ai-agent-page .ai-save { color: #7c3aed; background: #fff; border-color: #8b5cf6; }
html:not(.dark) #ai-agent-page .ai-save:hover { background: #f5f3ff; }
html:not(.dark) #ai-agent-page .ai-toggle { color: #fff; background: #7c3aed; }
html:not(.dark) #ai-agent-page .ai-toggle:hover { background: #6d28d9; }
html:not(.dark) #ai-agent-page .ai-feedback { color: #64748b; }
/* The shared rule repaints every .rounded-xl white in light mode, so the
   tinted status banner and icon badge restate their own colours. */
html:not(.dark) #ai-agent-page .ai-banner { color: #0f172a; background: #fffbeb; border-color: #fde68a; }
html:not(.dark) #ai-agent-page .ai-banner .ai-banner-title { color: #92400e; }
html:not(.dark) #ai-agent-page .ai-banner .ai-banner-detail { color: #b45309; }
html:not(.dark) #ai-agent-page .ai-icon { color: #7c3aed; background: #f5f3ff; }
html:not(.dark) #ai-agent-page .ai-alert { color: #991b1b; background: #fef2f2; border-color: #fecaca; }

/* Dark mode mirrors the floating card this form replaced. */
.dark #ai-agent-page { background: #06111f; color: #fff; }
.dark #ai-agent-page h1,
.dark #ai-agent-page h2 { color: #fff; }
.dark #ai-agent-page .ai-label { color: #94a3b8; }
.dark #ai-agent-page .ai-hint { color: #94a3b8; }
.dark #ai-agent-page .ai-card { background: #091827; border-color: #2a4055; }
.dark #ai-agent-page .ai-divide { border-color: #263b50; }
.dark #ai-agent-page input,
.dark #ai-agent-page select,
.dark #ai-agent-page textarea { color-scheme: dark; color: #fff !important; background: #071625 !important; border-color: #365068 !important; }
.dark #ai-agent-page .ai-readonly { color: #cbd5e1; background: #0b1c2c; border-color: #263b50; }
.dark #ai-agent-page .ai-save { color: #c4b5fd; background: transparent; border-color: #8b5cf6; }
.dark #ai-agent-page .ai-save:hover { background: rgba(139, 92, 246, .1); }
.dark #ai-agent-page .ai-toggle { color: #fff; background: #7c3aed; }
.dark #ai-agent-page .ai-toggle:hover { background: #6d28d9; }
.dark #ai-agent-page .ai-feedback { color: #94a3b8; }
.dark #ai-agent-page .ai-banner { color: #fff; background: rgba(245, 158, 11, .1); border-color: rgba(245, 158, 11, .2); }
.dark #ai-agent-page .ai-banner .ai-banner-title { color: #fef3c7; }
.dark #ai-agent-page .ai-banner .ai-banner-detail { color: #fcd34d; }
.dark #ai-agent-page .ai-icon { color: #c4b5fd; background: rgba(139, 92, 246, .15); }
.dark #ai-agent-page .ai-alert { color: #fecaca; background: rgba(239, 68, 68, .1); border-color: rgba(239, 68, 68, .2); }
/* Expected questions. The row surface is a light-first card, so in dark mode
   the editor keeps a white input on it and only the chrome around the inputs
   follows the theme. */
html:not(.dark) #ai-agent-page .ai-faq { background: #f8fafc; border-color: #e2e8f0; }
html:not(.dark) #ai-agent-page .ai-faq-index { color: #7c3aed; background: #f5f3ff; }
html:not(.dark) #ai-agent-page .ai-add-faq,
html:not(.dark) #ai-agent-page .ai-save-faq { color: #7c3aed; background: #fff; border-color: #8b5cf6; }
html:not(.dark) #ai-agent-page .ai-add-faq:hover,
html:not(.dark) #ai-agent-page .ai-save-faq:hover { background: #f5f3ff; }
.dark #ai-agent-page .ai-faq { background: #0b1c2c; border-color: #263b50; }
.dark #ai-agent-page .ai-faq-index { color: #c4b5fd; background: rgba(139, 92, 246, .15); }
.dark #ai-agent-page .ai-add-faq,
.dark #ai-agent-page .ai-save-faq { color: #c4b5fd; background: transparent; border-color: #8b5cf6; }
.dark #ai-agent-page .ai-add-faq:hover,
.dark #ai-agent-page .ai-save-faq:hover { background: rgba(139, 92, 246, .1); }
/* The checkbox sits on a light card inside the dark card, so it keeps the
   page's dark input surface rather than a flat white box. */
.dark #ai-agent-page input[type="checkbox"] {
    background: #0b1c2c !important;
    border-color: #365068 !important;
    /* The checked state paints its background as currentColor !important, and
       the card-wide rule above leaves every input's color at white, so the
       checked box would come out white on white. Setting the checkbox colour
       here makes currentColor resolve to violet for both states. */
    color: #c4b5fd !important;
}
/* The card-wide rule above sets `background: #071625 !important` on every
   input, and `background` is the shorthand for background-color, so without
   the flag here the checkbox would stay white. The same flag is what lets the
   checked state override the unchecked one. */
.dark #ai-agent-page input[type="checkbox"]:checked { background-color: #7c3aed !important; border-color: #7c3aed !important; }
</style>
@endpush

@section('admin-content')
@php
    // Rendered up front so the page reads correctly before the script runs and
    // stays correct if it never does. The script re-applies the same state from
    // the backend once it responds.
    $agentEnabled = (bool) ($aiAgentSettings['enabled'] ?? false);
    $agentReady = (bool) ($aiAgentSettings['ready'] ?? false);
    $agentStatusTitle = $agentEnabled
        ? __('AI agent enabled')
        : ($agentReady ? __('AI agent ready') : __('Setup required'));
    $agentStatusDetail = $agentEnabled
        ? __('New inbound calls go to Gemini Live')
        : ($agentReady ? __('Human routing remains active') : __('Add GEMINI_API_KEY to activate'));
@endphp
{{--
    The page surface follows the same palette as the floating card this form
    replaced: light by default, dark navy under the `dark` class, and it uses
    the card's own light-first utility classes rather than dark-only colours.
--}}
<div id="ai-agent-page" class="connectpro-communication-page min-h-full bg-slate-50 text-slate-900 dark:bg-[#06111f] dark:text-white">
    <div class="w-full p-4 sm:p-6">
        <div class="mx-auto max-w-4xl space-y-6">

            <header class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $aiAgentBoxTitle }}</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $aiAgentBoxSubtitle }} — {{ __('configure the agent that answers inbound calls') }}</p>
                </div>
                <span class="rounded-full bg-violet-500/10 px-3 py-1 text-xs font-semibold text-violet-600 dark:text-violet-300">{{ __('Gemini Live') }}</span>
            </header>

            <section class="ai-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_24px_80px_rgba(2,9,20,.08)] dark:border-[#2a4055] dark:bg-[#091827] dark:shadow-[0_24px_80px_rgba(2,9,20,.35)]">
                <div class="flex items-center gap-3 ai-divide ai-divide border-b border-slate-200 px-4 py-3 dark:border-[#263b50]">
                    <span class="ai-icon relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-500/15 text-violet-500 ring-1 ring-inset ring-violet-500/25 dark:text-violet-300">
                        <i class="bi bi-stars text-xl"></i>
                        <span id="ai-agent-dot" class="absolute -right-1 -top-1 h-3 w-3 rounded-full border-2 border-white bg-amber-400 dark:border-[#091827]"></span>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2">
                            <strong id="ai-agent-status-title" class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $agentStatusTitle }}</strong>
                        </span>
                        <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ $agentStatusDetail }}</span>
                    </span>
                </div>

                <div class="space-y-4 p-4">
                    <div class="ai-banner flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 dark:border-amber-500/20 dark:bg-amber-500/10">
                        <span id="ai-agent-status-dot" class="h-2.5 w-2.5 shrink-0 rounded-full bg-amber-400 shadow-[0_0_10px_rgba(251,191,36,.55)]"></span>
                        <span class="min-w-0 flex-1">
                            <span id="ai-agent-status-banner-title" class="ai-banner-title block text-xs font-semibold text-amber-900 dark:text-amber-100">{{ $agentStatusTitle }}</span>
                            <span id="ai-agent-status-detail" class="ai-banner-detail block text-[11px] text-amber-700 dark:text-amber-300/80">{{ $agentStatusDetail }}</span>
                        </span>
                        <i id="ai-agent-status-icon" class="bi bi-cloud-slash text-amber-600 dark:text-amber-300" aria-hidden="true"></i>
                    </div>

                    @if ($aiAgentSettings === null)
                        <div class="ai-alert flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2.5 text-xs text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300">
                            {{ __('The backend is not reachable, so the current settings could not be loaded. Saving may fail until it responds.') }}
                        </div>
                    @endif

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="ai-label mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Live voice model (Google)') }}</label>
                            <p id="ai-agent-live-model" class="ai-readonly w-full rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-xs text-slate-600 dark:border-[#263b50] dark:bg-[#0b1c2c] dark:text-slate-300">{{ $aiAgentSettings['model'] ?? '—' }}</p>
                        </div>
                        <div>
                            <label class="ai-label mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Active AI sessions') }}</label>
                            <p id="ai-agent-sessions" class="ai-readonly w-full rounded-xl border border-slate-200 bg-slate-100 px-3 py-2.5 text-xs text-slate-600 dark:border-[#263b50] dark:bg-[#0b1c2c] dark:text-slate-300">{{ $aiAgentSettings['activeSessions'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="ai-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_24px_80px_rgba(2,9,20,.08)] dark:border-[#2a4055] dark:bg-[#091827] dark:shadow-[0_24px_80px_rgba(2,9,20,.35)]">
                <div class="ai-divide ai-divide border-b border-slate-200 px-4 py-3 dark:border-[#263b50]">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Agent behaviour') }}</h2>
                    <p class="ai-hint mt-1 text-[11px] text-slate-500 dark:text-slate-400">{{ __('The duty is the job the agent performs, the greeting is how it opens every call, and the objective is what the call must achieve. Any wording is accepted.') }}</p>
                </div>

                <div class="space-y-4 p-4">
                    <div>
                        <label for="ai-agent-role" class="ai-label mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Duty assignment') }}</label>
                        <input id="ai-agent-role" type="text" maxlength="200" value="{{ $aiAgentSettings['role'] ?? '' }}" placeholder="{{ __('e.g. sales representative, appointment booking, assistant, guide…') }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 dark:border-[#365068] dark:bg-[#071625] dark:text-white">
                        <p class="ai-hint mt-1 text-[11px] text-slate-500 dark:text-slate-400">{{ __('Leave empty to use the default duty.') }}</p>
                    </div>

                    <div>
                        <label for="ai-agent-greeting" class="ai-label mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Opening greeting') }}</label>
                        <textarea id="ai-agent-greeting" rows="2" maxlength="1000" placeholder="{{ __('Hello, this is Adam, how can I help you today?') }}" class="w-full resize-none rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 dark:border-[#365068] dark:bg-[#071625] dark:text-white">{{ $aiAgentSettings['greeting'] ?? '' }}</textarea>
                        <p class="ai-hint mt-1 text-[11px] text-slate-500 dark:text-slate-400">{{ __('Spoken at the start of every call. Leave empty to let the agent introduce itself.') }}</p>
                    </div>

                    <div>
                        <label for="ai-agent-goal" class="ai-label mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Conversational goal') }}</label>
                        <textarea id="ai-agent-goal" rows="4" maxlength="2000" placeholder="{{ __('Collect the details needed to book the appointment…') }}" class="w-full resize-none rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 dark:border-[#365068] dark:bg-[#071625] dark:text-white">{{ $aiAgentSettings['goal'] ?? '' }}</textarea>
                        <p class="ai-hint mt-1 text-[11px] text-slate-500 dark:text-slate-400">{{ __('What this call must achieve: collect, inform, qualify, and so on.') }}</p>
                    </div>
                </div>
            </section>

            <section class="ai-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_24px_80px_rgba(2,9,20,.08)] dark:border-[#2a4055] dark:bg-[#091827] dark:shadow-[0_24px_80px_rgba(2,9,20,.35)]"
                x-data="aiFaqEditor(@js($aiAgentFaqs), @js(route('admin.ai-agent.faqs.update')))">
                <div class="ai-divide ai-divide border-b border-slate-200 px-4 py-3 dark:border-[#263b50]">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Expected questions and answers') }}</h2>
                    <p class="ai-hint mt-1 text-[11px] text-slate-500 dark:text-slate-400">{{ __('Write the questions callers actually ask and the answer the agent must give for each one. A matching question is answered from your wording instead of something invented on the call.') }}</p>
                </div>

                <div class="space-y-3 p-4">
                    <template x-if="faqs.length === 0">
                        <p class="ai-readonly rounded-xl border border-slate-200 px-3 py-4 text-center text-xs dark:border-[#263b50]">
                            {{ __('No expected questions yet. The agent will answer from its duty and objective, and say when it has no verified details.') }}
                        </p>
                    </template>

                    <template x-for="(faq, index) in faqs" :key="faq.key">
                        <div class="ai-faq rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-[#263b50] dark:bg-[#0b1c2c]">
                            <div class="flex items-start gap-3">
                                <span class="ai-faq-index mt-2 flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-[11px] font-semibold" x-text="index + 1"></span>
                                <div class="min-w-0 flex-1 space-y-2.5">
                                    <div>
                                        <label :for="`ai-faq-question-${faq.key}`" class="ai-label mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('If the caller asks') }}</label>
                                        <input :id="`ai-faq-question-${faq.key}`" type="text" maxlength="500" x-model="faq.question"
                                            placeholder="{{ __('e.g. What are the consultation fees?') }}"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 dark:border-[#365068] dark:bg-white dark:text-slate-900">
                                    </div>
                                    <div>
                                        <label :for="`ai-faq-answer-${faq.key}`" class="ai-label mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('The agent answers') }}</label>
                                        <textarea :id="`ai-faq-answer-${faq.key}`" rows="2" maxlength="2000" x-model="faq.answer"
                                            placeholder="{{ __('e.g. A consultation is 2,000 rupees and includes the follow-up visit.') }}"
                                            class="w-full resize-none rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 dark:border-[#365068] dark:bg-white dark:text-slate-900"></textarea>
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-2">
                                    <label class="flex cursor-pointer items-center gap-1.5 text-[11px] font-medium text-slate-600 dark:text-slate-400">
                                        <input type="checkbox" x-model="faq.isEnabled" class="h-3.5 w-3.5 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                                        <span>{{ __('Active') }}</span>
                                    </label>
                                    <button type="button" class="ai-faq-remove flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-red-300 hover:text-red-600 dark:border-[#263b50] dark:text-slate-400 dark:hover:border-red-500/40 dark:hover:text-red-300"
                                        :aria-label="__('Remove this question')" @click="remove(index)">
                                        <i class="bi bi-trash3 text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <button type="button" class="ai-add-faq flex items-center gap-1.5 rounded-xl border border-violet-500/50 px-3 py-2 text-xs font-semibold text-violet-600 transition hover:bg-violet-500/10 dark:text-violet-300"
                            @click="add()">
                            <i class="bi bi-plus-lg"></i><span>{{ __('Add question') }}</span>
                        </button>
                        <button type="button" class="ai-save-faq flex items-center gap-1.5 rounded-xl border border-violet-500 px-3 py-2 text-xs font-semibold text-violet-600 transition hover:bg-violet-500/10 disabled:opacity-50 dark:text-violet-300"
                            :disabled="saving" @click="save()">
                            <i class="bi bi-check-lg" x-show="!saving"></i>
                            <span x-text="saving ? @json(__('Saving…')) : @json(__('Save questions'))"></span>
                        </button>
                        <span x-show="message" x-text="message" class="ai-feedback text-[11px] text-slate-500 dark:text-slate-400"></span>
                    </div>
                </div>
            </section>

            <section class="ai-card overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_24px_80px_rgba(2,9,20,.08)] dark:border-[#2a4055] dark:bg-[#091827] dark:shadow-[0_24px_80px_rgba(2,9,20,.35)]">
                <div class="ai-divide ai-divide border-b border-slate-200 px-4 py-3 dark:border-[#263b50]">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Voice and routing') }}</h2>
                </div>

                <div class="grid grid-cols-2 gap-3 p-4">
                    <div class="col-span-2">
                        <label for="ai-agent-call-direction" class="ai-label mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Apply configuration to') }}</label>
                        <select id="ai-agent-call-direction" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-xs text-slate-900 outline-none focus:border-violet-500 dark:border-[#365068] dark:bg-[#071625] dark:text-white">
                            <option value="inbound" @selected(($aiAgentSettings['callDirection'] ?? 'inbound') === 'inbound')>{{ __('Inbound only') }}</option>
                            <option value="outbound" @selected(($aiAgentSettings['callDirection'] ?? 'inbound') === 'outbound')>{{ __('Outbound only') }}</option>
                            <option value="both" @selected(($aiAgentSettings['callDirection'] ?? 'inbound') === 'both')>{{ __('Both inbound and outbound') }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="ai-agent-mode" class="ai-label mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Mode') }}</label>
                        <select id="ai-agent-mode" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-xs text-slate-900 outline-none focus:border-violet-500 dark:border-[#365068] dark:bg-[#071625] dark:text-white">
                            <option value="lead" @selected(($aiAgentSettings['mode'] ?? 'lead') === 'lead')>{{ __('Lead conversation') }}</option>
                            <option value="assist" @selected(($aiAgentSettings['mode'] ?? '') === 'assist')>{{ __('Assist agent') }}</option>
                            <option value="qualify" @selected(($aiAgentSettings['mode'] ?? '') === 'qualify')>{{ __('Qualify only') }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="ai-agent-voice" class="ai-label mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Voice style') }}</label>
                        <select id="ai-agent-voice" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-xs text-slate-900 outline-none focus:border-violet-500 dark:border-[#365068] dark:bg-[#071625] dark:text-white">
                            <option value="man" @selected(($aiAgentSettings['voice'] ?? 'man') === 'man')>{{ __('Man') }}</option>
                            <option value="woman" @selected(($aiAgentSettings['voice'] ?? '') === 'woman')>{{ __('Woman') }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-2 px-4 pb-4">
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-medium text-slate-700 dark:border-[#263b50] dark:text-slate-300">
                        <input id="ai-agent-handoff" type="checkbox" @checked((bool) ($aiAgentSettings['humanHandoff'] ?? true)) class="h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                        <span>{{ __('Human handoff') }}</span>
                    </label>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <button id="ai-agent-save" type="button" class="ai-save flex w-full items-center justify-center rounded-xl border border-violet-500 px-4 py-3 text-sm font-semibold text-violet-600 transition hover:bg-violet-500/10 dark:text-violet-300">{{ __('Save settings') }}</button>
                <button id="ai-agent-toggle" type="button" class="ai-toggle flex w-full items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-violet-500">
                    <i class="bi bi-play-fill text-lg"></i><span>{{ __('Enable AI agent') }}</span>
                </button>
            </div>
            <p id="ai-agent-feedback" class="ai-feedback text-center text-[11px] text-slate-500 dark:text-slate-400">{{ __('Inbound calls use normal agent routing while disabled.') }}</p>
        </div>
    </div>
</div>

<script>
// The expected questions editor. Kept as its own Alpine component rather than
// folded into the settings script below because it has a different save
// lifecycle: the settings form has one pair of buttons, while this list is
// edited in place and saved as a whole ordered set.
document.addEventListener('alpine:init', () => {
    Alpine.data('aiFaqEditor', (initial, url) => ({
        // A key per row so Alpine can track each entry by identity while the
        // caller reorders and removes rows; the stored id is never needed here
        // because saving replaces the whole list.
        faqs: (initial || []).map((faq) => ({
            key: faq.id ?? Math.random().toString(36).slice(2),
            question: faq.question || '',
            answer: faq.answer || '',
            isEnabled: faq.isEnabled === undefined ? true : Boolean(faq.isEnabled)
        })),
        url,
        saving: false,
        message: '',

        add() {
            this.faqs.push({
                key: Math.random().toString(36).slice(2),
                question: '',
                answer: '',
                isEnabled: true
            });
            this.message = '';
        },

        remove(index) {
            this.faqs.splice(index, 1);
            this.message = '';
        },

        async save() {
            this.saving = true;
            this.message = @json(__('Saving…'));
            try {
                const response = await fetch(this.url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        faqs: this.faqs.map((faq) => ({
                            question: faq.question,
                            answer: faq.answer,
                            isEnabled: Boolean(faq.isEnabled)
                        }))
                    })
                });
                const body = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(body.message || @json(__('Unable to save the expected questions.')));
                // Re-render from what the backend stored so the list matches the
                // saved order and drops any half-filled row the server discarded.
                this.faqs = (body.faqs || []).map((faq) => ({
                    key: faq.id ?? Math.random().toString(36).slice(2),
                    question: faq.question || '',
                    answer: faq.answer || '',
                    isEnabled: faq.isEnabled === undefined ? true : Boolean(faq.isEnabled)
                }));
                this.message = @json(__('Expected questions saved.'));
            } catch (error) {
                this.message = error.message;
            } finally {
                this.saving = false;
            }
        }
    }));
});

document.addEventListener('DOMContentLoaded', () => {
    const settingsUrl = @json(route('admin.dialer.ai-agent.show'));
    const roleInput = document.getElementById('ai-agent-role');
    const greetingInput = document.getElementById('ai-agent-greeting');
    const goalInput = document.getElementById('ai-agent-goal');
    const modeSelect = document.getElementById('ai-agent-mode');
    const voiceSelect = document.getElementById('ai-agent-voice');
    const handoffInput = document.getElementById('ai-agent-handoff');
    const callDirectionSelect = document.getElementById('ai-agent-call-direction');
    const modelEl = document.getElementById('ai-agent-live-model');
    const sessionsEl = document.getElementById('ai-agent-sessions');
    const dotEl = document.getElementById('ai-agent-dot');
    const statusTitle = document.getElementById('ai-agent-status-title');
    const bannerTitle = document.getElementById('ai-agent-status-banner-title');
    const statusDetail = document.getElementById('ai-agent-status-detail');
    const statusDot = document.getElementById('ai-agent-status-dot');
    const statusIcon = document.getElementById('ai-agent-status-icon');
    const feedback = document.getElementById('ai-agent-feedback');
    const saveButton = document.getElementById('ai-agent-save');
    const toggleButton = document.getElementById('ai-agent-toggle');

    // Rendered server side on load; kept here so the page refreshes the same
    // state from the backend when it is reachable.
    let enabled = @json((bool) ($aiAgentSettings['enabled'] ?? false));
    let ready = @json((bool) ($aiAgentSettings['ready'] ?? false));

    const renderState = () => {
        toggleButton.disabled = false;
        toggleButton.classList.remove('cursor-not-allowed', 'opacity-60');
        toggleButton.querySelector('span').textContent = enabled
            ? @json(__('Disable AI agent'))
            : @json(__('Enable AI agent'));
        toggleButton.querySelector('i').className = enabled ? 'bi bi-stop-fill text-lg' : 'bi bi-play-fill text-lg';

        const on = enabled ? @json(__('AI agent enabled')) : @json(__('Enable AI agent'));
        statusTitle.textContent = on;
        bannerTitle.textContent = on;
        const callDirection = callDirectionSelect?.value || 'inbound';
        const activeRoutingLabel = callDirection === 'both'
            ? @json(__('Inbound and outbound calls go to Gemini Live'))
            : (callDirection === 'outbound'
                ? @json(__('New outbound calls go to Gemini Live'))
                : @json(__('New inbound calls go to Gemini Live')));
        statusDetail.textContent = enabled
            ? activeRoutingLabel
            : (ready ? @json(__('Human routing remains active')) : @json(__('Add GEMINI_API_KEY to activate')));

        // The indicator dot carries a border colour that has to match the card
        // surface, so it is chosen from the active theme rather than hardcoded.
        const isDark = document.documentElement.classList.contains('dark');
        const ring = isDark ? 'dark:border-[#091827]' : 'border-white';
        dotEl.className = `absolute -right-1 -top-1 h-3 w-3 rounded-full border-2 ${ring} ${enabled ? 'bg-emerald-400' : 'bg-amber-400'}`;
        statusDot.className = `h-2.5 w-2.5 shrink-0 rounded-full ${enabled ? 'bg-emerald-400 shadow-[0_0_10px_rgba(52,211,153,.55)]' : 'bg-amber-400 shadow-[0_0_10px_rgba(251,191,36,.55)]'}`;
        statusIcon.className = `bi ${ready ? 'bi-cloud-check' : 'bi-cloud-slash'} text-amber-600 dark:text-amber-300`;
    };

    const applySettings = (settings) => {
        enabled = Boolean(settings.enabled);
        ready = Boolean(settings.ready);
        if (roleInput) roleInput.value = settings.role || '';
        if (greetingInput) greetingInput.value = settings.greeting || '';
        if (goalInput) goalInput.value = settings.goal || '';
        if (modeSelect) modeSelect.value = settings.mode || 'lead';
        if (voiceSelect) voiceSelect.value = settings.voice || 'man';
        if (handoffInput) handoffInput.checked = Boolean(settings.humanHandoff);
        if (callDirectionSelect) callDirectionSelect.value = settings.callDirection || 'inbound';
        if (modelEl) modelEl.textContent = settings.model || '—';
        if (sessionsEl) sessionsEl.textContent = settings.activeSessions ?? 0;
        renderState();
    };

    // Keep a local draft so an accidental navigation does not lose typing, but
    // never let a draft overwrite what the server reports as saved.
    const draftKey = 'dialer.aiAgent.setup';
    const readDraft = () => {
        try { return JSON.parse(window.localStorage.getItem(draftKey) || 'null'); } catch (error) { return null; }
    };
    const writeDraft = () => {
        try {
            window.localStorage.setItem(draftKey, JSON.stringify({
                role: roleInput?.value || '',
                greeting: greetingInput?.value || '',
                goal: goalInput?.value || '',
                mode: modeSelect?.value || 'lead',
                voice: voiceSelect?.value || 'man',
                handoff: Boolean(handoffInput?.checked),
                callDirection: callDirectionSelect?.value || 'inbound'
            }));
        } catch (error) {}
    };
    [roleInput, greetingInput, goalInput, modeSelect, voiceSelect, handoffInput, callDirectionSelect]
        .filter(Boolean)
        .forEach((control) => control.addEventListener('input', writeDraft));
    [modeSelect, voiceSelect, handoffInput, callDirectionSelect].filter(Boolean)
        .forEach((control) => control.addEventListener('change', writeDraft));

    const draft = readDraft();
    if (draft) {
        if (roleInput && typeof draft.role === 'string' && roleInput.value === '') roleInput.value = draft.role;
        if (greetingInput && typeof draft.greeting === 'string' && greetingInput.value === '') greetingInput.value = draft.greeting;
        if (goalInput && typeof draft.goal === 'string' && goalInput.value === '') goalInput.value = draft.goal;
    }

    renderState();
    // Keep the indicator ring aligned if the theme is toggled while on the page.
    new MutationObserver(renderState).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    const save = async (nextEnabled) => {
        toggleButton.disabled = true;
        if (saveButton) saveButton.disabled = true;
        if (feedback) feedback.textContent = @json(__('Saving…'));
        try {
            const response = await fetch(settingsUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    enabled: nextEnabled,
                    role: roleInput?.value || '',
                    greeting: greetingInput?.value || '',
                    goal: goalInput?.value || '',
                    mode: modeSelect?.value || 'lead',
                    voice: voiceSelect?.value || 'man',
                    humanHandoff: Boolean(handoffInput?.checked),
                    callDirection: callDirectionSelect?.value || 'inbound'
                })
            });
            const body = await response.json();
            if (!response.ok) throw new Error(body.message || 'Unable to update AI agent');
            applySettings(body.settings);
            try { window.localStorage.removeItem(draftKey); } catch (error) {}
            if (feedback) feedback.textContent = @json(__('Settings saved.'));
        } catch (error) {
            if (feedback) feedback.textContent = error.message;
        } finally {
            if (saveButton) saveButton.disabled = false;
            renderState();
        }
    };

    toggleButton?.addEventListener('click', () => save(!enabled));
    saveButton?.addEventListener('click', () => save(enabled));
});
</script>
@endsection