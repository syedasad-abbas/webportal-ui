@can('recording.view')
<div id="active-transcripts" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm"
     data-calls-url="{{ route('admin.recordings.activeCalls') }}"
     data-transcript-url="{{ route('admin.recordings.transcript', ['callLog' => '__UUID__']) }}">
    <div class="p-5 border-b border-slate-200 dark:border-slate-800">
        <h3 class="text-lg font-semibold">{{ __('Active Call Transcripts') }}</h3>
        <p class="text-xs text-gray-500 mt-1">{{ __('Customer speech only. Ended calls disappear automatically.') }}</p>
    </div>
    <div class="p-4">
        <p data-feed-status class="text-sm text-gray-500" role="status">{{ __('Checking active calls…') }}</p>
        <div data-call-list class="space-y-2 max-h-[420px] overflow-y-auto"></div>
    </div>
</div>
@push('scripts')
<script src="{{ asset('js/active-call-transcripts.js') }}?v=1" defer></script>
@endpush
@endcan
