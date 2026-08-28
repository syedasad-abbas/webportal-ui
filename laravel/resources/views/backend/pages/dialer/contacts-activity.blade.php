@extends('backend.layouts.app')

@section('title', __('Activity Logs') . ' | ' . config('app.name'))

@section('admin-content')
<div class="connectpro-communication-page min-h-full bg-[#06111f] text-white">
    @include('backend.pages.dialer.contacts-header', ['title' => __('Activity Logs'), 'subtitle' => __('Contact updates, notes, labels and follow-up activity')])
    <div class="mx-auto max-w-[1100px] p-4 sm:p-6">
        <section class="rounded-2xl border border-[#294158] bg-[#091827] p-4 shadow-2xl shadow-black/20 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#294158] pb-4">
                <div><h2 class="text-lg font-semibold">{{ __('Recent customer activity') }}</h2><p class="mt-1 text-xs text-slate-400">{{ __('A shared timeline across your contact book') }}</p></div>
                <span class="rounded-full border border-blue-500/30 bg-blue-500/10 px-3 py-1 text-xs font-semibold text-blue-300">{{ $activities->total() }} {{ __('events') }}</span>
            </div>
            <div class="relative mt-5 space-y-1 before:absolute before:bottom-6 before:left-5 before:top-6 before:w-px before:bg-[#294158]">
                @forelse($activities as $activity)
                    @php
                        $icons = ['comment_added' => ['bi-chat-left-text', 'bg-violet-600'], 'contact_created' => ['bi-person-plus', 'bg-blue-600'], 'contact_updated' => ['bi-pencil', 'bg-cyan-600'], 'label_added' => ['bi-tag', 'bg-emerald-600'], 'label_removed' => ['bi-tag', 'bg-slate-600'], 'flag_added' => ['bi-flag-fill', 'bg-amber-500'], 'flag_removed' => ['bi-flag', 'bg-slate-600']];
                        [$icon, $color] = $icons[$activity->action] ?? ['bi-activity', 'bg-blue-600'];
                    @endphp
                    <article class="relative flex gap-4 rounded-xl px-1 py-4 transition hover:bg-white/[.025] sm:px-3">
                        <span class="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-4 border-[#091827] {{ $color }} text-white"><i class="bi {{ $icon }}"></i></span>
                        <div class="min-w-0 flex-1 border-b border-[#1d3246] pb-4">
                            <div class="flex flex-wrap items-start justify-between gap-2"><div><a href="{{ $activity->contact ? route('admin.contacts.show', $activity->contact) : '#' }}" class="font-semibold text-white hover:text-blue-400">{{ $activity->contact?->name ?? __('Deleted contact') }}</a><span class="mx-2 text-slate-600">•</span><span class="text-sm text-slate-400">{{ $activity->contact?->company }}</span></div><time class="text-xs text-slate-500">{{ $activity->created_at?->diffForHumans() }}</time></div>
                            <p class="mt-1 text-sm text-slate-300">{{ $activity->description }}</p>
                            <p class="mt-1 text-[11px] text-slate-500">{{ __('By') }} {{ $activity->user?->external_name ?: $activity->user?->email ?: __('System') }} · {{ $activity->created_at?->format('M j, Y \a\t g:i A') }}</p>
                        </div>
                    </article>
                @empty
                    <div class="py-16 text-center text-sm text-slate-400">{{ __('No contact activity has been recorded yet.') }}</div>
                @endforelse
            </div>
        </section>
        <div class="connectpro-pagination mt-4">{{ $activities->links() }}</div>
    </div>
</div>
@endsection
