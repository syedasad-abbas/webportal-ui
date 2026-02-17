@extends('backend.layouts.app')

@section('title')
    {{ $breadcrumbs['title'] }} | {{ config('app.name') }}
@endsection

@section('admin-content')
    <div class="p-4 mx-auto max-w-7xl md:p-6">
        <x-breadcrumbs :breadcrumbs="$breadcrumbs" />

        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="p-5 space-y-6 border-t border-gray-100 dark:border-gray-800 sm:p-6">
                    <form action="{{ route('admin.campaigns.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

                            {{-- List id --}}
                            <div>
                                <label for="List id " class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('List id') }}
                                </label>
                                <input
                                    type="text"
                                    name="list_id"
                                    id="list_id"
                                    required
                                    value="{{ old('list_id') }}"
                                    placeholder="{{ __('Displayed to others') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- List Name --}}
                            <div>
                                <label for="List_name" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('List Name') }}
                                </label>
                                <input
                                    type="text"
                                    name="list_name"
                                    id="list_name"
                                    required
                                    value="{{ old('list_name') }}"
                                    placeholder="{{ __('For internal reference') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- List Discription--}}
                            <div>
                                <label for="list_description" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('List Description') }}
                                </label>
                                <input
                                    type="text"
                                    name="list_description"
                                    id="list_description"
                                    required
                                    value="{{ old('list_description') }}"
                                    placeholder="{{ __('List Description') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>

                            {{-- File--}}
                            <div>
                                <label for="file" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                    {{ __('File') }}
                                </label>
                                <input
                                    type="file"
                                    name="file"
                                    id="file"
                                    accept=".csv,.xls,.xlsx"
                                    required
                                    placeholder="{{ __('File') }}"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                            </div>


                        </div>

                        <div class="mt-6 flex justify-start gap-4">
                            <button type="submit" class="btn-primary">{{ __('Submit') }}</button>
                            <a href="{{ route('admin.dashboard') }}" class="btn-default">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>

            @if($campaigns->isNotEmpty())
                <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <div class="p-5 space-y-4 border-t border-gray-100 dark:border-gray-800 sm:p-6">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                                {{ __('Uploaded campaigns') }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Monitor import progress and errors in real time.') }}
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-900/40 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3 text-left">{{ __('Campaign') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Description') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Status') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Progress') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Updated') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($campaigns as $campaign)
                                        @php
                                            $status = strtolower($campaign->import_status ?? 'pending');
                                            $total = (int) $campaign->total_rows;
                                            $imported = (int) $campaign->imported_rows;
                                            $percent = $total > 0 ? min(100, round(($imported / $total) * 100)) : 0;
                                            $statusLabel = \Illuminate\Support\Str::headline($campaign->import_status ?? 'pending');
                                            $statusClasses = match ($status) {
                                                'completed' => 'text-green-800 bg-green-100 dark:text-green-100 dark:bg-green-500/30 dark:border dark:border-green-400/60',
                                                'failed' => 'text-red-800 bg-red-100 dark:text-red-100 dark:bg-red-500/30 dark:border dark:border-red-400/60',
                                                'processing' => 'text-blue-800 bg-blue-100 dark:text-blue-100 dark:bg-blue-500/30 dark:border dark:border-blue-400/60',
                                                default => 'text-amber-800 bg-amber-100 dark:text-amber-100 dark:bg-amber-500/30 dark:border dark:border-amber-400/60',
                                            };
                                        @endphp
                                        <tr
                                            data-campaign-row
                                            data-campaign-id="{{ $campaign->id }}"
                                            data-status="{{ $status }}"
                                            data-status-url="{{ route('admin.campaigns.status', $campaign) }}"
                                        >
                                            <td class="px-4 py-4 align-top">
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ $campaign->list_name }} <span class="text-gray-500 dark:text-gray-400">({{ $campaign->list_id }})</span>
                                                </div>
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ __('Uploaded') }} {{ optional($campaign->created_at)->format('M j, Y H:i') ?? '—' }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 align-top text-gray-700 dark:text-gray-300">
                                                {{ $campaign->list_description }}
                                            </td>
                                            <td class="px-4 py-4 align-top">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold border {{ $statusClasses }}"
                                                      data-status-text>
                                                    {{ $statusLabel }}
                                                </span>
                                                <p class="mt-2 text-xs text-red-500 dark:text-red-300 @if(empty($campaign->import_error)) hidden @endif"
                                                   data-status-error>
                                                    {{ $campaign->import_error }}
                                                </p>
                                            </td>
                                            <td class="px-4 py-4 align-top text-sm text-gray-700 dark:text-gray-300">
                                                <div data-status-progress-text>
                                                    {{ $imported }} / {{ $total ?: '—' }}
                                                </div>
                                                <div class="mt-2 h-2 bg-gray-200 dark:bg-gray-800 rounded-full overflow-hidden">
                                                    <div
                                                        class="h-2 bg-blue-500 transition-all duration-300"
                                                        style="width: {{ $percent }}%;"
                                                        data-status-progress-bar
                                                    ></div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 align-top text-xs text-gray-500 dark:text-gray-400">
                                                {{ optional($campaign->import_completed_at ?? $campaign->updated_at)->format('M j, Y H:i') ?? '—' }}
                                            </td>
                                            <td class="px-4 py-4 align-top">
                                                <div class="flex justify-center">
                                                    <x-buttons.action-buttons :label="__('Actions')" :show-label="false" align="right">
                                                        <x-buttons.action-item
                                                            :href="route('admin.campaigns.edit', $campaign)"
                                                            icon="pencil"
                                                            :label="__('Edit')"
                                                        />
                                                        <div x-data="{ deleteModalOpen: false }">
                                                            <x-buttons.action-item
                                                                type="modal-trigger"
                                                                modal-target="deleteModalOpen"
                                                                icon="trash"
                                                                :label="__('Delete')"
                                                                class="text-red-600 dark:text-red-400"
                                                            />

                                                            <x-modals.confirm-delete
                                                                id="delete-campaign-{{ $campaign->id }}"
                                                                title="{{ __('Delete Campaign') }}"
                                                                content="{{ __('Are you sure you want to delete this campaign? This cannot be undone.') }}"
                                                                formId="delete-campaign-form-{{ $campaign->id }}"
                                                                formAction="{{ route('admin.campaigns.destroy', $campaign) }}"
                                                                modalTrigger="deleteModalOpen"
                                                                cancelButtonText="{{ __('No, cancel') }}"
                                                                confirmButtonText="{{ __('Yes, delete') }}"
                                                            />
                                                        </div>
                                                    </x-buttons.action-buttons>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ACTIVE_STATUSES = ['pending', 'processing'];
    let pollRows = Array.from(document.querySelectorAll('[data-campaign-row]'))
        .filter((row) => ACTIVE_STATUSES.includes((row.dataset.status || '').toLowerCase()));

    if (!pollRows.length) {
        return;
    }

    const fetchStatus = async (row) => {
        const url = row.dataset.statusUrl;
        if (!url) {
            return;
        }

        try {
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            const data = await response.json();
            applyStatus(row, data);
        } catch (error) {
            const errorEl = row.querySelector('[data-status-error]');
            if (errorEl) {
                errorEl.textContent = error.message || '{{ __('Unable to fetch status') }}';
                errorEl.classList.remove('hidden');
            }
        }
    };

    const applyStatus = (row, payload) => {
        const status = (payload.status || payload.import_status || 'pending').toLowerCase();
        row.dataset.status = status;

        const statusText = row.querySelector('[data-status-text]');
        if (statusText) {
            const label = status.replace(/_/g, ' ');
            statusText.textContent = label.charAt(0).toUpperCase() + label.slice(1);
            statusText.classList.toggle('bg-green-100', status === 'completed');
            statusText.classList.toggle('text-green-800', status === 'completed');
            statusText.classList.toggle('bg-yellow-100', status === 'processing' || status === 'pending');
            statusText.classList.toggle('text-yellow-800', status === 'processing' || status === 'pending');
            statusText.classList.toggle('bg-red-100', status === 'failed');
            statusText.classList.toggle('text-red-800', status === 'failed');
        }

        const imported = payload.imported ?? payload.imported_rows ?? 0;
        const total = payload.total ?? payload.total_rows ?? 0;

        const progressText = row.querySelector('[data-status-progress-text]');
        if (progressText) {
            progressText.textContent = `${imported} / ${total || '—'}`;
        }

        const progressBar = row.querySelector('[data-status-progress-bar]');
        if (progressBar) {
            const percent = total > 0 ? Math.min(100, Math.round((imported / total) * 100)) : 0;
            progressBar.style.width = `${percent}%`;
        }

        const errorEl = row.querySelector('[data-status-error]');
        if (errorEl) {
            const errorMessage = payload.error || payload.import_error || '';
            if (errorMessage) {
                errorEl.textContent = errorMessage;
                errorEl.classList.remove('hidden');
            } else {
                errorEl.textContent = '';
                errorEl.classList.add('hidden');
            }
        }
    };

    const poll = () => {
        pollRows = pollRows.filter((row) => ACTIVE_STATUSES.includes((row.dataset.status || '').toLowerCase()));
        if (!pollRows.length) {
            return false;
        }
        pollRows.forEach(fetchStatus);
        return true;
    };

    poll();
    const interval = setInterval(() => {
        if (!poll()) {
            clearInterval(interval);
        }
    }, 5000);
});
</script>
@endpush
