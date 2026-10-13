@extends('backend.layouts.app')
@section('title', __('VICIdial') . ' | ' . config('app.name'))

@section('admin-content')
@include('backend.pages.dialer.contacts-header')
<div id="vicidial-page" class="mx-auto max-w-4xl p-6">
    <h1 class="text-2xl font-semibold">{{ __('VICIdial dialer') }}</h1>
    <p class="mt-2">{{ __('Agent') }}: {{ $agentUser }}</p>
    @if($configurationError)
        <p role="alert" class="mt-4 text-red-600">{{ $configurationError }}</p>
    @else
        @if(! $apiReady)
            <p class="mt-4" role="status">{{ __('Phone test mode: connect your browser phone and accept an incoming test call. Customer call controls require VICIdial API credentials and an active agent session.') }}</p>
        @endif
        <div class="mt-4 rounded-xl border p-4">
            <p>{{ __('Connect your browser phone, then log in to VICIdial with your assigned phone and campaign. Keep that agent screen open; use this page for audio. Disable the other webphone for this extension to avoid competing registrations.') }}</p>
            @if($agentUrl)
            <a href="{{ $agentUrl }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-block text-blue-600 underline">{{ __('Open VICIdial agent login') }}</a>
            @endif
            <p class="mt-2">{{ __('Accept the agent audio connection once. Customer calls then arrive through that connection; follow customer status on the VICIdial agent screen.') }}</p>
        </div>
        <div class="mt-4 rounded-xl border p-4">
            <p id="vici-phone-status" role="status">{{ __('Browser phone disconnected') }}</p>
            <p id="vici-media-status" class="mt-2" role="status"></p>
            <div class="mt-3 flex flex-wrap gap-3">
                <button type="button" data-vici-local="register">{{ __('Connect browser phone') }}</button>
                <button type="button" data-vici-action="connect" disabled>{{ __('Request agent audio') }}</button>
                <button type="button" data-vici-local="answer" disabled>{{ __('Accept agent audio') }}</button>
                <button type="button" data-vici-local="mute" disabled>{{ __('Mute microphone') }}</button>
                <button type="button" data-vici-local="audio">{{ __('Enable audio') }}</button>
            </div>
        </div>
        <div class="mt-4 rounded-xl border p-4">
            <h2 class="font-semibold">{{ __('Customer call controls') }}</h2>
            <div class="mt-3 flex flex-wrap gap-3">
                <button type="button" data-vici-action="ready" disabled>{{ __('Ready for calls') }}</button>
                <button type="button" data-vici-action="pause">{{ __('Pause after current call') }}</button>
                <button type="button" data-vici-action="hangup">{{ __('End customer call') }}</button>
            </div>
            <p class="mt-2 text-sm">{{ __('End customer call preserves the agent audio connection. After the call ends, submit its disposition before receiving the next call.') }}</p>
            @if(count($dispositions))
                <label for="vici-disposition" class="mt-3 block">{{ __('Disposition') }}</label>
                <select id="vici-disposition" class="mt-2 rounded border p-2">
                    <option value="">{{ __('Select disposition') }}</option>
                    @foreach($dispositions as $code)<option value="{{ $code }}">{{ $code }}</option>@endforeach
                </select>
                <button type="button" data-vici-action="disposition">{{ __('Submit disposition') }}</button>
            @else
                <p class="mt-3">{{ __('Submit dispositions on the VICIdial agent screen until your administrator configures the allowed codes.') }}</p>
            @endif
            @if($leadLookup)
                <p id="vici-lead" class="mt-3" aria-live="polite"></p>
                <button type="button" data-vici-action="lead">{{ __('Refresh customer details') }}</button>
            @endif
        </div>
        <p id="vici-feedback" class="mt-4" role="status" aria-live="polite"></p>
        <audio id="dialer-audio" autoplay playsinline></audio>
        <script type="application/json" id="vicidial-config">{!! json_encode(['apiReady' => $apiReady, 'webrtc' => $webrtc, 'commandUrl' => route('admin.vicidial.command'), 'csrf' => csrf_token(), 'leadLookup' => $leadLookup], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @endif
</div>
@endsection

@push('styles')
<style>
#vicidial-page button { border: 1px solid #64748b; border-radius: .5rem; padding: .55rem .85rem; }
#vicidial-page button:disabled { opacity: .45; cursor: not-allowed; }
#vicidial-page button:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }
#vicidial-page [data-vici-action="hangup"] { color: #dc2626; }
</style>
@endpush
@push('scripts')
<script src="{{ asset('js/vicidial-dialer.js') }}" defer></script>
@endpush
