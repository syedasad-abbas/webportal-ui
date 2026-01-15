<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class CampaignStatsUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:campaign-stats-update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Set your target user (can be looped later for all users if needed)
        $targetUser = '2238';
        $targetDate = '2025-06-16';

        $start = $targetDate . ' 00:00:00';
        $end = $targetDate . ' 23:59:59';

        // Raw query from vicidial
        $data = DB::connection('asterisk') // default Laravel DB, change if needed
            ->table('vicidial_agent_log')
            ->selectRaw("
                user,
                SUM(talk_sec) AS talk_sec,
                SUM(pause_sec) AS pause_sec,
                SUM(wait_sec) AS wait_sec,
                SUM(dispo_sec) AS dispo_sec,
                MIN(event_time) AS first_login,
                MAX(event_time) AS last_log_activity
            ")
            ->where('user', $targetUser)
            ->whereBetween('event_time', [$start, $end])
            ->groupBy('user')
            ->first();

        if (!$data) {
            $this->info("No data found for user $targetUser on $targetDate");
            return;
        }

        // Convert values to minutes
        $loginMinutes = round((strtotime($data->last_log_activity) - strtotime($data->first_login)) / 60);
        $pauseMinutes = round($data->pause_sec / 60);
        $talkMinutes = round(($data->talk_sec + $data->wait_sec + $data->dispo_sec) / 60); // invoice = all but pause
        $userId = intval($data->user); // adjust conversion if needed

        // Insert into agent_time_logs table
        DB::table('agent_time_logs')->updateOrInsert(
            [
                'user_id' => $userId,
                'log_date' => $targetDate,
            ],
            [
                'login_minutes' => $loginMinutes,
                'pause_minutes' => $pauseMinutes,
                'invoice_minutes' => $talkMinutes,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->info("Data inserted for user $targetUser on $targetDate.");
        //
    }
}
