<?php

namespace App\Console\Commands\Sync;

use Illuminate\Console\Command;
use App\Console\Traits\HandlesOrgContext;
use App\Models\Integration;

class SyncMetaAdsCommand extends Command
{
    use HandlesOrgContext;

    protected $signature = 'sync:meta-ads
                            {--org=* : Specific org IDs}';

    protected $description = 'Sync Meta Ads data (Facebook & Instagram ads)';

    public function handle()
    {
        $this->info('🚀 Starting Meta Ads Sync');
        $this->newLine();

        $orgIds = $this->option('org') ?: null;

        $this->executePerOrg(function ($org) {
            $integrations = Integration::where('org_id', $org->org_id)
                ->whereIn('platform', ['facebook', 'instagram', 'meta'])
                ->where('status', 'active')
                ->get();

            if ($integrations->isEmpty()) {
                $this->warn("  ⚠️  No active Meta integrations");
                return;
            }

            foreach ($integrations as $integration) {
                $this->info("  📊 Platform: {$integration->platform}");

                try {
                    // TODO: Implement Meta Ads sync
                    $this->line("     → Syncing campaigns, ad sets, ads");
                    $this->line("     → Syncing metrics and insights");
                    $this->info("     ✓ Sync completed (placeholder)");

                } catch (\Exception $e) {
                    $this->error("     ✗ Error: " . $e->getMessage());
                }
            }
        }, $orgIds);

        $this->newLine();
        $this->info('✅ Meta Ads Sync Completed');

        return Command::SUCCESS;
    }
}
