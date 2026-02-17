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
use PhpOffice\PhpSpreadsheet\IOFactory;

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

        $rows = 0;
        $imported = 0;
        $chunk = [];

        foreach ($this->recordsFromFile($path) as $record) {
            $rows++;
            $normalizedRecord = array_change_key_case($record, CASE_LOWER);
            $phoneField = $normalizedRecord['phone']
                ?? ($normalizedRecord['phone number'] ?? null)
                ?? ($normalizedRecord['phonenumber'] ?? null)
                ?? $this->extractPhoneCandidateFromValues($record);
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

    protected function recordsFromFile(string $path): iterable
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            $reader = Reader::createFromPath($path);
            $firstRow = $reader->fetchOne();
            $hasHeader = $this->hasPhoneHeader($firstRow);

            if ($hasHeader) {
                $reader->setHeaderOffset(0);
                yield from $reader->getRecords();
                return;
            }

            foreach ($reader->getRecords() as $row) {
                yield $row;
            }
            return;
        }

        if (! in_array($extension, ['xlsx', 'xls'], true)) {
            throw new \RuntimeException('Unsupported campaign file format. Please upload CSV, XLS, or XLSX.');
        }

        if (! class_exists(IOFactory::class)) {
            throw new \RuntimeException('Excel import support is not installed. Run composer install/update.');
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, false, false, false);

        if (count($rows) === 0) {
            return;
        }

        $firstRow = (array) array_shift($rows);
        $hasHeader = $this->hasPhoneHeader($firstRow);

        if (! $hasHeader) {
            if (array_filter($firstRow, static fn ($value) => $value !== null && $value !== '')) {
                yield $firstRow;
            }
            foreach ($rows as $row) {
                if (array_filter((array) $row, static fn ($value) => $value !== null && $value !== '')) {
                    yield $row;
                }
            }
            return;
        }

        $headers = $this->normalizeHeaders($firstRow);

        foreach ($rows as $row) {
            $record = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $record[$header] = $row[$index] ?? null;
            }
            if ($record !== []) {
                yield $record;
            }
        }
    }

    protected function normalizeHeaders(array $headers): array
    {
        return array_map(
            static fn ($value) => preg_replace('/\s+/', ' ', strtolower(trim((string) ($value ?? '')))),
            $headers
        );
    }

    protected function hasPhoneHeader(array $row): bool
    {
        $headers = $this->normalizeHeaders($row);
        $phoneHeaders = [
            'phone',
            'phone number',
            'phonenumber',
            'mobile',
            'mobile phone',
            'contact number',
            'telephone',
        ];

        foreach ($headers as $header) {
            if (in_array($header, $phoneHeaders, true)) {
                return true;
            }
        }

        return false;
    }

    protected function extractPhoneCandidateFromValues(array $values): ?string
    {
        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }
            $raw = trim((string) $value);
            if ($raw === '') {
                continue;
            }
            $normalized = $this->normalizePhone($raw);
            if ($normalized) {
                return $raw;
            }
        }

        return null;
    }

    protected function normalizePhone(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        $raw = trim((string) $value);
        $digits = preg_replace('/\D+/', '', $raw);
        if (! $digits) {
            return null;
        }
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return null;
        }

        return str_starts_with($digits, '1') && strlen($digits) === 11
            ? "+{$digits}"
            : (str_starts_with($raw, '+') ? "+{$digits}" : $digits);
    }
}
