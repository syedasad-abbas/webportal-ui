<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SipDirectorySyncService;
use Illuminate\Console\Command;

class SyncSipDirectory extends Command
{
    protected $signature = 'freeswitch:sync-sip-directory';

    protected $description = 'Sync SIP credentials into the FreeSWITCH directory XML files';

    public function __construct(
        private readonly SipDirectorySyncService $sipDirectorySyncService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $changed = $this->sipDirectorySyncService->syncAll();

        $this->info(sprintf('SIP directory sync complete. %d file(s) updated.', $changed));

        return self::SUCCESS;
    }
}
