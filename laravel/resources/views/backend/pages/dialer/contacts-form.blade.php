@extends('backend.layouts.app')

@php($editing = isset($contact) && $contact)

@section('title', ($editing ? __('Edit contact') : __('Add contact')) . ' | ' . config('app.name'))

@push('styles')
    @include('backend.pages.dialer.nightwave-form-styles')
@endpush

@section('admin-content')
<div class="connectpro-communication-page min-h-full bg-[#06111f] text-white">
    @include('backend.pages.dialer.contacts-header', [
        'title' => $editing ? __('Edit contact') : __('Add contact'),
        'subtitle' => __('Customer record and ownership'),
        'showAddContact' => false,
    ])

    <div class="connectpro-admin-page connectpro-record-form-page p-4 md:p-6">
        <x-messages />

        <div class="connectpro-record-form-layout">
            <section class="connectpro-record-form-card">
                <div class="connectpro-record-form-body">
                    <h2 class="connectpro-record-form-heading">{{ __('Details') }}</h2>

                    <form method="POST" action="{{ $editing ? route('admin.contacts.update', $contact) : route('admin.contacts.store') }}" class="space-y-6">
                        @csrf
                        @if($editing) @method('PUT') @endif

                        <div class="connectpro-record-form-fields">
                            <div>
                                <label for="name" class="mb-2 block">{{ __('Name') }} *</label>
                                <input id="name" name="name" type="text" required maxlength="255" value="{{ old('name', $contact?->name) }}" placeholder="{{ __('Contact name') }}" class="h-11 px-4 text-sm">
                                @error('name')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="company" class="mb-2 block">{{ __('Company') }}</label>
                                <input id="company" name="company" type="text" maxlength="255" value="{{ old('company', $contact?->company) }}" placeholder="{{ __('Company') }}" class="h-11 px-4 text-sm">
                                @error('company')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="phone" class="mb-2 block">{{ __('Phone') }} *</label>
                                <input id="phone" name="phone" type="tel" required maxlength="40" value="{{ old('phone', $contact?->phone) }}" placeholder="{{ __('Phone number') }}" class="h-11 px-4 text-sm">
                                @error('phone')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="email" class="mb-2 block">{{ __('Email') }}</label>
                                <input id="email" name="email" type="email" maxlength="255" value="{{ old('email', $contact?->email) }}" placeholder="{{ __('contact@example.com') }}" class="h-11 px-4 text-sm">
                                @error('email')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="avatar_url" class="mb-2 block">{{ __('Profile image URL') }}</label>
                                <input id="avatar_url" name="avatar_url" type="url" maxlength="2048" value="{{ old('avatar_url', $contact?->avatar_url) }}" placeholder="https://example.com/avatar.jpg" class="h-11 px-4 text-sm">
                                @error('avatar_url')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="connectpro-record-form-actions">
                            <a href="{{ $editing ? route('admin.contacts.show', $contact) : route('admin.contacts.index') }}" class="btn-default">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn-primary">{{ $editing ? __('Save changes') : __('Add contact') }}</button>
                        </div>
                    </form>
                </div>
            </section>

            <aside class="connectpro-record-form-context">
                <span class="connectpro-record-context-icon"><i class="bi bi-shield-check"></i></span>
                <h2>{{ __('Context & permissions') }}</h2>
                <p class="mt-4">{{ __('Changes are recorded in the contact activity history.') }}</p>
                <p class="mt-2">{{ __('Your existing role permissions continue to control who can create or edit contacts.') }}</p>
                <p class="mt-2">{{ __('Call notes, labels and flags are managed from the contact workspace.') }}</p>
            </aside>
        </div>
    </div>
</div>
@endsection
