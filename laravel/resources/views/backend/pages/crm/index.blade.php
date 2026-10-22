@extends('backend.layouts.app')
@section('title', __('CRM') . ' | ' . config('app.name'))
@section('admin-content')
<div id="crm-console" class="p-6 space-y-4" data-url="{{ url('/admin/crm/api') }}">
    <h1 class="text-2xl font-semibold">{{ __('CRM API workspace') }}</h1>
    <p>{{ __('Manage customer records through the configured CRM API. The test CRM uses separate data from dialer contacts.') }}</p>
    <a href="{{ route('admin.dialer.index') }}" class="underline">{{ __('Back to dialer') }}</a>
    <p data-crm-message role="status"></p>
    <div class="flex flex-wrap gap-3"><input data-crm-search type="tel" placeholder="{{ __('Exact phone lookup') }}" class="border rounded p-2"><button data-crm-load type="button" class="btn-primary">{{ __('Search / Refresh') }}</button></div>
    <div data-crm-summary class="border rounded p-4"></div>
    <div class="overflow-x-auto"><table class="w-full text-left"><thead><tr><th>{{ __('Name') }}</th><th>{{ __('Phone') }}</th><th>{{ __('Status') }}</th><th>{{ __('Value') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody data-crm-rows></tbody></table></div>
    <div class="flex gap-3"><button data-crm-prev type="button">{{ __('Previous') }}</button><span data-crm-page></span><button data-crm-next type="button">{{ __('Next') }}</button></div>
    @if(auth()->user()->can('contacts.create') || auth()->user()->can('contacts.edit'))
    <form data-crm-form class="grid gap-3 max-w-xl">
        <h2 class="text-lg font-semibold">{{ __('Create / edit customer') }}</h2>
        <input name="id" type="hidden">
        <label>{{ __('Name') }} <input name="name" required maxlength="200" class="border rounded p-2 w-full"></label>
        <label>{{ __('Phone with country code') }} <input name="phone" required type="tel" maxlength="30" class="border rounded p-2 w-full"></label>
        <label>{{ __('Email') }} <input name="email" type="email" maxlength="255" class="border rounded p-2 w-full"></label>
        <label>{{ __('Company') }} <input name="company" maxlength="200" class="border rounded p-2 w-full"></label>
        <label>{{ __('Status') }} <select name="status" class="border rounded p-2 w-full"><option value="new">New</option><option value="qualified">Qualified</option><option value="won">Won</option><option value="lost">Lost</option></select></label>
        <label>{{ __('Lead value') }} <input name="value" type="number" min="0" max="9999999999.99" step="0.01" value="0" required class="border rounded p-2 w-full"></label>
        <label>{{ __('Notes') }} <textarea name="notes" maxlength="5000" class="border rounded p-2 w-full"></textarea></label>
        <button type="submit" class="btn-primary">{{ __('Save to CRM') }}</button>
        <button type="reset">{{ __('Clear / New record') }}</button>
    </form>
    @endif
    <span data-crm-permissions data-edit="{{ auth()->user()->can('contacts.edit') ? '1' : '0' }}" data-delete="{{ auth()->user()->can('contacts.delete') ? '1' : '0' }}"></span>
</div>
@endsection
@push('scripts')<script src="{{ asset('js/crm-console.js') }}" defer></script>@endpush
