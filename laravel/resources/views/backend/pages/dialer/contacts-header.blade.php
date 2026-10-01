@php
    $isDialerPage = request()->routeIs('admin.dialer.index', 'admin.calls.dialing', 'admin.calls.in_call');
    $activeTab = $isDialerPage ? 'dialpad' : (request()->routeIs('admin.contacts.reports') ? 'reports' : (request()->routeIs('admin.contacts.call-history') ? 'history' : (request()->routeIs('admin.contacts.activity') ? 'activity' : 'contacts')));
@endphp

@once
@push('styles')
<style>
@media (min-width: 768px) {
    .connectpro-dialer-toolbar { min-height: 58px; border-color: #1d2d42; padding-right: 1.25rem; padding-left: 1.25rem; }
    .connectpro-reference-nav { display: flex !important; }
    .connectpro-agent-status { display: inline-flex !important; }
    .connectpro-reference-nav a { display: inline-flex; align-items: center; min-height: 32px; padding: 0 .9rem; border-radius: 8px; color: #8ea0b8; font-size: .65rem; font-weight: 600; }
    .connectpro-reference-nav a:hover, .connectpro-reference-nav .connectpro-reference-nav-active { background: #1b3154; color: #f8fafc; }
    .connectpro-agent-status { background: #064e3b; color: #34d399; }
}
html:not(.dark) .connectpro-dialer-toolbar { background: #f3f4f6 !important; border-color: #e5e7eb !important; }
html:not(.dark) .connectpro-reference-nav a { color: #4b5563 !important; }
html:not(.dark) .connectpro-reference-nav a:hover, html:not(.dark) .connectpro-reference-nav .connectpro-reference-nav-active { background: #e5e7eb !important; color: #111827 !important; }
html:not(.dark) .connectpro-agent-status { background: #d1fae5 !important; color: #065f46 !important; }
.connectpro-dialer-toolbar [x-cloak] { display: none !important; }
.connectpro-dialer-toolbar [x-show] { transition: opacity .2s ease, transform .2s ease; }
</style>
@endpush
@endonce

<div class="connectpro-dialer-toolbar" x-data="{ mobileToolbarOpen: false }">
        <div class="flex min-h-[82px] items-center gap-4 border-b border-gray-200 dark:border-[#20364c] bg-white dark:bg-[#06111f] px-3 sm:px-6">
            <button type="button" @click.stop="sidebarToggle = true" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-gray-300 bg-gray-100 text-gray-700 dark:border-[#2a4055] dark:bg-[#091827] dark:text-slate-200 lg:hidden" aria-label="{{ __('Open navigation') }}"><i class="bi bi-list text-2xl"></i></button>
            <nav class="connectpro-reference-nav hidden items-center gap-2 lg:flex" aria-label="{{ __('Dialer navigation') }}">
                <a href="{{ route('admin.contacts.index') }}" class="{{ $activeTab === 'contacts' ? 'connectpro-reference-nav-active text-gray-900 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-slate-300 dark:hover:text-white' }}">{{ __('Contacts') }}</a>
                <a class="{{ $activeTab === 'dialpad' ? 'connectpro-reference-nav-active text-gray-900 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-slate-300 dark:hover:text-white' }}" href="{{ $isDialerPage ? '#' : route('admin.dialer.index') }}">{{ __('Dialpad') }}</a>
                <a href="{{ route('admin.contacts.call-history') }}" class="{{ $activeTab === 'history' ? 'connectpro-reference-nav-active text-gray-900 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-slate-300 dark:hover:text-white' }}">{{ __('History') }}</a>
                <a data-dialer-activity href="{{ auth()->user()->can('dialer.create_call') ? route('admin.dialer.index', ['tab' => 'activity']) : route('admin.contacts.activity') }}" class="{{ $activeTab === 'activity' ? 'connectpro-reference-nav-active text-gray-900 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-slate-300 dark:hover:text-white' }}">{{ __('Activity') }}</a>
                <a href="{{ route('admin.contacts.reports') }}" class="{{ $activeTab === 'reports' ? 'connectpro-reference-nav-active text-gray-900 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-slate-300 dark:hover:text-white' }}">{{ __('Reports') }}</a>
            </nav>
            <div class="ml-auto flex items-center gap-2">
                <button type="button" @click="mobileToolbarOpen = !mobileToolbarOpen" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-gray-300 bg-gray-100 text-gray-700 dark:border-[#2a4055] dark:bg-[#091827] dark:text-slate-200 lg:hidden" aria-label="{{ __('Toggle navigation') }}">
                    <i class="bi bi-grid text-2xl" x-show="!mobileToolbarOpen"></i>
                    <i class="bi bi-x text-2xl" x-show="mobileToolbarOpen" x-cloak></i>
                </button>
                <span class="connectpro-agent-status hidden items-center rounded-full px-3 py-1 text-[10px] font-semibold lg:inline-flex bg-emerald-100 text-emerald-700 dark:bg-[#064e3b] dark:text-emerald-400">{{ __('Agent online') }}</span>
                <a href="{{ route('admin.contacts.index') }}" class="flex h-11 w-11 items-center justify-center rounded-xl border border-gray-300 bg-gray-100 text-blue-600 hover:border-blue-500 dark:border-[#2a4055] dark:bg-[#0b1b2c] dark:text-blue-400" title="{{ __('Contacts') }}"><i class="bi bi-people-fill text-lg"></i></a>
                <a href="{{ route('admin.settings.index') }}" class="flex h-11 w-11 items-center justify-center rounded-xl border border-gray-300 bg-gray-100 text-gray-600 hover:border-blue-500 hover:text-blue-600 dark:border-[#2a4055] dark:bg-[#0b1b2c] dark:text-slate-300 dark:hover:border-blue-500 dark:hover:text-blue-400" title="{{ __('Settings') }}"><i class="bi bi-gear-fill text-lg"></i></a>
            </div>
        </div>
        <div x-show="mobileToolbarOpen" x-cloak class="border-b border-gray-200 dark:border-[#20364c] bg-white dark:bg-[#06111f] px-3 pb-3 pt-2 lg:hidden">
            <nav class="flex flex-wrap items-center gap-2" aria-label="{{ __('Dialer navigation') }}">
                <a href="{{ route('admin.contacts.index') }}" class="flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold {{ $activeTab === 'contacts' ? 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-300' : 'border-gray-300 bg-gray-100 text-gray-700 hover:border-blue-500 hover:text-blue-600 dark:border-[#2a4055] dark:bg-[#0b1b2c] dark:text-slate-200 dark:hover:border-blue-500 dark:hover:text-blue-400' }}">{{ __('Contacts') }}</a>
                <a href="{{ $isDialerPage ? '#' : route('admin.dialer.index') }}" class="flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold {{ $activeTab === 'dialpad' ? 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-300' : 'border-gray-300 bg-gray-100 text-gray-700 hover:border-blue-500 hover:text-blue-600 dark:border-[#2a4055] dark:bg-[#0b1b2c] dark:text-slate-200 dark:hover:border-blue-500 dark:hover:text-blue-400' }}">{{ __('Dialpad') }}</a>
                <a href="{{ route('admin.contacts.call-history') }}" class="flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold {{ $activeTab === 'history' ? 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-300' : 'border-gray-300 bg-gray-100 text-gray-700 hover:border-blue-500 hover:text-blue-600 dark:border-[#2a4055] dark:bg-[#0b1b2c] dark:text-slate-200 dark:hover:border-blue-500 dark:hover:text-blue-400' }}">{{ __('History') }}</a>
                <a data-dialer-activity href="{{ auth()->user()->can('dialer.create_call') ? route('admin.dialer.index', ['tab' => 'activity']) : route('admin.contacts.activity') }}" class="flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold {{ $activeTab === 'activity' ? 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-300' : 'border-gray-300 bg-gray-100 text-gray-700 hover:border-blue-500 hover:text-blue-600 dark:border-[#2a4055] dark:bg-[#0b1b2c] dark:text-slate-200 dark:hover:border-blue-500 dark:hover:text-blue-400' }}">{{ __('Activity') }}</a>
                <a href="{{ route('admin.contacts.reports') }}" class="flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold {{ $activeTab === 'reports' ? 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-300' : 'border-gray-300 bg-gray-100 text-gray-700 hover:border-blue-500 hover:text-blue-600 dark:border-[#2a4055] dark:bg-[#0b1b2c] dark:text-slate-200 dark:hover:border-blue-500 dark:hover:text-blue-400' }}">{{ __('Reports') }}</a>
            </nav>
        </div>
    </div>
