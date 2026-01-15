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
                    <form action="{{ route('admin.users.update', $user->id) }}" method="POST" class="space-y-6" enctype="multipart/form-data">
                        @method('PUT')
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
                                    value="{{ old('fullName', $user->full_name ?? $user->external_name ?? $user->name) }}"
                                    placeholder="{{ __('Jane Smith') }}"
                                    @if(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support')) readonly @endif
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
                                    value="{{ old('email', $user->email) }}"
                                    placeholder="{{ __('user@example.com') }}"
                                    @if(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support')) readonly @endif
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- Password (optional) --}}
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Password (Optional)') }}
                                </label>
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
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
                                    @if(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support')) disabled @endif
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >
                                    <option value="">{{ __('Default') }}</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group['id'] }}"
                                            {{ old('groupId', $user->group_id ?? null) == $group['id'] ? 'selected' : '' }}>
                                            {{ $group['name'] }}
                                        </option>
                                    @endforeach
                                </select>

                                {{-- keep value if disabled --}}
                                @if(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support'))
                                    <input type="hidden" name="groupId" value="{{ old('groupId', $user->group_id ?? '') }}">
                                @endif
                            </div>

                            {{-- Carrier --}}
                            <div>
                                <label for="carrierId" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Carrier') }}
                                </label>
                                <select
                                    name="carrierId"
                                    id="carrierId"
                                    @if(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support')) disabled @endif
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >
                                    <option value="">{{ __('Default') }}</option>
                                    @foreach($carriers as $carrier)
                                        <option value="{{ $carrier['id'] }}"
                                            {{ old('carrierId', $user->carrier_id ?? null) == $carrier['id'] ? 'selected' : '' }}>
                                            {{ $carrier['name'] }}
                                        </option>
                                    @endforeach
                                </select>

                                @if(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support'))
                                    <input type="hidden" name="carrierId" value="{{ old('carrierId', $user->carrier_id ?? '') }}">
                                @endif
                            </div>

                            {{-- Recording --}}
                            <div>
                                <label for="recording_enabled" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Recording') }}
                                </label>
                                <select
                                    name="recording_enabled"
                                    id="recording_enabled"
                                    @if(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support')) disabled @endif
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                >
                                    <option value="1" {{ old('recording_enabled', (int)($user->recording_enabled ?? 0)) == 1 ? 'selected' : '' }}>{{ __('Enabled') }}</option>
                                    <option value="0" {{ old('recording_enabled', (int)($user->recording_enabled ?? 0)) == 0 ? 'selected' : '' }}>{{ __('Disabled') }}</option>
                                </select>

                                @if(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support'))
                                    <input type="hidden" name="recording_enabled" value="{{ old('recording_enabled', (int)($user->recording_enabled ?? 0)) }}">
                                @endif
                            </div>

                            {!! ld_apply_filters('after_username_field', '', $user) !!}
                        </div>

                        <div class="mt-6 flex justify-start gap-4">
                            <button type="submit" class="btn-primary">{{ __('Save') }}</button>
                            <a href="{{ route('admin.users.index') }}" class="btn-default">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
