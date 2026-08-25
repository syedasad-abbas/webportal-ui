<aside
    :class="sidebarToggle ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="connectpro-sidebar fixed inset-y-0 left-0 z-50 flex w-[264px] shrink-0 flex-col border-r border-slate-200 bg-white text-slate-800 transition-all duration-300 dark:border-[#24384c] dark:bg-[#071526] dark:text-white lg:static"
    id="appSidebar"
>
    <div class="flex h-[90px] items-center gap-3 px-5">
        <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-600/20">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
            </span>
            <span class="min-w-0">
                <span class="block truncate text-xl font-bold tracking-tight text-white">Connect<span class="text-blue-500">Pro</span></span>
            </span>
        </a>
        <button type="button" class="ml-auto rounded-lg p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 lg:hidden" @click="sidebarToggle = false" aria-label="Close navigation">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto px-3 py-5 scrollbar-thin">
        @include('backend.layouts.partials.sidebar-menu')
    </div>

    <div class="border-t border-slate-200 p-4 dark:border-[#24384c]">
        <button id="sidebarDarkModeToggle" type="button" @click.prevent="toggleTheme()" :aria-pressed="darkMode.toString()" class="mb-4 flex w-full items-center justify-between rounded-xl px-2 py-2 text-sm text-slate-600 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-white/5">
            <span class="flex items-center gap-3"><i class="bi text-xl" :class="darkMode ? 'bi-moon-stars' : 'bi-sun'"></i><span x-text="darkMode ? '{{ __('Dark Mode') }}' : '{{ __('Light Mode') }}'"></span></span>
            <span class="relative h-6 w-11 rounded-full transition-colors" :class="darkMode ? 'bg-blue-600' : 'bg-slate-300'"><span class="absolute top-1 h-4 w-4 rounded-full bg-white shadow transition-all" :class="darkMode ? 'left-6' : 'left-1'"></span></span>
        </button>
        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-xl p-2 transition hover:bg-slate-100 dark:hover:bg-white/5">
            <img src="{{ auth()->user()->getGravatarUrl() }}" alt="" class="h-9 w-9 rounded-full object-cover ring-2 ring-blue-500/20">
            <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-semibold text-gray-800 dark:text-gray-100">{{ auth()->user()->name }}</span>
                <span class="flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>{{ __('Available') }}
                </span>
            </span>
        </a>
        <p class="mt-2 truncate px-2 text-[11px] text-gray-400">{{ auth()->user()->email }}</p>
    </div>
</aside>
