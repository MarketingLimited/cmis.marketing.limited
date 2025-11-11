<?php

namespace App\Console\Commands\Sync;

use Illuminate\Console\Command;
use App\Console\Traits\HandlesOrgContext;
use App\Models\Integration;
use App\Jobs\SyncMetaAdsJob;
use Carbon\Carbon;

class SyncMetaAdsCommand extends Command
{
    use HandlesOrgContext;

    protected $signature = 'sync:meta-ads
                            {--org=* : Specific org IDs}
                            {--from= : Start date (YYYY-MM-DD)}
                            {--to= : End date (YYYY-MM-DD)}
                            {--limit=50 : Campaigns limit}
                            {--queue : Dispatch as queue job}';

    protected $description = 'Sync Meta Ads data (Facebook & Instagram ads)';

    public function handle()
    {
        $this->info('🚀 Starting Meta Ads Sync');
        $this->newLine();

        $orgIds = $this->option('org') ?: null;
        $from = $this->option('from') ? Carbon::parse($this->option('from')) : Carbon::now()->subDays(30);
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : Carbon::now();
        $limit = $this->option('limit');

        $this->executePerOrg(function ($org) use ($from, $to, $limit) {
            $integrations = Integration::where('org_id', $org->org_id)
                ->where('platform', 'meta_ads')
                ->where('status', 'active')
                ->get();

            if ($integrations->isEmpty()) {
                $this->warn("  ⚠️  No active Meta Ads integrations");
                return;
            }

            foreach ($integrations as $integration) {
                $adAccountId = $integration->metadata['ad_account_id'] ?? 'N/A';
                $this->info("  💼 Ad Account: {$adAccountId}");

                try {
                    $this->line("     → Syncing campaigns from {$from->toDateString()} to {$to->toDateString()}");
                    $this->line("     → Limit: {$limit} campaigns");

                    if ($this->option('queue')) {
                        // Dispatch job to queue
                        SyncMetaAdsJob::dispatch($integration, $from, $to, $limit);
                        $this->info("     ✓ Job dispatched to queue");
                    } else {
                        // Run synchronously
                        $job = new SyncMetaAdsJob($integration, $from, $to, $limit);
                        $job->handle();
                        $this->info("     ✓ Sync completed");
                    }

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
