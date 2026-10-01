@extends('backend.layouts.app')

@section('title', __('Call History') . ' | ' . config('app.name'))

@section('admin-content')
<div class="connectpro-communication-page min-h-full bg-[#06111f] text-white">
    @include('backend.pages.dialer.contacts-header', ['title' => __('Call History'), 'subtitle' => __('Review recent inbound and outbound conversations')])
    <div class="mx-auto max-w-[1180px] p-4 sm:p-6">
        <section class="overflow-hidden rounded-2xl border border-[#294158] bg-[#091827] shadow-2xl shadow-black/20">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#294158] p-4 sm:px-5">
                <div><h2 class="text-lg font-semibold">{{ __('Recent Calls') }}</h2><p class="mt-1 text-xs text-slate-400">{{ __('All call activity from your connected lines') }}</p></div>
                <span class="rounded-full border border-[#365068] px-3 py-1 text-xs text-slate-300">{{ $calls->total() }} {{ __('calls') }}</span>
            </div>
            @canany(['recording.delete', 'contacts.delete'])
                <form id="history-bulk-delete" action="{{ route('admin.contacts.call-history.bulk-destroy') }}" method="POST" class="flex flex-wrap items-center gap-3 border-b border-[#294158] p-4" onsubmit="return confirm(@js(__('Delete the selected call records and any associated recordings? This cannot be undone.')));">
                    @csrf
                    @method('DELETE')
                    <label class="flex items-center gap-2 text-sm"><input id="history-page-select" type="checkbox" @disabled($calls->isEmpty())> {{ __('Select this page') }}</label>
                    <button id="history-bulk-submit" type="submit" disabled class="rounded-lg border border-red-500 px-3 py-2 text-sm text-red-400 disabled:opacity-40">{{ __('Delete selected') }} (<span id="history-selection-count">0</span>)</button>
                </form>
            @endcanany
            <div class="divide-y divide-[#1e3347]">
                @forelse($calls as $call)
                    @php
                        $contact = $call->getRelation('matchedContact');
                        $number = $call->direction === 'inbound' ? $call->caller_id : $call->destination;
                        $isMissed = in_array(strtolower((string) $call->status), ['failed', 'missed', 'declined', 'busy', 'no_answer']);
                    @endphp
                    <article class="grid items-center gap-3 px-4 py-4 transition hover:bg-white/[.025] sm:grid-cols-[48px_minmax(160px,1fr)_140px_110px_120px_auto] sm:px-5">
                        <div class="flex items-center gap-1">
                            @canany(['recording.delete', 'contacts.delete'])
                                <input type="checkbox" name="ids[]" value="{{ $call->id }}" form="history-bulk-delete" data-history-record aria-label="{{ __('Select call record') }} {{ $number }}">
                            @endcanany
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $isMissed ? 'bg-red-500/10 text-red-400' : 'bg-blue-500/10 text-blue-400' }}"><i class="bi {{ $call->direction === 'inbound' ? 'bi-telephone-inbound' : 'bi-telephone-outbound' }}"></i></span>
                        </div>
                        <div class="min-w-0"><p class="truncate font-semibold">{{ $contact?->name ?: $number ?: __('Unknown caller') }}</p><p class="truncate text-xs text-slate-400">{{ $contact?->company ?: $number }}</p></div>
                        <span class="text-sm capitalize text-slate-300">{{ str_replace('_', ' ', $call->direction ?: 'outbound') }}</span>
                        <span class="text-sm {{ $isMissed ? 'text-red-400' : 'text-emerald-400' }}">{{ ucfirst(str_replace('_', ' ', (string) $call->status)) }}</span>
                        <span class="text-sm text-slate-400">{{ gmdate('i:s', max(0, (int) $call->duration_seconds)) }}</span>
                        <div class="flex items-center justify-end gap-2">
                            <span class="hidden text-xs text-slate-500 xl:inline">{{ $call->created_at?->format('M j, g:i A') }}</span>
                            @canany(['recording.delete', 'contacts.delete'])
                            <form action="{{ route('admin.contacts.call-history.destroy', $call) }}" method="POST" onsubmit="return confirm(@js(__('Delete this call record and any associated recording? This cannot be undone.')));" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="flex h-10 w-10 items-center justify-center rounded-full border border-[#365068] text-slate-300 hover:border-red-500 hover:text-red-400" title="{{ __('Delete call record') }}"><i class="bi bi-trash"></i></button>
                            </form>
                            @endcanany
                            <a href="{{ route('admin.dialer.index', ['destination' => $number]) }}" class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-600 text-white hover:bg-emerald-500" title="{{ __('Call') }}"><i class="bi bi-telephone-fill"></i></a>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-16 text-center text-sm text-slate-400">{{ __('No calls have been recorded yet.') }}</div>
                @endforelse
            </div>
        </section>
        <div class="connectpro-pagination mt-4">{{ $calls->links() }}</div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectAll = document.getElementById('history-page-select');
    const submit = document.getElementById('history-bulk-submit');
    if (!selectAll || !submit) return;
    const records = [...document.querySelectorAll('[data-history-record]')];
    const updateSelection = () => {
        const count = records.filter((record) => record.checked).length;
        submit.disabled = count === 0;
        selectAll.checked = records.length > 0 && count === records.length;
        selectAll.indeterminate = count > 0 && count < records.length;
        document.getElementById('history-selection-count').textContent = count;
    };
    selectAll.addEventListener('change', () => {
        records.forEach((record) => { record.checked = selectAll.checked; });
        updateSelection();
    });
    records.forEach((record) => record.addEventListener('change', updateSelection));
    updateSelection();
});
</script>
@endsection
