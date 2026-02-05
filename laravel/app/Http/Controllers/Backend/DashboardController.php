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
use Illuminate\Support\Facades\Schema;
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
        $dialingUsers = DB::table('campaign_runs')
            ->where('is_running', true)
            ->distinct('user_id')
            ->count('user_id');
        $inCallUsers = DB::table('call_logs')
            ->whereNotNull('connected_at')
            ->whereNull('ended_at')
            ->distinct('user_id')
            ->count('user_id');
        $userActivityUser = (int) request()->get('user_activity_user', 0);
        $userActivityStats = $this->getUserActivityStats($userActivityUser);
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

    private function getUserActivityStats(int $userId = 0): array
    {
        $windowEnd = Carbon::now();
        $windowStart = $windowEnd->copy()->subHours(24);

        $query = DB::table('call_logs')
            ->whereBetween('created_at', [$windowStart, $windowEnd]);
        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        $stats = $query->selectRaw("
                COUNT(*) AS total_calls,
                SUM(CASE WHEN sip_status = 200 THEN 1 ELSE 0 END) AS ok_200,
                SUM(CASE WHEN sip_status = 503 THEN 1 ELSE 0 END) AS error_503,
                SUM(
                    CASE
                        WHEN sip_status IS NOT NULL AND sip_status NOT IN (200, 503)
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
            'window' => [
                'start' => $windowStart,
                'end' => $windowEnd
            ]
        ];
    }

    private function countActiveSessions(): int
    {
        if (! Schema::hasTable('sessions')) {
            return 0;
        }

        $lifetime = (int) config('session.lifetime', 120);
        $threshold = Carbon::now()->subMinutes($lifetime)->getTimestamp();

        return (int) DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $threshold)
            ->distinct('user_id')
            ->count('user_id');
    }
}
