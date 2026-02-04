<div class="col-span-12">
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
        <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('User Activity') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Dialed calls grouped by SIP outcomes') }}</p>
            </div>
            <form method="GET" class="flex items-center gap-2">
                @foreach(request()->except('user_activity_user') as $field => $value)
                    <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                @endforeach
                <label for="user-activity-filter" class="text-sm font-medium text-gray-600 dark:text-gray-300">
                    {{ __('User Activity') }}
                </label>
                <select
                    id="user-activity-filter"
                    name="user_activity_user"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    onchange="this.form.submit()"
                >
                    <option value="0">{{ __('All Users') }}</option>
                    @foreach($user_options as $option)
                        <option value="{{ $option['id'] }}" @selected($user_activity_selected == $option['id'])>
                            {{ $option['name'] }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 mb-6">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-center dark:border-gray-700 dark:bg-gray-900/40">
                <p class="text-xs uppercase text-gray-500 dark:text-gray-400">{{ __('Total Dialed') }}</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($user_activity_stats['total']) }}</p>
            </div>
            <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-center dark:border-green-900/50 dark:bg-green-900/10">
                <p class="text-xs uppercase text-green-600 dark:text-green-300">{{ __('200 OK') }}</p>
                <p class="text-2xl font-semibold text-green-700 dark:text-green-300">{{ number_format($user_activity_stats['ok_200']) }}</p>
            </div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-center dark:border-amber-900/50 dark:bg-amber-900/10">
                <p class="text-xs uppercase text-amber-600 dark:text-amber-300">{{ __('503 Errors') }}</p>
                <p class="text-2xl font-semibold text-amber-700 dark:text-amber-200">{{ number_format($user_activity_stats['err_503']) }}</p>
            </div>
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-center dark:border-red-900/50 dark:bg-red-900/10">
                <p class="text-xs uppercase text-red-600 dark:text-red-300">{{ __('Other Errors') }}</p>
                <p class="text-2xl font-semibold text-red-700 dark:text-red-300">{{ number_format($user_activity_stats['other']) }}</p>
            </div>
        </div>
        <div id="user-activity-chart" class="h-80"></div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const activityData = @json($user_activity_stats);

        const categories = [
            '{{ __("Total Dialed") }}',
            '{{ __("200 OK") }}',
            '{{ __("503 Errors") }}',
            '{{ __("Other Errors") }}'
        ];

        const seriesData = [
            activityData.total || 0,
            activityData.ok_200 || 0,
            activityData.err_503 || 0,
            activityData.other || 0
        ];

        const options = {
            series: [{
                name: '{{ __("Calls") }}',
                data: seriesData
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
                categories: categories,
                labels: {
                    style: {
                        colors: ['#64748b'],
                        fontSize: '13px'
                    }
                }
            },
            yaxis: {
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

        const chart = new ApexCharts(document.querySelector("#user-activity-chart"), options);
        chart.render();
    });
</script>
@endpush
