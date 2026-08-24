@extends('backend.layouts.app')

@section('title', __('Recordings'))

@section('admin-content')
    <div class="p-4 mx-auto max-w-6xl md:p-6">
        <div class="mb-4">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('Recordings') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('Paste the phone number you dialled, then refine by user or time period.') }}
            </p>
        </div>

        @php
            $periodOptions = [
                'last_24_hours' => __('Last 24 hours'),
                'last_7_days' => __('Last 7 days'),
                'last_30_days' => __('Last 30 days'),
                'this_month' => __('This month'),
            ];
            $currentPeriod = request()->get('period');
            $currentPeriod = ($currentPeriod === null) ? '' : $currentPeriod;
        @endphp

        <form id="recordings-filter-form" action="{{ route('admin.recordings.index') }}" method="GET" class="space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="p-5 space-y-5 sm:p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:gap-6">
                        <label class="w-full">
                            <span class="form-label text-sm text-gray-600 dark:text-gray-300">{{ __('Search (paste number)') }}</span>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <input
                                    type="text"
                                    name="phone_number"
                                    class="form-control flex-1"
                                    placeholder="{{ __('Paste number here') }}"
                                    value="{{ $filters['phone_number'] ?? '' }}"
                                >
                                <button type="submit" class="btn btn-primary whitespace-nowrap px-6">{{ __('Search') }}</button>
                            </div>
                        </label>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <div class="flex items-center gap-2">
                                <span class="flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-900 shadow-sm dark:border-indigo-500/40 dark:bg-indigo-900/30 dark:text-indigo-100">
                                    <i class="bi bi-person-lines-fill text-base"></i>
                                    {{ __('User') }}
                                </span>
                                <div class="relative">
                                    <button type="button" id="recording-user-filter" data-dropdown-toggle="recording-user-dropdown"
                                        class="btn-primary flex items-center justify-center gap-2 rounded-full px-5 py-2 text-sm">
                                        <i class="bi bi-sliders text-base"></i>
                                        <span>{{ __('Filter') }}</span>
                                        <i class="bi bi-chevron-down text-xs"></i>
                                    </button>
                                    <div id="recording-user-dropdown"
                                        class="z-20 hidden w-60 rounded-lg border border-gray-100 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                        <ul class="max-h-64 overflow-y-auto py-1 text-sm text-gray-700 dark:text-gray-200">
                                            <li>
                                                <button type="button"
                                                    class="flex w-full items-center justify-between rounded-md px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white {{ empty($filters['user_id']) ? 'bg-indigo-50 dark:bg-gray-700' : '' }}"
                                                    data-filter-choice="user_id"
                                                    data-value="">
                                                    <span>{{ __('All users') }}</span>
                                                    @if(empty($filters['user_id']))
                                                        <i class="bi bi-check text-indigo-600 dark:text-white"></i>
                                                    @endif
                                                </button>
                                            </li>
                                            @foreach($users as $user)
                                                @php
                                                    $label = $user->external_name ?: $user->email;
                                                    if ($user->external_name && $user->email) {
                                                        $label = "{$user->external_name} ({$user->email})";
                                                    }
                                                    $userValue = (string)$user->id;
                                                    $isActive = ($filters['user_id'] ?? '') == $userValue;
                                                @endphp
                                                <li>
                                                    <button type="button"
                                                        class="flex w-full items-center justify-between rounded-md px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white {{ $isActive ? 'bg-indigo-50 dark:bg-gray-700' : '' }}"
                                                        data-filter-choice="user_id"
                                                        data-value="{{ $userValue }}">
                                                        <span>{{ $label }}</span>
                                                        @if($isActive)
                                                            <i class="bi bi-check text-indigo-600 dark:text-white"></i>
                                                        @endif
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-900 shadow-sm dark:border-indigo-500/40 dark:bg-indigo-900/30 dark:text-indigo-100">
                                    <i class="bi bi-calendar-week text-base"></i>
                                    {{ __('Period') }}
                                </span>
                                <div class="relative">
                                    <button type="button" id="recording-period-filter" data-dropdown-toggle="recording-period-dropdown"
                                        class="btn-primary flex items-center justify-center gap-2 rounded-full px-5 py-2 text-sm">
                                        <i class="bi bi-sliders text-base"></i>
                                        <span>{{ __('Filter') }}</span>
                                        <i class="bi bi-chevron-down text-xs"></i>
                                    </button>
                                    <div id="recording-period-dropdown"
                                        class="z-20 hidden w-60 rounded-lg border border-gray-100 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                        <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                                            <li>
                                                <button type="button"
                                                    class="flex w-full items-center justify-between rounded-md px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white {{ $currentPeriod === '' ? 'bg-indigo-50 dark:bg-gray-700' : '' }}"
                                                    data-period-option="all">
                                                    <span>{{ __('All time') }}</span>
                                                    @if($currentPeriod === '')
                                                        <i class="bi bi-check text-indigo-600 dark:text-white"></i>
                                                    @endif
                                                </button>
                                            </li>
                                            @foreach($periodOptions as $value => $label)
                                                <li>
                                                    <button type="button"
                                                        class="flex w-full items-center justify-between rounded-md px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white {{ $currentPeriod === $value ? 'bg-indigo-50 dark:bg-gray-700' : '' }}"
                                                        data-period-option="{{ $value }}">
                                                        <span>{{ $label }}</span>
                                                        @if($currentPeriod === $value)
                                                            <i class="bi bi-check text-indigo-600 dark:text-white"></i>
                                                        @endif
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="user_id" value="{{ $filters['user_id'] ?? '' }}">
                    <input type="hidden" name="start_date" value="{{ $filters['start_date'] ?? '' }}">
                    <input type="hidden" name="end_date" value="{{ $filters['end_date'] ?? '' }}">
                    <input type="hidden" name="period" value="{{ $currentPeriod }}">
                </div>
            </div>
        </form>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 mt-4">
            <div class="p-4 sm:p-6">
                @include('backend.pages.recordings._table', ['recordings' => $recordings ?? collect()])
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('recordings-filter-form');
        if (!form) return;

        const setValue = (name, value) => {
            const field = form.querySelector(`[name="${name}"]`);
            if (field) field.value = value ?? '';
        };

        const applyAndSubmit = () => form.submit();

        document.querySelectorAll('[data-filter-choice]').forEach((button) => {
            button.addEventListener('click', () => {
                setValue(button.dataset.filterChoice, button.dataset.value ?? '');
                applyAndSubmit();
            });
        });

        const formatDate = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        const addDays = (date, days) => {
            const copy = new Date(date);
            copy.setDate(copy.getDate() + days);
            return copy;
        };

        const computePeriodRange = (period) => {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            switch (period) {
                case 'last_24_hours': {
                    const start = addDays(today, -1);
                    return { start: formatDate(start), end: formatDate(today) };
                }
                case 'last_7_days': {
                    const start = addDays(today, -6);
                    return { start: formatDate(start), end: formatDate(today) };
                }
                case 'last_30_days': {
                    const start = addDays(today, -29);
                    return { start: formatDate(start), end: formatDate(today) };
                }
                case 'this_month': {
                    const start = new Date(today.getFullYear(), today.getMonth(), 1);
                    return { start: formatDate(start), end: formatDate(today) };
                }
                default:
                    return null;
            }
        };

        document.querySelectorAll('[data-period-option]').forEach((button) => {
            button.addEventListener('click', () => {
                const option = button.dataset.periodOption || '';
                if (!option || option === 'all') {
                    setValue('period', '');
                    setValue('start_date', '');
                    setValue('end_date', '');
                    applyAndSubmit();
                    return;
                }

                const range = computePeriodRange(option);
                if (!range) {
                    setValue('period', '');
                    setValue('start_date', '');
                    setValue('end_date', '');
                } else {
                    setValue('period', option);
                    setValue('start_date', range.start);
                    setValue('end_date', range.end);
                }
                applyAndSubmit();
            });
        });
    });
</script>
@endpush
