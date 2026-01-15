@extends('backend.layouts.app')

@section('title')
    {{ $breadcrumbs['title'] }} | {{ config('app.name') }}
@endsection

@section('admin-content')
    <div class="p-4 mx-auto max-w-7xl md:p-6">
        <x-breadcrumbs :breadcrumbs="$breadcrumbs" />

        {!! ld_apply_filters('users_after_breadcrumbs', '') !!}

        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="p-5 space-y-6 border-t border-gray-100 dark:border-gray-800 sm:p-6">
                    <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            {{-- Full Name --}}
                            <div>
                                <label for="fullName" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Full Name') }}
                                </label>
                                <input
                                    type="text"
                                    name="fullName"
                                    id="fullName"
                                    required
                                    autofocus
                                    value="{{ old('fullName') }}"
                                    placeholder="{{ __('Jane Smith') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- Email --}}
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Email') }}
                                </label>
                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    required
                                    value="{{ old('email') }}"
                                    placeholder="{{ __('user@example.com') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- Password --}}
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Password') }}
                                </label>
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    required
                                    placeholder="{{ __('Temporary password') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- Group --}}
                            <div>
                                <label for="groupId" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Group') }}
                                </label>
                                <select
                                    name="groupId"
                                    id="groupId"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >
                                    <option value="">{{ __('Default') }}</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group['id'] }}" {{ old('groupId') == $group['id'] ? 'selected' : '' }}>
                                            {{ $group['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Carrier --}}
                            <div>
                                <label for="carrierId" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Carrier') }}
                                </label>
                                <select
                                    name="carrierId"
                                    id="carrierId"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >
                                    <option value="">{{ __('Default') }}</option>
                                    @foreach($carriers as $carrier)
                                        <option value="{{ $carrier['id'] }}" {{ old('carrierId') == $carrier['id'] ? 'selected' : '' }}>
                                            {{ $carrier['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-start gap-4">
                            <button type="submit" class="btn-primary">{{ __('Create user') }}</button>
                            <a href="{{ route('admin.dashboard') }}" class="btn-default">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
