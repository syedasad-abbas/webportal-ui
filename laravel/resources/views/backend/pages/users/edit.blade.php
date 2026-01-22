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

                            {{-- External Name --}}
                            <div>
                                <label for="external_name" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('External Name') }}
                                </label>
                                <input
                                    type="text"
                                    name="external_name"
                                    id="external_name"
                                    required
                                    value="{{ old('external_name', $user->external_name ?? '') }}"
                                    placeholder="{{ __('Displayed to others') }}"
                                    @if(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support')) readonly @endif
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                                @if(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support'))
                                    <input type="hidden" name="external_name" value="{{ old('external_name', $user->external_name ?? '') }}">
                                @endif
                            </div>

                            {{-- Internal Name --}}
                            <div>
                                <label for="internal_name" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Internal Name') }}
                                </label>
                                <input
                                    type="text"
                                    name="internal_name"
                                    id="internal_name"
                                    required
                                    value="{{ old('internal_name', $user->internal_name ?? '') }}"
                                    placeholder="{{ __('For internal reference') }}"
                                    @if(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support')) readonly @endif
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                                @if(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support'))
                                    <input type="hidden" name="internal_name" value="{{ old('internal_name', $user->internal_name ?? '') }}">
                                @endif
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

                            {{-- Confirm Password (optional; required only if password filled in backend validation) --}}
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Confirm Password (Optional)') }}
                                </label>
                                <input
                                    type="password"
                                    name="password_confirmation"
                                    id="password_confirmation"
                                    placeholder="{{ __('Re-enter password') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
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

                            {{-- SIP Username --}}
                            <div>
                                <label for="sip_username" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('SIP Username') }}
                                </label>
                                <input
                                    type="text"
                                    name="sip_username"
                                    id="sip_username"
                                    value="{{ old('sip_username', $user->sipCredential->sip_username ?? '') }}"
                                    placeholder="{{ __('e.g. 1001') }}"
                                    @if(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support')) readonly @endif
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                                @if(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support'))
                                    <input type="hidden" name="sip_username" value="{{ old('sip_username', $user->sipCredential->sip_username ?? '') }}">
                                @endif
                            </div>

                            {{-- SIP Password --}}
                            <div>
                                <label for="sip_password" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('SIP Password') }}
                                </label>
                                <input
                                    type="password"
                                    name="sip_password"
                                    id="sip_password"
                                    placeholder="{{ __('Leave blank to keep current') }}"
                                    @if(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support')) readonly @endif
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                                @if(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support'))
                                    <input type="hidden" name="sip_password" value="">
                                @endif
                            </div>

                            {{-- Roles --}}
                            @if (!(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support')))
                                <div>
                                    <x-inputs.combobox
                                        name="roles[]"
                                        label="{{ __('Assign Roles') }}"
                                        placeholder="{{ __('Select Roles') }}"
                                        :options="collect($roles)->map(fn($name, $id) => ['value' => $name, 'label' => ucfirst($name)])->values()->toArray()"
                                        :selected="$user->roles->pluck('name')->toArray()"
                                        :multiple="true"
                                        :searchable="false"
                                    />
                                </div>
                            @else
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Assigned Role') }}</label>
                                    <input type="text" readonly
                                        value="{{ ucfirst($user->roles->pluck('name')->first()) }}"
                                        class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                </div>
                            @endif

                            {{-- Status --}}
                            @if (!(auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support')))
                                <div>
                                    <label for="is_active" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        {{ __('Status') }}
                                    </label>
                                    <select name="is_active" id="is_active"
                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                        <option value="1" {{ (int)old('is_active', (int)$user->is_active) === 1 ? 'selected' : '' }}>{{ __('Enabled') }}</option>
                                        <option value="0" {{ (int)old('is_active', (int)$user->is_active) === 0 ? 'selected' : '' }}>{{ __('Disabled') }}</option>
                                    </select>
                                </div>
                            @else
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Status') }}</label>
                                    <input type="text" readonly
                                        value="{{ $user->is_active ? __('Enabled') : __('Disabled') }}"
                                        class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                </div>
                            @endif

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
