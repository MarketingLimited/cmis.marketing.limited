<?php

namespace App\Console\Commands;

use App\Services\Social\InstagramSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncInstagramData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instagram:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Instagram account content and metrics for all connected organizations.';

    public function __construct(private readonly InstagramSyncService $instagramSyncService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Instagram synchronization.');

        $processed = $this->instagramSyncService->syncAllActive();

        $message = sprintf('Instagram synchronization completed. Processed %d integration(s).', $processed);
        $this->info($message);
        Log::info($message);

        return self::SUCCESS;
    }
}
