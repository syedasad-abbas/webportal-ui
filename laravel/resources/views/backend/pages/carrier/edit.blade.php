@extends('backend.layouts.app')

@section('title')
    {{ $breadcrumbs['title'] }} | {{ config('app.name') }}
@endsection

@section('admin-content')
@php
    abort_unless(auth()->check() && auth()->user()->hasRole('Admin'), 403);
@endphp
<div class="p-4 mx-auto max-w-7xl md:p-6">
    <x-breadcrumbs :breadcrumbs="$breadcrumbs" />

    {!! ld_apply_filters('carriers_edit_after_breadcrumbs', '', $carrier) !!}

    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="p-5 space-y-6 border-t border-gray-100 dark:border-gray-800 sm:p-6"
                 x-data="{ registrationRequired: {{ old('registrationRequired', !empty($carrier['registration_required'])) ? 'true' : 'false' }} }">

                <form method="POST" action="{{ route('admin.carriers.update', $carrier['id']) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        {{-- Name --}}
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ __('Name') }} *
                            </label>
                            <input type="text" name="name" id="name" required
                                   value="{{ old('name', $carrier['name'] ?? '') }}"
                                   class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        </div>

                        {{-- Default Caller ID --}}
                        <div>
                            <label for="callerId" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ __('Default Caller ID') }}
                            </label>
                            <input type="text" name="callerId" id="callerId"
                                   value="{{ old('callerId', $carrier['default_caller_id'] ?? '') }}"
                                   class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        </div>

                        {{-- Requires Caller ID --}}
                        <div class="sm:col-span-2">
                            <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-400">
                                <input type="hidden" name="callerIdRequired" value="0">
                                <input type="checkbox" name="callerIdRequired" value="1"
                                       {{ old('callerIdRequired', !empty($carrier['caller_id_required']) ? '1' : '0') === '1' ? 'checked' : '' }}
                                       class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <span>{{ __('Requires Caller ID') }}</span>
                            </label>
                        </div>

                        {{-- Prefix --}}
                        <div>
                            <label for="prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ __('Prefix') }} ({{ __('optional') }})
                            </label>
                            <input type="text" name="prefix" id="prefix"
                                   value="{{ old('prefix') }}"
                                   placeholder="{{ __('e.g. 100') }}"
                                   class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        </div>

                        {{-- Transport --}}
                        <div>
                            <label for="transport" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ __('Transport') }}
                            </label>
                            @php($selectedTransport = old('transport', $carrier['transport'] ?? 'udp'))
                            <select name="transport" id="transport"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="udp" {{ $selectedTransport === 'udp' ? 'selected' : '' }}>UDP</option>
                                <option value="tcp" {{ $selectedTransport === 'tcp' ? 'selected' : '' }}>TCP</option>
                                <option value="tls" {{ $selectedTransport === 'tls' ? 'selected' : '' }}>TLS</option>
                            </select>
                        </div>

                        {{-- ✅ NEW: Outbound Proxy --}}
                        <div>
                            <label for="outboundProxy" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ __('Outbound Proxy') }} ({{ __('optional') }})
                            </label>
                            <input type="text" name="outboundProxy" id="outboundProxy"
                                   value="{{ old('outboundProxy', $carrier['outbound_proxy'] ?? '') }}"
                                   placeholder="{{ __('proxy.provider.com:5060') }}"
                                   class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        </div>

                        {{-- Domain / IP --}}
                        <div>
                            <label for="sipDomain" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ __('Domain / IP') }} *
                            </label>
                            <input type="text" name="sipDomain" id="sipDomain" required
                                   value="{{ old('sipDomain', $carrier['sip_domain'] ?? '') }}"
                                   class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        </div>

                        {{-- Port --}}
                        <div>
                            <label for="sipPort" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                {{ __('Port') }} *
                            </label>
                            <input type="number" name="sipPort" id="sipPort" required min="1" max="65535"
                                   value="{{ old('sipPort', $carrier['sip_port'] ?? 5062) }}"
                                   class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        </div>

                        {{-- Requires Registration --}}
                        <div class="sm:col-span-2">
                            <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-400">
                                <input type="checkbox" name="registrationRequired" value="1"
                                       x-model="registrationRequired"
                                       {{ old('registrationRequired', !empty($carrier['registration_required']) ? '1' : '') ? 'checked' : '' }}
                                       class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <span>{{ __('Requires Registration') }}</span>
                            </label>
                        </div>

                        {{-- Registration fields --}}
                        <div class="sm:col-span-2" x-show="registrationRequired" x-cloak>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label for="registrationUsername" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        {{ __('Registration Username') }}
                                    </label>
                                    <input type="text" name="registrationUsername" id="registrationUsername"
                                           value="{{ old('registrationUsername', $carrier['registration_username'] ?? '') }}"
                                           class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                                </div>
                                <div>
                                    <label for="registrationPassword" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                        {{ __('Registration Password') }}
                                    </label>
                                    <input type="password" name="registrationPassword" id="registrationPassword"
                                           value="{{ old('registrationPassword') }}"
                                           placeholder="{{ __('Leave blank to keep current') }}"
                                           class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-start gap-4">
                        <button type="submit" class="btn-primary">{{ __('Save') }}</button>
                        <a href="{{ route('admin.carriers.index') }}" class="btn-default">{{ __('Cancel') }}</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection
