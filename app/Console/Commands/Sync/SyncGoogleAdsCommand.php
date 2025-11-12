<?php

namespace App\Console\Commands\Sync;

use Illuminate\Console\Command;
use App\Models\AdPlatformIntegration;
use App\Jobs\SyncPlatformDataJob;
use Illuminate\Support\Facades\Log;

class SyncGoogleAdsCommand extends Command
{
    protected $signature = 'sync:google-ads {--org= : Organization ID to sync}';
    protected $description = 'Sync Google Ads data (campaigns, ad groups, ads, performance)';

    public function handle()
    {
        $this->info('🔄 Starting Google Ads sync...');
        $this->newLine();

        $orgId = $this->option('org');

        $query = AdPlatformIntegration::where('platform', 'google')
            ->where('status', 'active');

        if ($orgId) {
            $query->where('org_id', $orgId);
            $this->info("Syncing for organization: {$orgId}");
        } else {
            $this->info('Syncing for all organizations');
        }

        $integrations = $query->get();

        if ($integrations->isEmpty()) {
            $this->warn('⚠️  No active Google Ads integrations found');
            return self::SUCCESS;
        }

        $this->info("Found {$integrations->count()} Google Ads integration(s)");
        $this->newLine();

        $bar = $this->output->createProgressBar($integrations->count());
        $bar->start();

        $synced = 0;
        $failed = 0;

        foreach ($integrations as $integration) {
            try {
                SyncPlatformDataJob::dispatch($integration->ad_platform_integration_id, 'google');
                $synced++;
                $bar->advance();
            } catch (\Exception $e) {
                $failed++;
                Log::error('Google Ads sync failed', [
                    'integration_id' => $integration->ad_platform_integration_id,
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

        $this->info('Google Ads sync completed. Jobs are being processed in the background.');

        return self::SUCCESS;
    }
}
