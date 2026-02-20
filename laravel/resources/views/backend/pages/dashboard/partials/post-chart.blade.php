<div class="col-span-12">
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
        <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('User Activity') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Window: :start – :end', [
                        'start' => optional($user_activity_stats['window']['start'] ?? null)->format('M d, h:i A'),
                        'end' => optional($user_activity_stats['window']['end'] ?? null)->format('M d, h:i A')
                    ]) }}
                </p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                <form method="GET" id="user-activity-user-form" class="flex items-center gap-2">
                    @foreach(request()->except(['user_activity_user']) as $field => $value)
                        <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                    @endforeach
                    <input type="hidden" name="user_activity_user" value="{{ $user_activity_selected }}">
                    <span class="flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-900 shadow-sm dark:border-indigo-500/40 dark:bg-indigo-900/30 dark:text-indigo-100">
                        <i class="bi bi-people-fill text-base"></i>
                        {{ __('Users') }}
                    </span>
                    <div class="relative">
                        <button type="button" id="user-activity-user-filter" data-dropdown-toggle="user-activity-user-dropdown"
                            class="btn-primary flex items-center justify-center gap-2 rounded-full px-5 py-2 text-sm">
                            <i class="bi bi-sliders text-base"></i>
                            <span>{{ __('Filter') }}</span>
                            <i class="bi bi-chevron-down text-xs"></i>
                        </button>
                        <div id="user-activity-user-dropdown"
                            class="z-20 hidden w-56 rounded-lg border border-gray-100 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                            <ul class="max-h-64 overflow-y-auto py-1 text-sm text-gray-700 dark:text-gray-200">
                                <li>
                                    <button type="button"
                                        class="flex w-full items-center justify-between rounded-md px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white {{ (int)$user_activity_selected === 0 ? 'bg-indigo-50 dark:bg-gray-700' : '' }}"
                                        data-filter-trigger
                                        data-form="user-activity-user-form"
                                        data-input="user_activity_user"
                                        data-value="0">
                                        <span>{{ __('All Users') }}</span>
                                        @if((int)$user_activity_selected === 0)
                                            <i class="bi bi-check text-indigo-600 dark:text-white"></i>
                                        @endif
                                    </button>
                                </li>
                                @foreach($user_options as $option)
                                    <li>
                                        <button type="button"
                                            class="flex w-full items-center justify-between rounded-md px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white {{ (int)$user_activity_selected === (int)$option['id'] ? 'bg-indigo-50 dark:bg-gray-700' : '' }}"
                                            data-filter-trigger
                                            data-form="user-activity-user-form"
                                            data-input="user_activity_user"
                                            data-value="{{ $option['id'] }}">
                                            <span>{{ $option['name'] }}</span>
                                            @if((int)$user_activity_selected === (int)$option['id'])
                                                <i class="bi bi-check text-indigo-600 dark:text-white"></i>
                                            @endif
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </form>

                <form method="GET" id="user-activity-period-form" class="flex items-center gap-2">
                    @foreach(request()->except(['user_activity_period']) as $field => $value)
                        <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                    @endforeach
                    <input type="hidden" name="user_activity_period" value="{{ $user_activity_period ?? 'today' }}">
                    <span class="flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-900 shadow-sm dark:border-indigo-500/40 dark:bg-indigo-900/30 dark:text-indigo-100">
                        <i class="bi bi-clock-history text-base"></i>
                        {{ __('Period') }}
                    </span>
                    <div class="relative">
                        <button type="button" id="user-activity-period-filter" data-dropdown-toggle="user-activity-period-dropdown"
                            class="btn-primary flex items-center justify-center gap-2 rounded-full px-5 py-2 text-sm">
                            <i class="bi bi-sliders text-base"></i>
                            <span>{{ __('Filter') }}</span>
                            <i class="bi bi-chevron-down text-xs"></i>
                        </button>
                        <div id="user-activity-period-dropdown"
                            class="z-20 hidden w-60 rounded-lg border border-gray-100 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                            <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                                @php
                                    $periodOptions = [
                                        'today' => __('Today (9 PM - 9 PM)'),
                                        'last_7_days' => __('Last 7 days'),
                                        'last_30_days' => __('Last 30 days'),
                                        'this_month' => __('This month'),
                                    ];
                                @endphp
                                @foreach($periodOptions as $value => $label)
                                    <li>
                                        <button type="button"
                                            class="flex w-full items-center justify-between rounded-md px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white {{ ($user_activity_period ?? 'today') === $value ? 'bg-indigo-50 dark:bg-gray-700' : '' }}"
                                            data-filter-trigger
                                            data-form="user-activity-period-form"
                                            data-input="user_activity_period"
                                            data-value="{{ $value }}">
                                            <span>{{ $label }}</span>
                                            @if(($user_activity_period ?? 'today') === $value)
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
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 mb-6">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center dark:border-gray-700 dark:bg-gray-900/40">
                <p class="text-xs uppercase text-gray-500 dark:text-gray-400">{{ __('Total Dialed') }}</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white" data-activity-metric="total">{{ number_format($user_activity_stats['total']) }}</p>
            </div>
            <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-center dark:border-green-900/50 dark:bg-green-900/10">
                <p class="text-xs uppercase text-green-600 dark:text-green-300">{{ __('200 OK') }}</p>
                <p class="text-2xl font-semibold text-green-700 dark:text-green-300" data-activity-metric="ok200">{{ number_format($user_activity_stats['ok_200']) }}</p>
            </div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-center dark:border-amber-900/50 dark:bg-amber-900/10">
                <p class="text-xs uppercase text-amber-600 dark:text-amber-300">{{ __('503 Errors') }}</p>
                <p class="text-2xl font-semibold text-amber-700 dark:text-amber-200" data-activity-metric="err503">{{ number_format($user_activity_stats['err_503']) }}</p>
            </div>
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-center dark:border-red-900/50 dark:bg-red-900/10">
                <p class="text-xs uppercase text-red-600 dark:text-red-300">{{ __('Other Errors') }}</p>
                <p class="text-2xl font-semibold text-red-700 dark:text-red-300" data-activity-metric="other">{{ number_format($user_activity_stats['other']) }}</p>
            </div>
        </div>
        <div id="user-activity-chart" class="h-80"></div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const activityData = @json($user_activity_stats);
        const labels = [
            '{{ __("Total Dialed") }}',
            '{{ __("200 OK") }}',
            '{{ __("503 Errors") }}',
            '{{ __("Other Errors") }}'
        ];

        const buildSeries = (activity) => ([
            activity.total || 0,
            activity.ok200 || activity.ok_200 || 0,
            activity.err503 || activity.err_503 || 0,
            activity.other || 0
        ]);

        const createChartOptions = (activity) => {
            const data = buildSeries(activity);
            const maxVal = Math.max(...data);
            const yMax = Math.max(10, Math.ceil((maxVal || 0) / 10) * 10);
            const yTicks = Math.max(1, Math.round(yMax / 10));

            return {
                series: [{
                    name: '{{ __("Calls") }}',
                    data
                }],
                chart: {
                    type: 'bar',
                    height: 320,
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        columnWidth: '45%',
                        distributed: true,
                        borderRadius: 6
                    }
                },
                colors: ['#6366F1', '#22C55E', '#F97316', '#EF4444'],
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return val.toLocaleString();
                    },
                    style: {
                        colors: ['#1f2937']
                    }
                },
                xaxis: {
                    categories: labels,
                    labels: {
                        style: {
                            colors: ['#64748b'],
                            fontSize: '13px'
                        }
                    }
                },
                yaxis: {
                    min: 0,
                    max: yMax,
                    tickAmount: yTicks,
                    labels: {
                        formatter: function (val) {
                            return val.toLocaleString();
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
                        formatter: function (val) {
                            return val.toLocaleString() + ' {{ __("calls") }}';
                        }
                    }
                }
            };
        };

        const chart = new ApexCharts(document.querySelector("#user-activity-chart"), createChartOptions(activityData));
        chart.render();

        const updateActivityCards = (activity) => {
            const map = {
                total: activity.total || 0,
                ok200: activity.ok200 || activity.ok_200 || 0,
                err503: activity.err503 || activity.err_503 || 0,
                other: activity.other || 0
            };
            Object.entries(map).forEach(([key, value]) => {
                const el = document.querySelector(`[data-activity-metric=\"${key}\"]`);
                if (el) {
                    el.textContent = Number(value).toLocaleString();
                }
            });
        };

        window.DashboardActivityChart = {
            selectedUser: Number(@json($user_activity_selected ?? 0)),
            selectedPeriod: @json($user_activity_period ?? 'today'),
            update(activity) {
                updateActivityCards(activity);
                chart.updateOptions(createChartOptions(activity), false, true);
            }
        };

        document.querySelectorAll('[data-filter-trigger]').forEach((trigger) => {
            trigger.addEventListener('click', function(event) {
                event.preventDefault();
                const formId = this.getAttribute('data-form');
                const inputName = this.getAttribute('data-input');
                const value = this.getAttribute('data-value');
                if (!formId || !inputName) {
                    return;
                }
                const form = document.getElementById(formId);
                if (!form) {
                    return;
                }
                let input = form.querySelector(`input[name="${inputName}"]`);
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = inputName;
                    form.appendChild(input);
                }
                input.value = value;
                form.submit();
            });
        });
    });
</script>
@endpush
