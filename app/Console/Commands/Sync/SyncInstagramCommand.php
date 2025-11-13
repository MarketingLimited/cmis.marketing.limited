<?php

namespace App\Console\Commands\Sync;

use Illuminate\Console\Command;
use App\Models\Integration;
use App\Jobs\SyncPlatformDataJob;
use Illuminate\Support\Facades\Log;

class SyncInstagramCommand extends Command
{
    protected $signature = 'sync:instagram {--org= : Organization ID to sync}';
    protected $description = 'Sync Instagram data (posts, stories, insights)';

    public function handle()
    {
        $this->info('🔄 Starting Instagram sync...');
        $this->newLine();

        $orgId = $this->option('org');

        $query = Integration::where('platform', 'instagram')
            ->where('status', 'active');

        if ($orgId) {
            $query->where('org_id', $orgId);
            $this->info("Syncing for organization: {$orgId}");
        } else {
            $this->info('Syncing for all organizations');
        }

        $integrations = $query->get();

        if ($integrations->isEmpty()) {
            $this->warn('⚠️  No active Instagram integrations found');
            return self::SUCCESS;
        }

        $this->info("Found {$integrations->count()} Instagram integration(s)");
        $this->newLine();

        $bar = $this->output->createProgressBar($integrations->count());
        $bar->start();

        $synced = 0;
        $failed = 0;

        foreach ($integrations as $integration) {
            try {
                SyncPlatformDataJob::dispatch($integration->integration_id, 'instagram');
                $synced++;
                $bar->advance();
            } catch (\Exception $e) {
                $failed++;
                Log::error('Instagram sync failed', [
                    'integration_id' => $integration->integration_id,
                    'error' => $e->getMessage()
                ]);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Sync jobs queued: {$synced}");
        if ($failed > 0) {
            $this->error("❌ Failed: {$failed}");
        }

        $this->info('Instagram sync completed. Jobs are being processed in the background.');

        return self::SUCCESS;
    }
}
