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
            <div class="divide-y divide-[#1e3347]">
                @forelse($calls as $call)
                    @php
                        $contact = $call->getRelation('matchedContact');
                        $number = $call->direction === 'inbound' ? $call->caller_id : $call->destination;
                        $isMissed = in_array(strtolower((string) $call->status), ['failed', 'missed', 'declined', 'busy', 'no_answer']);
                    @endphp
                    <article class="grid items-center gap-3 px-4 py-4 transition hover:bg-white/[.025] sm:grid-cols-[48px_minmax(160px,1fr)_140px_110px_120px_auto] sm:px-5">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full {{ $isMissed ? 'bg-red-500/10 text-red-400' : 'bg-blue-500/10 text-blue-400' }}"><i class="bi {{ $call->direction === 'inbound' ? 'bi-telephone-inbound' : 'bi-telephone-outbound' }} text-lg"></i></span>
                        <div class="min-w-0"><p class="truncate font-semibold">{{ $contact?->name ?: $number ?: __('Unknown caller') }}</p><p class="truncate text-xs text-slate-400">{{ $contact?->company ?: $number }}</p></div>
                        <span class="text-sm capitalize text-slate-300">{{ str_replace('_', ' ', $call->direction ?: 'outbound') }}</span>
                        <span class="text-sm {{ $isMissed ? 'text-red-400' : 'text-emerald-400' }}">{{ ucfirst(str_replace('_', ' ', (string) $call->status)) }}</span>
                        <span class="text-sm text-slate-400">{{ gmdate('i:s', max(0, (int) $call->duration_seconds)) }}</span>
                        <div class="flex items-center justify-end gap-2"><span class="hidden text-xs text-slate-500 xl:inline">{{ $call->created_at?->format('M j, g:i A') }}</span><a href="{{ route('admin.dialer.index', ['destination' => $number]) }}" class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-600 text-white hover:bg-emerald-500"><i class="bi bi-telephone-fill"></i></a></div>
                    </article>
                @empty
                    <div class="px-6 py-16 text-center text-sm text-slate-400">{{ __('No calls have been recorded yet.') }}</div>
                @endforelse
            </div>
        </section>
        <div class="connectpro-pagination mt-4">{{ $calls->links() }}</div>
    </div>
</div>
@endsection
