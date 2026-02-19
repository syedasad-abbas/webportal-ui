<div class="col-span-12">
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
        <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('User Call Logs') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Call time in minutes') }}
                </p>
            </div>
            <form method="GET" class="flex items-center gap-2">
                @foreach(request()->except(['user_call_time_user', 'user_call_time_period']) as $field => $value)
                    <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                @endforeach
                <label for="user-call-time-user" class="text-sm font-medium text-gray-600 dark:text-gray-300">
                    {{ __('User') }}
                </label>
                <select
                    id="user-call-time-user"
                    name="user_call_time_user"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    onchange="this.form.submit()"
                >
                    <option value="0">{{ __('All Users') }}</option>
                    @foreach($user_options as $option)
                        <option value="{{ $option['id'] }}" @selected(($user_call_time_selected ?? 0) == $option['id'])>
                            {{ $option['name'] }}
                        </option>
                    @endforeach
                </select>
                <label for="user-call-time-period" class="text-sm font-medium text-gray-600 dark:text-gray-300">
                    {{ __('Period') }}
                </label>
                <select
                    id="user-call-time-period"
                    name="user_call_time_period"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    onchange="this.form.submit()"
                >
                    <option value="last_24_hours" @selected(($user_call_time_period ?? 'last_7_days') === 'last_24_hours')>{{ __('Last 24 hours') }}</option>
                    <option value="last_7_days" @selected(($user_call_time_period ?? 'last_7_days') === 'last_7_days')>{{ __('Last 7 days') }}</option>
                    <option value="this_month" @selected(($user_call_time_period ?? 'last_7_days') === 'this_month')>{{ __('This month') }}</option>
                </select>
            </form>
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
                colors: ['#0EA5E9'],
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
