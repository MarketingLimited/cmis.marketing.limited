<?php

namespace App\Console\Commands;

use App\Jobs\SyncPlatformDataJob;
use App\Models\Core\Integration;
use Illuminate\Console\Command;

class SyncPlatformsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cmis:sync-platforms
                            {--platform= : Specific platform to sync}
                            {--type=full : Sync type (channels, ad_accounts, metrics, full)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data from connected platforms';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $platform = $this->option('platform');
        $syncType = $this->option('type');

        $this->info('Finding active integrations...');

        $query = Integration::where('status', 'active');

        if ($platform) {
            $query->where('platform', $platform);
        }

        $integrations = $query->get();

        if ($integrations->isEmpty()) {
            $this->warn('No active integrations found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$integrations->count()} active integration(s).");

        $bar = $this->output->createProgressBar($integrations->count());
        $bar->start();

        foreach ($integrations as $integration) {
            SyncPlatformDataJob::dispatch($integration, $syncType);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Dispatched {$integrations->count()} sync job(s).");

        return Command::SUCCESS;
    }
}
