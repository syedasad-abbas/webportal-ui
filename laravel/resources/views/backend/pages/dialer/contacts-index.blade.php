@extends('backend.layouts.app')

@section('title', __('Contacts') . ' | ' . config('app.name'))

@push('styles')
<style>
    .connectpro-reference-nav { display: flex !important; }
    .connectpro-reference-nav a { display: inline-flex; align-items: center; min-height: 32px; padding: 0 .9rem; border-radius: 8px; color: #8ea0b8; font-size: .65rem; font-weight: 600; }
    .connectpro-reference-nav a:hover, .connectpro-reference-nav .connectpro-reference-nav-active { background: #1b3154; color: #f8fafc; }
</style>
@endpush

@section('admin-content')
<div class="connectpro-communication-page min-h-full bg-[#06111f] text-white">
    @include('backend.pages.dialer.contacts-header')

    <div class="mx-auto max-w-[1240px] p-4 sm:p-6">
        <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_auto]">
            <div class="relative">
                <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                <input name="search" value="{{ $search }}" type="search" placeholder="{{ __('Search contacts by name, company or phone…') }}" class="h-12 w-full rounded-xl border border-[#2a4055] bg-[#0a1929] pl-12 pr-4 text-sm text-white outline-none placeholder:text-slate-500 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
            </div>
            <div class="flex gap-2">
                <select name="sort" onchange="this.form.submit()" class="h-12 rounded-xl border border-[#2a4055] bg-[#0a1929] px-4 text-sm text-slate-200 outline-none focus:border-blue-500">
                    <option value="name" @selected($sort === 'name')>{{ __('Name (A–Z)') }}</option>
                    <option value="recent" @selected($sort === 'recent')>{{ __('Recently updated') }}</option>
                </select>
                <button class="flex h-12 w-12 items-center justify-center rounded-xl border border-[#2a4055] bg-[#0a1929] text-slate-300 hover:border-blue-500 hover:text-blue-400" aria-label="{{ __('Search') }}"><i class="bi bi-funnel text-lg"></i></button>
            </div>
        </form>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap gap-2">
                @foreach(['all' => ['All', 'bi-people'], 'vip' => ['VIP', 'bi-star-fill'], 'recent' => ['Recent', 'bi-clock'], 'follow-up' => ['Needs Follow-up', 'bi-exclamation-circle']] as $value => [$label, $icon])
                    <a href="{{ route('admin.contacts.index', array_filter(['filter' => $value === 'all' ? null : $value, 'search' => $search ?: null, 'sort' => $sort !== 'name' ? $sort : null])) }}" class="inline-flex h-10 items-center gap-2 rounded-full border px-4 text-sm font-medium transition {{ $filter === $value ? 'border-blue-500 bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'border-[#2a4055] bg-[#0a1929] text-slate-300 hover:border-blue-500/60 hover:text-blue-400' }}">
                        <i class="bi {{ $icon }}"></i>{{ __($label) }}
                    </a>
                @endforeach
            </div>
            <a href="{{ route('admin.contacts.create') }}" class="flex h-10 items-center gap-2 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-500">
                <i class="bi bi-person-plus-fill"></i><span>{{ __('Add Contact') }}</span>
            </a>
        </div>

        <section class="mt-4 overflow-hidden rounded-2xl border border-[#294158] bg-[#091827] shadow-2xl shadow-black/20">
            @forelse($contacts as $contact)
                @php
                    $initials = collect(explode(' ', $contact->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
                    $labels = collect($contact->labels ?? []);
                    $accent = ['text-blue-400', 'text-fuchsia-400', 'text-emerald-400', 'text-amber-400'][$loop->index % 4];
                @endphp
                <article class="group grid items-center gap-3 border-b border-[#1e3347] px-4 py-3 last:border-0 sm:grid-cols-[64px_minmax(180px,1.1fr)_minmax(170px,.85fr)_auto] sm:px-5">
                    <a href="{{ route('admin.contacts.show', $contact) }}" class="relative row-span-2 h-14 w-14 overflow-hidden rounded-full border-2 border-[#365068] bg-gradient-to-br from-blue-500 to-blue-800">
                        @if($contact->avatar_url)
                            <img src="{{ $contact->avatar_url }}" alt="" class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-base font-bold text-white">{{ $initials ?: '?' }}</span>
                        @endif
                    </a>
                    <div class="min-w-0">
                        <a href="{{ route('admin.contacts.show', $contact) }}" class="truncate text-lg font-semibold text-white transition group-hover:text-blue-300">{{ $contact->name }}</a>
                        <p class="truncate text-sm font-medium {{ $accent }}">{{ $contact->company ?: __('Independent contact') }}</p>
                        <p class="mt-0.5 truncate text-xs italic text-slate-400">{{ $contact->comments->first()?->body ?? __('No notes yet. Open contact to add context.') }}</p>
                    </div>
                    <div class="min-w-0 text-sm text-slate-300">
                        <p class="truncate"><i class="bi bi-telephone mr-2 text-slate-400"></i>{{ $contact->phone }}</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach($labels->take(2) as $label)
                                <span class="rounded-full border border-blue-500/20 bg-blue-500/10 px-2 py-0.5 text-[10px] font-semibold text-blue-300">{{ $label }}</span>
                            @endforeach
                            @if($contact->is_flagged)<span class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-0.5 text-[10px] font-semibold text-amber-300">{{ __('Follow-up') }}</span>@endif
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.contacts.edit', $contact) }}" class="flex h-10 w-10 items-center justify-center rounded-full border border-[#365068] text-slate-300 hover:border-blue-500 hover:text-blue-400" title="{{ __('Edit contact') }}"><i class="bi bi-pencil"></i></a>
                        <a href="{{ route('admin.dialer.index', ['contact' => $contact->id, 'destination' => $contact->phone]) }}" class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-600 text-xl text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-500" title="{{ __('Call') }}"><i class="bi bi-telephone-fill"></i></a>
                    </div>
                </article>
            @empty
                <div class="px-6 py-16 text-center">
                    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-500/10 text-2xl text-blue-400"><i class="bi bi-people"></i></span>
                    <h2 class="mt-4 text-lg font-semibold text-white">{{ __('No contacts found') }}</h2>
                    <p class="mt-1 text-sm text-slate-400">{{ __('Contacts saved from the dialer will appear here.') }}</p>
                </div>
            @endforelse
        </section>

        <div class="mt-4 flex items-center justify-between gap-4 text-sm text-slate-400">
            <span>{{ trans_choice(':count contact|:count contacts', $contacts->total(), ['count' => $contacts->total()]) }}</span>
            <div class="connectpro-pagination">{{ $contacts->links() }}</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const topBarLinks = document.querySelectorAll('.connectpro-reference-nav a');
    topBarLinks.forEach((link) => {
        link.addEventListener('click', (e) => {
            // Update active tab highlighting
            topBarLinks.forEach(l => l.classList.remove('connectpro-reference-nav-active'));
            link.classList.add('connectpro-reference-nav-active');
        });
    });
});
</script>
@endpush
