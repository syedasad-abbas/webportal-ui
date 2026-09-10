<div class="hidden md:block">
    <table class="w-full text-sm text-gray-700 dark:text-gray-300">
        <thead class="bg-gray-50 dark:bg-gray-800 text-left">
            <tr class="border-b border-gray-100 dark:border-gray-700">
                <th class="px-5 py-3 font-semibold">{{ __('Caller ID') }}</th>
                <th class="px-5 py-3 font-semibold">{{ __('Destination') }}</th>
                <th class="px-5 py-3 font-semibold">{{ __('User') }}</th>
                <th class="px-5 py-3 font-semibold">{{ __('Date & Time') }}</th>
                <th class="px-5 py-3 font-semibold">{{ __('Duration') }}</th>
                <th class="px-5 py-3 font-semibold">{{ __('Recording') }}</th>
                <th class="px-5 py-3 font-semibold text-right">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recordings as $recording)
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    <td class="px-5 py-4">{{ $recording->caller_id ?? '—' }}</td>
                    <td class="px-5 py-4">{{ $recording->destination ?? '—' }}</td>
                    <td class="px-5 py-4">{{ $recording->user->external_name ?? $recording->user->name ?? '—' }}</td>
                    <td class="px-5 py-4">{{ $recording->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="px-5 py-4">
                        @if($recording->duration_seconds)
                            {{ gmdate('i:s', $recording->duration_seconds) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        @if($recording->recording_path)
                            <div class="flex flex-col gap-2">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-white">
                                    {{ basename($recording->recording_path) }}
                                </span>
                                <audio controls preload="none" class="w-full md:w-56">
                                    <source src="{{ $recording->recording_url ?? '' }}">
                                </audio>
                            </div>
                        @else
                            <span class="text-gray-400 dark:text-gray-500">{{ __('No file') }}</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex justify-end">
                            <x-buttons.action-buttons :label="__('Actions')" :show-label="false" align="right">
                            @can('recording.download')
                                <x-buttons.action-item
                                    :href="route('admin.recordings.download', $recording)"
                                    icon="download"
                                    :label="__('Download')"
                                />
                            @endcan

                            @can('recording.delete')
                                <form id="delete-recording-{{ $recording->id }}" action="{{ route('admin.recordings.destroy', $recording) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <x-buttons.action-item
                                    type="button"
                                    icon="trash"
                                    class="text-red-600 dark:text-red-400"
                                    :label="__('Delete')"
                                    onClick="event.preventDefault(); if(confirm('{{ __('Delete this recording?') }}')) document.getElementById('delete-recording-{{ $recording->id }}').submit();"
                                />
                            @endcan
                            </x-buttons.action-buttons>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-5 py-6 text-center text-gray-500 dark:text-gray-400">
                        {{ __('No recordings found.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="md:hidden space-y-4">
    @forelse($recordings as $recording)
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                        {{ $recording->caller_id ?? '—' }} → {{ $recording->destination ?? '—' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $recording->user->external_name ?? $recording->user->name ?? '—' }}
                    </p>
                </div>
                <div class="flex shrink-0">
                    <x-buttons.action-buttons :label="__('Actions')" :show-label="false" align="right">
                    @can('recording.download')
                        <x-buttons.action-item
                            :href="route('admin.recordings.download', $recording)"
                            icon="download"
                            :label="__('Download')"
                        />
                    @endcan

                    @can('recording.delete')
                        <form id="delete-recording-mobile-{{ $recording->id }}" action="{{ route('admin.recordings.destroy', $recording) }}" method="POST" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                        <x-buttons.action-item
                            type="button"
                            icon="trash"
                            class="text-red-600 dark:text-red-400"
                            :label="__('Delete')"
                            onClick="event.preventDefault(); if(confirm('{{ __('Delete this recording?') }}')) document.getElementById('delete-recording-mobile-{{ $recording->id }}').submit();"
                        />
                    @endcan
                    </x-buttons.action-buttons>
                </div>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3 text-xs text-gray-600 dark:text-gray-300">
                <div>
                    <span class="block text-gray-400 dark:text-gray-500">{{ __('Date & Time') }}</span>
                    {{ $recording->created_at?->format('Y-m-d H:i') ?? '—' }}
                </div>
                <div>
                    <span class="block text-gray-400 dark:text-gray-500">{{ __('Duration') }}</span>
                    @if($recording->duration_seconds)
                        {{ gmdate('i:s', $recording->duration_seconds) }}
                    @else
                        —
                    @endif
                </div>
            </div>
            @if($recording->recording_path)
                <div class="mt-3">
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-white">
                        {{ basename($recording->recording_path) }}
                    </span>
                    <audio controls preload="none" class="w-full mt-2">
                        <source src="{{ $recording->recording_url ?? '' }}">
                    </audio>
                </div>
            @else
                <span class="text-gray-400 dark:text-gray-500 text-xs mt-2">{{ __('No file') }}</span>
            @endif
        </div>
    @empty
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 p-6 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ __('No recordings found.') }}
        </div>
    @endforelse
</div>
