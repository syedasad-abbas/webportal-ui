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
            ->whereNotNull('connected_at')
            ->whereNull('ended_at')
            ->select('user_id')
            ->distinct();

        $dialingUsers = DB::query()
            ->fromSub(function ($query) {
                $query->from('call_logs')
                    ->select('user_id')
                    ->whereNull('connected_at')
                    ->whereNull('ended_at')
                    ->distinct()
                    ->union(
                        DB::table('campaign_runs')
                            ->select('user_id')
                            ->where('is_running', true)
                            ->distinct()
                    );
            }, 'dialing')
            ->whereNotIn('user_id', $inCallUsersQuery)
            ->distinct('user_id')
            ->count('user_id');
        $inCallUsers = DB::table('call_logs')
            ->whereNotNull('connected_at')
            ->whereNull('ended_at')
            ->distinct('user_id')
            ->count('user_id');
        $userActivityUser = (int) request()->get('user_activity_user', 0);
        $userActivityPeriod = (string) request()->get('user_activity_period', 'today');
        $allowedUserActivityPeriods = ['today', 'last_7_days', 'last_30_days', 'this_month'];
        if (!in_array($userActivityPeriod, $allowedUserActivityPeriods, true)) {
            $userActivityPeriod = 'today';
        }
        $userActivityStats = $this->getUserActivityStats($userActivityUser, $userActivityPeriod);
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
                SUM(CASE WHEN COALESCE(sip_status, 0) = 200 OR status = 'completed' THEN 1 ELSE 0 END) AS ok_200,
                SUM(
                    CASE
                        WHEN COALESCE(sip_status, 0) = 503
                          AND NOT (COALESCE(sip_status, 0) = 200 OR status = 'completed')
                        THEN 1 ELSE 0
                    END
                ) AS error_503,
                SUM(
                    CASE
                        WHEN NOT (COALESCE(sip_status, 0) = 200 OR status = 'completed')
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
