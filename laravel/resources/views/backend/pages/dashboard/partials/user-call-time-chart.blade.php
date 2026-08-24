<div class="col-span-12">
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
        <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('User Call Logs') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Call time in minutes') }}
                </p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                <form method="GET" id="user-call-time-user-form" class="flex items-center gap-2">
                    @foreach(request()->except(['user_call_time_user']) as $field => $value)
                        <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                    @endforeach
                    <input type="hidden" name="user_call_time_user" value="{{ $user_call_time_selected ?? 0 }}">
                    <span class="flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-900 shadow-sm dark:border-indigo-500/40 dark:bg-indigo-900/30 dark:text-indigo-100">
                        <i class="bi bi-person-lines-fill text-base"></i>
                        {{ __('User') }}
                    </span>
                    <div class="relative">
                        <button type="button" id="user-call-time-user-filter" data-dropdown-toggle="user-call-time-user-dropdown"
                            class="btn-primary flex items-center justify-center gap-2 rounded-full px-5 py-2 text-sm">
                            <i class="bi bi-sliders text-base"></i>
                            <span>{{ __('Filter') }}</span>
                            <i class="bi bi-chevron-down text-xs"></i>
                        </button>
                        <div id="user-call-time-user-dropdown"
                            class="z-20 hidden w-56 rounded-lg border border-gray-100 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                            <ul class="max-h-64 overflow-y-auto py-1 text-sm text-gray-700 dark:text-gray-200">
                                <li>
                                    <button type="button"
                                        class="flex w-full items-center justify-between rounded-md px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white {{ (int)($user_call_time_selected ?? 0) === 0 ? 'bg-indigo-50 dark:bg-gray-700' : '' }}"
                                        data-filter-trigger
                                        data-form="user-call-time-user-form"
                                        data-input="user_call_time_user"
                                        data-value="0">
                                        <span>{{ __('All Users') }}</span>
                                        @if((int)($user_call_time_selected ?? 0) === 0)
                                            <i class="bi bi-check text-indigo-600 dark:text-white"></i>
                                        @endif
                                    </button>
                                </li>
                                @foreach($user_options as $option)
                                    <li>
                                        <button type="button"
                                            class="flex w-full items-center justify-between rounded-md px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white {{ (int)($user_call_time_selected ?? 0) === (int)$option['id'] ? 'bg-indigo-50 dark:bg-gray-700' : '' }}"
                                            data-filter-trigger
                                            data-form="user-call-time-user-form"
                                            data-input="user_call_time_user"
                                            data-value="{{ $option['id'] }}">
                                            <span>{{ $option['name'] }}</span>
                                            @if((int)($user_call_time_selected ?? 0) === (int)$option['id'])
                                                <i class="bi bi-check text-indigo-600 dark:text-white"></i>
                                            @endif
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </form>

                <form method="GET" id="user-call-time-period-form" class="flex items-center gap-2">
                    @foreach(request()->except(['user_call_time_period']) as $field => $value)
                        <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                    @endforeach
                    <input type="hidden" name="user_call_time_period" value="{{ $user_call_time_period ?? 'last_7_days' }}">
                    <span class="flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-900 shadow-sm dark:border-indigo-500/40 dark:bg-indigo-900/30 dark:text-indigo-100">
                        <i class="bi bi-calendar-week text-base"></i>
                        {{ __('Period') }}
                    </span>
                    <div class="relative">
                        <button type="button" id="user-call-time-period-filter" data-dropdown-toggle="user-call-time-period-dropdown"
                            class="btn-primary flex items-center justify-center gap-2 rounded-full px-5 py-2 text-sm">
                            <i class="bi bi-sliders text-base"></i>
                            <span>{{ __('Filter') }}</span>
                            <i class="bi bi-chevron-down text-xs"></i>
                        </button>
                        <div id="user-call-time-period-dropdown"
                            class="z-20 hidden w-56 rounded-lg border border-gray-100 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                            <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                                @php
                                    $callTimePeriods = [
                                        'last_24_hours' => __('Last 24 hours'),
                                        'last_7_days' => __('Last 7 days'),
                                        'this_month' => __('This month'),
                                    ];
                                @endphp
                                @foreach($callTimePeriods as $value => $label)
                                    <li>
                                        <button type="button"
                                            class="flex w-full items-center justify-between rounded-md px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white {{ ($user_call_time_period ?? 'last_7_days') === $value ? 'bg-indigo-50 dark:bg-gray-700' : '' }}"
                                            data-filter-trigger
                                            data-form="user-call-time-period-form"
                                            data-input="user_call_time_period"
                                            data-value="{{ $value }}">
                                            <span>{{ $label }}</span>
                                            @if(($user_call_time_period ?? 'last_7_days') === $value)
                                                <i class="bi bi-check text-indigo-600 dark:text-white"></i>
                                            @endif
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div id="user-call-time-chart" class="h-80"></div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const timelineData = @json($user_call_time_timeline ?? ['periods' => []]);
        const selectedUser = Number(@json($user_call_time_selected ?? 0));
        const selectedPeriod = @json($user_call_time_period ?? 'last_7_days');

        const getPeriodData = (payload, period) => {
            return payload?.periods?.[period] || { labels: [], total: [], byUser: {} };
        };

        const toPeriodLabels = (labels, period) => (labels || []).map((iso) => {
            const date = new Date(iso);
            if (period === 'last_24_hours') {
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
            return date.toLocaleDateString([], { month: 'short', day: '2-digit' });
        });

        const pickSeries = (periodData, userId) => {
            const labels = periodData?.labels || [];
            const byUser = periodData?.byUser || {};
            if (userId > 0) {
                return byUser[String(userId)] || Array(labels.length).fill(0);
            }
            return periodData?.total || Array(labels.length).fill(0);
        };

        const formatMinutes = (minutes) => `${(Number(minutes) || 0).toFixed(2)}m`;

        const createOptions = (payload, userId, period) => {
            const periodData = getPeriodData(payload, period);
            const labels = periodData?.labels || [];
            const data = pickSeries(periodData, userId);
            const maxVal = Math.max(1, ...data, 0);
            const yMax = Math.ceil(maxVal / 5) * 5;

            return {
                series: [{
                    name: '{{ __("Call Time") }}',
                    data
                }],
                chart: {
                    type: 'line',
                    height: 320,
                    toolbar: { show: false },
                    animations: { enabled: true, easing: 'easeinout', speed: 350 }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                colors: ['#635BFF'],
                markers: {
                    size: 3,
                    strokeWidth: 0
                },
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    categories: toPeriodLabels(labels, period),
                    labels: {
                        style: {
                            colors: '#64748b',
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    min: 0,
                    max: yMax,
                    labels: {
                        formatter: function(val) {
                            return formatMinutes(val);
                        },
                        style: { colors: '#64748b' }
                    }
                },
                grid: {
                    borderColor: '#e5e7eb',
                    strokeDashArray: 4
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return `${formatMinutes(val)} {{ __("call time") }}`;
                        }
                    }
                }
            };
        };

        const chartEl = document.querySelector('#user-call-time-chart');
        if (!chartEl || typeof ApexCharts === 'undefined') {
            return;
        }

        const chart = new ApexCharts(chartEl, createOptions(timelineData, selectedUser, selectedPeriod));
        chart.render();

        window.DashboardUserCallTimeChart = {
            selectedUser,
            selectedPeriod,
            update(payload) {
                chart.updateOptions(createOptions(payload, this.selectedUser, this.selectedPeriod), false, true);
            }
        };
    });
</script>
@endpush
