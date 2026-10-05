@extends('backend.layouts.app')

@section('title')
    {{ $breadcrumbs['title'] }} | {{ config('app.name') }}
@endsection

@push('styles')
    @include('backend.pages.dialer.nightwave-form-styles')
@endpush

@section('admin-content')
<div class="connectpro-admin-page connectpro-record-form-page p-4 md:p-6">
    <x-breadcrumbs :breadcrumbs="$breadcrumbs" />
    <div class="connectpro-record-form-layout">
        <div class="connectpro-record-form-card rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="connectpro-record-form-body p-5 sm:p-6">
                <h2 class="connectpro-record-form-heading">{{ __('Details') }}</h2>
                <form method="POST" action="{{ route('admin.carrier.inbound-dids.update', $inboundDid) }}">
                    @csrf
                    @method('PUT')
                    @include('backend.pages.carrier.inbound-dids._form')
                </form>
            </div>
        </div>
        <aside class="connectpro-record-form-context">
            <span class="connectpro-record-context-icon"><i class="bi bi-telephone-inbound"></i></span>
            <h2>{{ __('Inbound numbers') }}</h2>
            <p class="mt-4">{{ __('Each DID is offered to inbound callers and routed to this portal.') }}</p>
            <p class="mt-2">{{ __('Numbers are stored as digits; formatting and a leading + are accepted.') }}</p>
            <p class="mt-2">{{ __('Deactivate a DID to stop accepting calls without deleting its history.') }}</p>
        </aside>
    </div>
</div>
@endsection
