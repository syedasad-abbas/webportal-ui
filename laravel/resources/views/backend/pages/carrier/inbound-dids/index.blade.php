@extends('backend.layouts.app')

@section('title')
    {{ $breadcrumbs['title'] }} | {{ config('app.name') }}
@endsection

@section('admin-content')
<div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
    <x-breadcrumbs :breadcrumbs="$breadcrumbs" />

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-5">
            <div>
                <h3 class="text-base font-medium text-gray-800 dark:text-white/90">{{ __('Inbound DIDs') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Only active numbers in this list are accepted and routed to online agents.') }}
                </p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                @include('backend.partials.search-form', ['placeholder' => __('Search DID, label, or carrier')])
                <a href="{{ route('admin.carrier.inbound-dids.create') }}" class="btn-primary whitespace-nowrap">
                    {{ __('Add Inbound DID') }}
                </a>
            </div>
        </div>

        <div class="overflow-x-auto border-t border-gray-100 dark:border-gray-800">
            <table class="w-full dark:text-gray-400">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-800">
                        <th class="px-5 py-3 text-left dark:text-white">{{ __('DID') }}</th>
                        <th class="px-5 py-3 text-left dark:text-white">{{ __('Label') }}</th>
                        <th class="px-5 py-3 text-left dark:text-white">{{ __('Carrier') }}</th>
                        <th class="px-5 py-3 text-left dark:text-white">{{ __('Status') }}</th>
                        <th class="px-5 py-3 text-right dark:text-white">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dids as $did)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-5 py-4 font-medium text-gray-800 dark:text-white/90">+{{ $did->did }}</td>
                            <td class="px-5 py-4">{{ $did->label ?: '—' }}</td>
                            <td class="px-5 py-4">{{ $did->carrier?->name ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $did->is_active ? 'bg-green-100 text-green-800 dark:bg-green-500/30 dark:text-green-100' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">
                                    {{ $did->is_active ? __('Active') : __('Inactive') }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('admin.carrier.inbound-dids.edit', $did) }}" class="text-brand-600 hover:underline">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('admin.carrier.inbound-dids.destroy', $did) }}" onsubmit="return confirm('{{ __('Delete this inbound DID? Calls to it will be rejected.') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-gray-500 dark:text-gray-400">
                                {{ __('No inbound DIDs configured. Add your carrier-provided DID to receive calls.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 sm:px-6">{{ $dids->links() }}</div>
    </div>
</div>
@endsection
