@extends('backend.layouts.app')

@section('title')
    {{ $breadcrumbs['title'] }} | {{ config('app.name') }}
@endsection

@section('admin-content')
<div class="connectpro-admin-page p-4 mx-auto max-w-7xl md:p-6">
    <x-breadcrumbs :breadcrumbs="$breadcrumbs" />
    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <form method="POST" action="{{ route('admin.carrier.inbound-dids.update', $inboundDid) }}" class="p-5 sm:p-6">
            @csrf
            @method('PUT')
            @include('backend.pages.carrier.inbound-dids._form')
        </form>
    </div>
</div>
@endsection
