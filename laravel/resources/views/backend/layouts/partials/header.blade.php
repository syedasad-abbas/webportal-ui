<header id="appHeader" class="sticky top-0 z-40 flex h-[76px] w-full items-center border-b border-gray-200 bg-white/90 px-4 backdrop-blur-xl dark:border-gray-800 dark:bg-[#0b1120]/90 sm:px-6">
    <button type="button" class="mr-3 flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 text-gray-500 transition hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 lg:hidden" @click.stop="sidebarToggle = true" aria-label="Open navigation">
        <i class="bi bi-list text-xl"></i>
    </button>

    <div class="min-w-0 flex-1">
    </div>

    <div class="flex items-center gap-2">
        @include('backend.layouts.partials.demo-mode-notice')
        <button id="darkModeToggle" type="button"
            class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-500 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            @click.prevent="toggleTheme()" :aria-pressed="darkMode.toString()" aria-label="Toggle theme">
            <i class="bi bi-sun hidden text-lg dark:block"></i>
            <i class="bi bi-moon-stars text-lg dark:hidden"></i>
        </button>

        <div class="relative" x-data="{ dropdownOpen: false }" @click.outside="dropdownOpen = false">
            <button type="button" @click="dropdownOpen = !dropdownOpen" class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white p-1.5 pr-3 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:hover:bg-gray-700">
                <img src="{{ auth()->user()->getGravatarUrl() }}" alt="" class="h-8 w-8 rounded-lg object-cover">
                <span class="hidden max-w-32 truncate text-sm font-medium text-gray-700 dark:text-gray-200 sm:block">{{ auth()->user()->name }}</span>
                <i class="bi bi-chevron-down text-xs text-gray-400"></i>
            </button>

            <div x-show="dropdownOpen" x-transition class="absolute right-0 mt-2 w-64 rounded-2xl border border-gray-200 bg-white p-2 shadow-2xl dark:border-gray-700 dark:bg-gray-800" style="display:none">
                <div class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                    <p class="truncate text-sm font-semibold text-gray-800 dark:text-white">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-gray-400">{{ auth()->user()->email }}</p>
                </div>
                <a href="{{ route('profile.edit') }}" class="mt-2 flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                    <i class="bi bi-person"></i>{{ __('Edit profile') }}
                </a>
                @if (session()->has('original_user_id'))
                    @php($originalUser = \App\Models\User::find(session('original_user_id')))
                    @if ($originalUser)
                        <form method="POST" action="{{ route('admin.users.switch-back') }}">@csrf
                            <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                <i class="bi bi-arrow-left"></i>{{ __('Switch back to') }} {{ $originalUser->name }}
                            </button>
                        </form>
                    @endif
                @endif
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">
                        <i class="bi bi-box-arrow-right"></i>{{ __('Logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
