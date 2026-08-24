<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Charts\PostChartService;
use App\Services\Charts\UserChartService;
use App\Services\LanguageService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __construct(
        private readonly UserChartService $userChartService,
        private readonly LanguageService $languageService,
        private readonly PostChartService $postChartService
    ) {
    }

    public function index()
    {
        $this->checkAuthorization(auth()->user(), ['dashboard.view']);

        $totalUsers = User::count();
        $activeUsers = $this->countActiveSessions();
        $offlineUsers = max($totalUsers - $activeUsers, 0);
        $inCallUsersQuery = DB::table('call_logs')
            ->whereNull('ended_at')
            ->where(function ($query) {
                $query->whereNotNull('connected_at')
                    ->orWhere('status', 'in_call');
            })
            ->select('user_id')
            ->distinct();

        $dialingUsers = DB::query()
            ->fromSub(function ($query) {
                $query->from('call_logs')
                    ->select('user_id')
                    ->whereNull('ended_at')
                    ->whereIn('status', ['queued', 'ringing', 'trying'])
                    ->distinct();
            }, 'dialing')
            ->whereNotIn('user_id', $inCallUsersQuery)
            ->distinct('user_id')
            ->count('user_id');
        $inCallUsers = DB::table('call_logs')
            ->whereNull('ended_at')
            ->where(function ($query) {
                $query->whereNotNull('connected_at')
                    ->orWhere('status', 'in_call');
            })
            ->distinct('user_id')
            ->count('user_id');
        $userActivityUser = (int) request()->get('user_activity_user', 0);
        $userActivityPeriod = (string) request()->get('user_activity_period', 'today');
        $allowedUserActivityPeriods = ['today', 'last_7_days', 'last_30_days', 'this_month'];
        if (!in_array($userActivityPeriod, $allowedUserActivityPeriods, true)) {
            $userActivityPeriod = 'today';
        }
        $userActivityStats = $this->getUserActivityStats($userActivityUser, $userActivityPeriod);
        $userCallTimeSelected = (int) request()->get('user_call_time_user', 0);
        $userCallTimePeriod = (string) request()->get('user_call_time_period', 'last_7_days');
        $allowedCallTimePeriods = ['last_24_hours', 'last_7_days', 'this_month'];
        if (!in_array($userCallTimePeriod, $allowedCallTimePeriods, true)) {
            $userCallTimePeriod = 'last_7_days';
        }
        $userCallTimeTimeline = $this->getUserCallTimeTimeline();
        $userOptions = User::orderBy('external_name')
            ->get(['id', 'external_name'])
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->external_name ?: $user->email,
            ]);

        return view(
            'backend.pages.dashboard.index',
            [
                'total_users' => number_format($totalUsers),
                'active_users' => number_format($activeUsers),
                'offline_users' => number_format($offlineUsers),
                'dialing_users' => number_format($dialingUsers),
                'in_call_users' => number_format($inCallUsers),
                'user_activity_stats' => $userActivityStats,
                'user_activity_selected' => $userActivityUser,
                'user_activity_period' => $userActivityPeriod,
                'user_call_time_selected' => $userCallTimeSelected,
                'user_call_time_period' => $userCallTimePeriod,
                'user_call_time_timeline' => $userCallTimeTimeline,
                'user_options' => $userOptions,
                'total_roles' => number_format(Role::count()),
                'total_permissions' => number_format(Permission::count()),
                'languages' => [
                    'total' => number_format(count($this->languageService->getLanguages())),
                    'active' => number_format(count($this->languageService->getActiveLanguages())),
                ],
                'user_growth_data' => $this->userChartService->getUserGrowthData(
                    request()->get('chart_filter_period', 'last_12_months')
                )->getData(true),
                'user_history_data' => $this->userChartService->getUserHistoryData(),
                'post_stats' => $this->postChartService->getPostActivityData(
                    request()->get('post_chart_filter_period', 'last_6_months')
                ),
                'breadcrumbs' => [
                    'title' => __('Dashboard'),
                    'show_home' => false,
                    'show_current' => false,
                ],
            ]
        );
    }

    private function getUserActivityStats(int $userId = 0, string $period = 'today'): array
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $now = Carbon::now($timezone);
        $anchorHour = 21;
        $anchor = $now->copy()->setTime($anchorHour, 0, 0);
        $todayWindowStart = $now->lt($anchor) ? $anchor->subDay() : $anchor;
        $todayWindowEnd = $todayWindowStart->copy()->addDay();

        switch ($period) {
            case 'last_7_days':
                $windowEnd = $todayWindowEnd->copy();
                $windowStart = $windowEnd->copy()->subDays(7);
                break;
            case 'last_30_days':
                $windowEnd = $todayWindowEnd->copy();
                $windowStart = $windowEnd->copy()->subDays(30);
                break;
            case 'this_month':
                $windowStart = $now->copy()->startOfMonth();
                $windowEnd = $now->copy();
                break;
            case 'today':
            default:
                $windowStart = $todayWindowStart;
                $windowEnd = $todayWindowEnd;
                break;
        }

        $dbWindowStart = $windowStart->copy()->setTimezone('UTC');
        $dbWindowEnd = $windowEnd->copy()->setTimezone('UTC');

        $query = DB::table('call_logs')
            ->whereBetween(DB::raw('COALESCE(created_at, ended_at, connected_at)'), [$dbWindowStart, $dbWindowEnd]);
        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        $stats = $query->selectRaw("
                COUNT(*) AS total_calls,
                SUM(
                    CASE
                        WHEN COALESCE(duration_seconds, 0) > 0 THEN 1
                        ELSE 0
                    END
                ) AS ok_200,
                SUM(
                    CASE
                        WHEN COALESCE(sip_status, 0) = 503
                          AND NOT (COALESCE(duration_seconds, 0) > 0)
                        THEN 1 ELSE 0
                    END
                ) AS error_503,
                SUM(
                    CASE
                        WHEN NOT (COALESCE(duration_seconds, 0) > 0)
                          AND COALESCE(sip_status, 0) <> 503
                        THEN 1 ELSE 0
                    END
                ) AS other_calls
            ")
            ->first();

        return [
            'total' => (int) ($stats->total_calls ?? 0),
            'ok_200' => (int) ($stats->ok_200 ?? 0),
            'err_503' => (int) ($stats->error_503 ?? 0),
            'other' => (int) ($stats->other_calls ?? 0),
            'period' => $period,
            'window' => [
                'start' => $windowStart,
                'end' => $windowEnd
            ]
        ];
    }

    private function getUserCallTimeTimeline(): array
    {
        $last24Rows = DB::select(
            "
            WITH bounds AS (
                SELECT
                    date_trunc('hour', NOW()) - INTERVAL '23 hour' AS window_start,
                    date_trunc('hour', NOW()) + INTERVAL '1 hour' AS window_end
            ),
            buckets AS (
                SELECT generate_series(window_start, window_end - INTERVAL '1 hour', INTERVAL '1 hour') AS bucket_utc
                FROM bounds
            ),
            bucketed AS (
                SELECT
                    b.bucket_utc,
                    c.user_id,
                    COALESCE(c.duration_seconds, 0)::int AS seconds_in_bucket
                FROM buckets b
                JOIN call_logs c
                    ON COALESCE(c.ended_at, c.created_at) >= b.bucket_utc
                    AND COALESCE(c.ended_at, c.created_at) < b.bucket_utc + INTERVAL '1 hour'
            )
            SELECT bucket_utc, user_id, SUM(seconds_in_bucket)::int AS seconds
            FROM bucketed
            GROUP BY bucket_utc, user_id
            ORDER BY bucket_utc, user_id
            "
        );

        $last7Rows = DB::select(
            "
            WITH bounds AS (
                SELECT
                    date_trunc('day', NOW()) - INTERVAL '6 day' AS window_start,
                    date_trunc('day', NOW()) + INTERVAL '1 day' AS window_end
            ),
            buckets AS (
                SELECT generate_series(window_start, window_end - INTERVAL '1 day', INTERVAL '1 day') AS bucket_utc
                FROM bounds
            ),
            bucketed AS (
                SELECT
                    b.bucket_utc,
                    c.user_id,
                    COALESCE(c.duration_seconds, 0)::int AS seconds_in_bucket
                FROM buckets b
                JOIN call_logs c
                    ON COALESCE(c.ended_at, c.created_at) >= b.bucket_utc
                    AND COALESCE(c.ended_at, c.created_at) < b.bucket_utc + INTERVAL '1 day'
            )
            SELECT bucket_utc, user_id, SUM(seconds_in_bucket)::int AS seconds
            FROM bucketed
            GROUP BY bucket_utc, user_id
            ORDER BY bucket_utc, user_id
            "
        );

        $thisMonthRows = DB::select(
            "
            WITH bounds AS (
                SELECT
                    date_trunc('month', NOW()) AS window_start,
                    date_trunc('day', NOW()) + INTERVAL '1 day' AS window_end
            ),
            buckets AS (
                SELECT generate_series(window_start, window_end - INTERVAL '1 day', INTERVAL '1 day') AS bucket_utc
                FROM bounds
            ),
            bucketed AS (
                SELECT
                    b.bucket_utc,
                    c.user_id,
                    COALESCE(c.duration_seconds, 0)::int AS seconds_in_bucket
                FROM buckets b
                JOIN call_logs c
                    ON COALESCE(c.ended_at, c.created_at) >= b.bucket_utc
                    AND COALESCE(c.ended_at, c.created_at) < b.bucket_utc + INTERVAL '1 day'
            )
            SELECT bucket_utc, user_id, SUM(seconds_in_bucket)::int AS seconds
            FROM bucketed
            GROUP BY bucket_utc, user_id
            ORDER BY bucket_utc, user_id
            "
        );

        $buildLabels = static function (int $days): array {
            $end = Carbon::now('UTC')->startOfDay();
            $labels = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $labels[] = $end->copy()->subDays($i)->toIso8601String();
            }
            return $labels;
        };

        $buildHourLabels = static function (int $hours): array {
            $end = Carbon::now('UTC')->startOfHour();
            $labels = [];
            for ($i = $hours - 1; $i >= 0; $i--) {
                $labels[] = $end->copy()->subHours($i)->toIso8601String();
            }
            return $labels;
        };

        $last7Labels = $buildLabels(7);
        $last24Labels = $buildHourLabels(24);
        $monthStart = Carbon::now('UTC')->startOfMonth();
        $monthDays = (int) max(1, (int) $monthStart->diffInDays(Carbon::now('UTC')->startOfDay()) + 1);
        $thisMonthLabels = $buildLabels($monthDays);

        $toSeries = static function (array $rows, array $labels, string $granularity = 'day'): array {
            $indexByLabel = [];
            foreach ($labels as $idx => $label) {
                $indexByLabel[$label] = $idx;
            }

            $total = array_fill(0, count($labels), 0);
            $byUser = [];

            foreach ($rows as $row) {
                $bucket = Carbon::parse((string) $row->bucket_utc, 'UTC');
                $bucket = $granularity === 'hour'
                    ? $bucket->startOfHour()->toIso8601String()
                    : $bucket->startOfDay()->toIso8601String();
                if (!array_key_exists($bucket, $indexByLabel)) {
                    continue;
                }

                $idx = $indexByLabel[$bucket];
                $minutes = round(((int) ($row->seconds ?? 0)) / 60, 2);
                $userKey = (string) ((int) ($row->user_id ?? 0));

                $total[$idx] += $minutes;
                if (!isset($byUser[$userKey])) {
                    $byUser[$userKey] = array_fill(0, count($labels), 0);
                }
                $byUser[$userKey][$idx] += $minutes;
            }

            return [
                'labels' => $labels,
                'total' => $total,
                'byUser' => $byUser,
            ];
        };

        return [
            'periods' => [
                'last_24_hours' => $toSeries($last24Rows, $last24Labels, 'hour'),
                'last_7_days' => $toSeries($last7Rows, $last7Labels, 'day'),
                'this_month' => $toSeries($thisMonthRows, $thisMonthLabels, 'day'),
            ],
        ];
    }

    private function countActiveSessions(): int
    {
        $presenceMinutes = (int) config(
            'session.presence_window_minutes',
            (int) config('session.lifetime', 120)
        );
        $presenceMinutes = $presenceMinutes > 0 ? $presenceMinutes : 5;
        $threshold = Carbon::now()->subMinutes($presenceMinutes)->timestamp;

        if (config('session.driver') === 'database') {
            return (int) DB::table('sessions')
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', $threshold)
                ->distinct('user_id')
                ->count('user_id');
        }

        return (int) User::query()
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', Carbon::now()->subMinutes($presenceMinutes))
            ->count();
    }

}
