@extends('backend.layouts.app')

@section('title', __('Call Reports') . ' | ' . config('app.name'))

@section('admin-content')
<div class="connectpro-communication-page min-h-full bg-white text-gray-900 dark:bg-[#06111f] dark:text-white">
    @include('backend.pages.dialer.contacts-header')
    <div class="mx-auto max-w-[1180px] space-y-6 p-4 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">{{ __('Call Reports') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ __('Inbound and outbound call activity for the selected dates. Defaults to this month.') }}</p>
            </div>
            <a href="{{ route('admin.dialer.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 dark:border-slate-600">{{ __('Back to Dialpad') }}</a>
        </div>
        <form action="{{ route('admin.contacts.reports') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <label class="flex flex-col gap-2 text-sm" for="report-from">{{ __('From') }}
                <input id="report-from" type="date" name="from" value="{{ old('from', $from) }}" required class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
            </label>
            <label class="flex flex-col gap-2 text-sm" for="report-to">{{ __('To') }}
                <input id="report-to" type="date" name="to" value="{{ old('to', $to) }}" required class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
            </label>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-500">{{ __('Apply') }}</button>
        </form>
        @if ($errors->any())
            <div role="alert" class="text-sm text-red-500">{{ $errors->first() }}</div>
        @endif
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([__('Total calls') => number_format($total), __('Inbound calls') => number_format($inbound), __('Outbound calls') => number_format($outbound), __('Recorded duration (h:m:s)') => sprintf('%02d:%02d:%02d', intdiv($duration, 3600), intdiv($duration % 3600, 60), $duration % 60)] as $label => $value)
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-sm text-gray-500 dark:text-slate-400">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-semibold">{{ $value }}</p>
                </div>
            @endforeach
        </div>
        <section class="overflow-hidden rounded-xl border border-gray-200 dark:border-slate-700">
            <h2 class="border-b border-gray-200 p-4 text-lg font-semibold dark:border-slate-700">{{ __('Calls by status') }}</h2>
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-slate-900"><tr><th scope="col" class="px-4 py-3">{{ __('Status') }}</th><th scope="col" class="px-4 py-3 text-right">{{ __('Calls') }}</th></tr></thead>
                <tbody>
                    @forelse ($statuses as $status)
                        <tr class="border-t border-gray-200 dark:border-slate-700"><td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $status->status ?: __('Unknown'))) }}</td><td class="px-4 py-3 text-right">{{ number_format($status->total) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-10 text-center text-gray-500 dark:text-slate-400">{{ __('No calls recorded in this date range.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</div>
@endsection
