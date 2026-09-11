@extends('backend.layouts.app')

@section('title')
    {{ $breadcrumbs['title'] }} | {{ config('app.name') }}
@endsection

@push('styles')
    @include('backend.pages.dialer.nightwave-form-styles')
    <style>
        .connectpro-user-sidebar { display: grid; align-self: start; gap: 22px; min-width: 0; }
        .connectpro-user-requirements { min-height: 0; }
        .connectpro-user-requirements dl { margin: 20px 0 0; }
        .connectpro-user-requirements dl > div + div { margin-top: 18px; }
        .connectpro-user-requirements dt { color: var(--record-heading); font-size: .8rem; font-weight: 600; }
        .connectpro-user-requirements dd { margin: 4px 0 0; color: var(--record-copy); font-size: .8rem; line-height: 1.6; }
        @media (max-width: 480px) { .connectpro-user-sidebar { gap: 16px; } }
    </style>
@endpush

@section('admin-content')
    <div class="connectpro-admin-page connectpro-record-form-page p-4 md:p-6">
        <x-breadcrumbs :breadcrumbs="$breadcrumbs" />

        {!! ld_apply_filters('users_after_breadcrumbs', '') !!}

        <div class="connectpro-record-form-layout">
            <div class="connectpro-record-form-card rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="connectpro-record-form-body p-5 space-y-6 border-t border-gray-100 dark:border-gray-800 sm:p-6">
                    <h2 class="connectpro-record-form-heading">{{ __('Details') }}</h2>
                    <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div class="connectpro-record-form-fields grid grid-cols-1 gap-6 sm:grid-cols-2">

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
                                    value="{{ old('external_name') }}"
                                    placeholder="{{ __('Displayed to others') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
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
                                    value="{{ old('internal_name') }}"
                                    placeholder="{{ __('For internal reference') }}"
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

                            {{-- Confirm Password --}}
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('Confirm Password') }}
                                </label>
                                <input
                                    type="password"
                                    name="password_confirmation"
                                    id="password_confirmation"
                                    required
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

                            {{-- SIP Username --}}
                            <div>
                                <label for="sip_username" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('SIP Username') }}
                                </label>
                                <input
                                    type="text"
                                    name="sip_username"
                                    id="sip_username"
                                    value="{{ old('sip_username') }}"
                                    placeholder="{{ __('e.g. 1001') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
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
                                    value="{{ old('sip_password') }}"
                                    placeholder="{{ __('SIP secret') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- User Role --}}
                            <div>
                                <x-inputs.combobox
                                    name="roles[]"
                                    label="{{ __('Assign Roles') }}"
                                    placeholder="{{ __('Select Roles') }}"
                                    :options="collect($roles)->map(fn($name, $id) => ['value' => $name, 'label' => ucfirst($name)])->values()->toArray()"
                                    :selected="old('roles', [])"
                                    :multiple="true"
                                    :searchable="false"
                                />
                            </div>

                            {{-- Status --}}
                            <div>
                                <label for="is_active" class="block text-sm font-medium text-gray-700 dark:text-gray-400">{{ __('Status') }}</label>
                                <select name="is_active" id="is_active" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>{{ __('Enabled') }}</option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>{{ __('Disabled') }}</option>
                                </select>
                            </div>

                        </div>

                        <div class="connectpro-record-form-actions mt-6 flex justify-start gap-4">
                            <button type="submit" class="btn-primary">{{ __('Create user') }}</button>
                            <a href="{{ route('admin.dashboard') }}" class="btn-default">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="connectpro-user-sidebar">
                <aside class="connectpro-record-form-context">
                    <span class="connectpro-record-context-icon"><i class="bi bi-shield-check"></i></span>
                    <h2>{{ __('Context & permissions') }}</h2>
                    <p class="mt-4">{{ __('User access is controlled by assigned roles and status.') }}</p>
                    <p class="mt-2">{{ __('SIP credentials connect this user to the calling service.') }}</p>
                    <p class="mt-2">{{ __('Sensitive account changes remain subject to your existing permissions.') }}</p>
                </aside>
                <aside class="connectpro-record-form-context connectpro-user-requirements" aria-labelledby="user-requirements-title">
                    <span class="connectpro-record-context-icon" aria-hidden="true"><i class="bi bi-info-circle"></i></span>
                    <h2 id="user-requirements-title">{{ __('User requirements') }}</h2>
                    <dl>
                        <div>
                            <dt>{{ __('Internal name') }}</dt>
                            <dd>{{ __('Use the user’s first name for internal reference.') }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('External name') }}</dt>
                            <dd>{{ __('You can use the user’s last name as their external name.') }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('Carrier') }}</dt>
                            <dd>{{ __('Choose a configured carrier to make real calls.') }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('SIP username') }}</dt>
                            <dd>{{ __('Use numbers only, typically 4 digits in the 1000 series (for example, 1000, 1001, 1002).') }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('SIP password') }}</dt>
                            <dd>{{ __('Choose a strong, unique password with uppercase and lowercase letters, numbers, and symbols.') }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('User role') }}</dt>
                            <dd>{{ __('Each role has permissions that control what a user can access and do. Choose a role for the user, then edit that role’s permissions to limit access as needed. Changes to a role’s permissions apply to all users assigned to that role.') }}</dd>
                        </div>
                    </dl>
                </aside>
            </div>
        </div>
    </div>
@endsection
