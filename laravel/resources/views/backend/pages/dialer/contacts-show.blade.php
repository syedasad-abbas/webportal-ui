@extends('backend.layouts.app')

@section('title', $contact->name . ' | ' . config('app.name'))

@section('admin-content')
@php
    $initials = collect(explode(' ', $contact->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $latestComment = $contact->comments->first();
@endphp
<div class="connectpro-communication-page min-h-full bg-[#06111f] text-white">
    @include('backend.pages.dialer.contacts-header', ['title' => __('Contact Info'), 'subtitle' => __('Customer profile, context and communication history')])

    <div class="mx-auto max-w-[1280px] p-4 sm:p-6">
        <section class="grid gap-5 border-b border-[#294158] pb-6 lg:grid-cols-[160px_minmax(0,1fr)_220px]">
            <div class="relative mx-auto h-36 w-36 overflow-hidden rounded-full border-4 border-[#365068] bg-gradient-to-br from-blue-500 to-blue-900 shadow-[0_0_0_8px_rgba(59,130,246,.08)] lg:mx-0">
                @if($contact->avatar_url)
                    <img src="{{ $contact->avatar_url }}" alt="" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-4xl font-bold">{{ $initials ?: '?' }}</span>
                @endif
                <span class="absolute bottom-2 right-2 h-5 w-5 rounded-full border-[3px] border-[#06111f] bg-emerald-500"></span>
            </div>
            <div class="min-w-0 self-center text-center lg:text-left">
                <div class="flex flex-wrap items-center justify-center gap-3 lg:justify-start">
                    <h1 class="truncate text-3xl font-bold tracking-tight sm:text-4xl">{{ $contact->name }}</h1>
                    @if($contact->is_flagged)<i class="bi bi-patch-check-fill text-2xl text-blue-500" title="{{ __('Verified contact') }}"></i>@endif
                    <i class="bi bi-star text-2xl text-slate-400"></i>
                </div>
                <p class="mt-2 text-lg"><span class="font-semibold text-blue-400">{{ $contact->company ?: __('Independent contact') }}</span><span class="mx-2 text-slate-500">•</span><span class="text-slate-300">{{ __('Customer') }}</span></p>
                <div class="mt-4 flex flex-wrap justify-center gap-x-5 gap-y-2 text-sm text-slate-200 lg:justify-start">
                    <span><i class="bi bi-phone mr-2 text-blue-400"></i>{{ $contact->phone }}</span>
                    @if($contact->secondary_phone)<span><i class="bi bi-telephone mr-2 text-slate-400"></i>{{ $contact->secondary_phone }}</span>@endif
                    @if($contact->email)<span><i class="bi bi-envelope mr-2 text-blue-400"></i>{{ $contact->email }}</span>@endif
                </div>
                <div class="mt-4 flex flex-wrap justify-center gap-2 lg:justify-start">
                    @foreach($contact->labels ?? [] as $label)
                        <span class="rounded-full border border-blue-500/30 bg-blue-500/10 px-3 py-1 text-xs font-semibold text-blue-300"><i class="bi bi-circle-fill mr-1 text-[7px]"></i>{{ $label }}</span>
                    @endforeach
                    @if($contact->is_flagged)<span class="rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-300">{{ __('Key Account') }}</span>@endif
                </div>
            </div>
            <div class="flex flex-col justify-center gap-3">
                <a href="{{ route('admin.dialer.index', ['contact' => $contact->id, 'destination' => $contact->phone]) }}" class="flex h-14 items-center justify-center gap-3 rounded-xl bg-emerald-600 text-lg font-semibold shadow-lg shadow-emerald-600/20 hover:bg-emerald-500"><i class="bi bi-telephone-fill text-xl"></i>{{ __('Call') }}<i class="bi bi-chevron-down ml-auto mr-1 text-sm"></i></a>
                <button type="button" class="flex h-14 items-center justify-center gap-3 rounded-xl bg-blue-700/80 text-lg font-semibold hover:bg-blue-600"><i class="bi bi-chat-left-fill text-xl"></i>{{ __('SMS') }}</button>
            </div>
        </section>

        <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,.9fr)_minmax(0,1.1fr)]">
            <section class="rounded-2xl border border-[#294158] bg-[#091827] p-4 shadow-2xl shadow-black/20 sm:p-5">
                <div class="flex items-center justify-between">
                    <h2 class="flex items-center gap-2 text-xl font-semibold"><i class="bi bi-person-fill text-blue-400"></i>{{ __('Customer Details') }}</h2>
                    <a href="{{ route('admin.contacts.edit', $contact) }}" class="text-xl text-slate-400 hover:text-blue-400" title="{{ __('Edit contact') }}"><i class="bi bi-pencil"></i></a>
                </div>
                <div class="mt-4 grid gap-0 overflow-hidden rounded-xl border border-[#294158] sm:grid-cols-2">
                    @foreach([
                        ['bi-geo-alt', 'Address', $contact->address],
                        ['bi-clipboard2', 'Account ID', $contact->account_id],
                        ['bi-check-circle', 'Account Status', $contact->account_status ?: 'Active'],
                        ['bi-clock', 'Customer Since', $contact->customer_since?->format('M j, Y')],
                        ['bi-star', 'Industry', $contact->industry],
                        ['bi-people', 'Employees', $contact->employees],
                        ['bi-clock-history', 'Preferred Contact Time', $contact->preferred_contact_time],
                        ['bi-briefcase', 'Annual Revenue', $contact->annual_revenue],
                    ] as [$icon, $label, $value])
                        <div class="min-h-24 border-b border-[#233a50] p-4 sm:[&:nth-child(odd)]:border-r">
                            <p class="text-xs font-medium text-slate-400"><i class="bi {{ $icon }} mr-2"></i>{{ __($label) }}</p>
                            <p class="mt-2 whitespace-pre-line text-sm font-medium leading-5 text-slate-100">{{ $value ?: '—' }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-[#294158] bg-[#091827] p-4 shadow-2xl shadow-black/20 sm:p-5">
                <div class="flex items-center justify-between gap-3 border-b border-[#294158] pb-4">
                    <h2 class="flex items-center gap-2 text-xl font-semibold"><i class="bi bi-chat-left-text text-blue-400"></i>{{ __('Notes & Comments') }}<span class="rounded-full bg-slate-700 px-2 py-0.5 text-xs text-slate-300">{{ $contact->comments->count() }}</span></h2>
                    <span class="text-xs font-semibold text-slate-400">{{ __('Newest First') }} <i class="bi bi-chevron-down ml-1"></i></span>
                </div>
                <div class="relative mt-4 space-y-4 before:absolute before:bottom-4 before:left-[19px] before:top-4 before:w-px before:bg-blue-500/40">
                    @forelse($contact->comments->take(6) as $comment)
                        @php($author = $comment->user?->external_name ?: $comment->user?->email ?: __('Team member'))
                        <article class="relative flex gap-3 pl-1">
                            <span class="relative z-10 flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 border-[#091827] bg-blue-600 text-xs font-bold">{{ mb_strtoupper(mb_substr($author, 0, 1)) }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2"><div><h3 class="font-semibold">{{ $author }}</h3><p class="text-[11px] text-slate-400">{{ $comment->created_at?->format('M j, Y \a\t g:i A') }}</p></div><i class="bi bi-three-dots text-slate-400"></i></div>
                                <p class="mt-2 rounded-lg border border-[#263d53] bg-[#071625] px-3 py-2 text-sm leading-5 text-slate-300">{{ $comment->body }}</p>
                            </div>
                        </article>
                    @empty
                        <div class="relative z-10 rounded-xl border border-dashed border-[#365068] bg-[#071625] px-5 py-10 text-center text-sm text-slate-400">{{ __('No notes have been added for this contact.') }}</div>
                    @endforelse
                </div>
                <form class="mt-4 flex items-center gap-2 rounded-xl border border-[#365068] bg-[#071625] p-2" data-contact-comment-form data-url="{{ route('admin.dialer.contacts.comments.store', $contact) }}">
                    <input name="body" maxlength="2000" placeholder="{{ __('Add a note or comment…') }}" class="min-w-0 flex-1 border-0 bg-transparent px-2 text-sm text-white outline-none ring-0 placeholder:text-slate-500">
                    <button class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white hover:bg-blue-500" aria-label="{{ __('Add comment') }}"><i class="bi bi-send-fill"></i></button>
                </form>
            </section>
        </div>

        <div class="mt-4 flex items-center justify-between text-xs text-slate-400"><span><i class="bi bi-circle-fill mr-2 text-[8px] text-blue-500"></i>{{ __('Last updated') }}: {{ $contact->updated_at?->format('M j, Y \a\t g:i A') }}</span><a href="{{ request()->url() }}" class="hover:text-blue-400"><i class="bi bi-arrow-clockwise mr-2"></i>{{ __('Refresh') }}</a></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelector('[data-contact-comment-form]')?.addEventListener('submit', async function (event) {
    event.preventDefault();
    const input = this.querySelector('input[name="body"]');
    if (!input.value.trim()) return;
    const response = await fetch(this.dataset.url, {
        method: 'POST',
        headers: {'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
        body: JSON.stringify({body: input.value.trim()})
    });
    if (response.ok) window.location.reload();
});
</script>
@endpush
