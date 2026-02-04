<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignLead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class ImportCampaignLeads implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Campaign identifier.
     */
    public int $campaignId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $campaignId)
    {
        $this->campaignId = $campaignId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $campaign = Campaign::find($this->campaignId);
        if (! $campaign) {
            return;
        }

    $campaign->update([
        'import_status' => 'processing',
        'import_started_at' => now(),
        'import_error' => null,
    ]);

    try {
        $path = Storage::disk('public')->path($campaign->file_path);
        $reader = Reader::createFromPath($path);
        $reader->setHeaderOffset(0);

        $rows = 0;
        $imported = 0;
        $chunk = [];

        foreach ($reader->getRecords() as $record) {
            $rows++;
            $normalizedRecord = array_change_key_case($record, CASE_LOWER);
            $phoneField = $normalizedRecord['phone'] ?? ($normalizedRecord['phone number'] ?? null);
            $phone = $this->normalizePhone($phoneField);
            if (! $phone) {
                continue;
            }

            $chunk[] = [
                'campaign_id' => $campaign->id,
                'phone' => $phone,
                'status' => 'new',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($chunk) === 1000) {
                CampaignLead::insert($chunk);
                $imported += count($chunk);
                $chunk = [];
            }
        }

        if ($chunk) {
            CampaignLead::insert($chunk);
            $imported += count($chunk);
        }

        $campaign->update([
            'import_status' => 'completed',
            'import_completed_at' => now(),
            'total_rows' => $rows,
            'imported_rows' => $imported,
        ]);
    } catch (\Throwable $e) {
        $campaign->update([
            'import_status' => 'failed',
            'import_error' => $e->getMessage(),
        ]);
        throw $e;
    }
    }

    protected function normalizePhone(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $digits = preg_replace('/[^\d+]/', '', $value);

        return strlen($digits) >= 7 ? $digits : null;
    }
}
