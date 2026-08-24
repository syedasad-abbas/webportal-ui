<table class="w-full text-sm text-gray-700 dark:text-gray-300">
    <thead class="bg-gray-50 dark:bg-gray-800 text-left">
        <tr class="border-b border-gray-100 dark:border-gray-700">
            <th class="px-5 py-3 font-semibold">{{ __('Caller ID') }}</th>
            <th class="px-5 py-3 font-semibold">{{ __('Destination') }}</th>
            <th class="px-5 py-3 font-semibold">{{ __('User ID') }}</th>
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
                <td class="px-5 py-4">#{{ $recording->user_id ?? '—' }}</td>
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
                            <audio controls class="w-full md:w-56">
                                <source src="{{ $recording->recording_url ?? '' }}" type="audio/mpeg">
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

@if($recordings->hasPages())
    <div class="mt-3">
        {{ $recordings->appends(request()->query())->links() }}
    </div>
@endif
