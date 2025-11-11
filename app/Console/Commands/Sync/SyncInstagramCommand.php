<?php

namespace App\Console\Commands\Sync;

use Illuminate\Console\Command;
use App\Console\Traits\HandlesOrgContext;
use App\Models\Integration;
use App\Jobs\SyncInstagramDataJob;
use Carbon\Carbon;

class SyncInstagramCommand extends Command
{
    use HandlesOrgContext;

    protected $signature = 'sync:instagram
                            {--org=* : Specific org IDs}
                            {--from= : Start date (YYYY-MM-DD)}
                            {--to= : End date (YYYY-MM-DD)}
                            {--limit=25 : Posts limit}
                            {--queue : Dispatch as queue job}';

    protected $description = 'Sync Instagram data (accounts, posts, metrics)';

    public function handle()
    {
        $this->info('🚀 Starting Instagram Sync');
        $this->newLine();

        $orgIds = $this->option('org') ?: null;
        $from = $this->option('from') ? Carbon::parse($this->option('from')) : Carbon::now()->subDays(30);
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : Carbon::now();
        $limit = $this->option('limit');

        $this->executePerOrg(function ($org) use ($from, $to, $limit) {
            $integrations = Integration::where('org_id', $org->org_id)
                ->where('platform', 'instagram')
                ->where('status', 'active')
                ->get();

            if ($integrations->isEmpty()) {
                $this->warn("  ⚠️  No active Instagram integrations");
                return;
            }

            foreach ($integrations as $integration) {
                $this->info("  📱 Account: {$integration->account_username}");

                try {
                    $this->line("     → Syncing posts from {$from->toDateString()} to {$to->toDateString()}");
                    $this->line("     → Limit: {$limit} posts");

                    if ($this->option('queue')) {
                        // Dispatch job to queue
                        SyncInstagramDataJob::dispatch($integration, $from, $to, $limit);
                        $this->info("     ✓ Job dispatched to queue");
                    } else {
                        // Run synchronously
                        $job = new SyncInstagramDataJob($integration, $from, $to, $limit);
                        $job->handle(app(\App\Services\Social\InstagramSyncService::class));
                        $this->info("     ✓ Sync completed");
                    }

                } catch (\Exception $e) {
                    $this->error("     ✗ Error: " . $e->getMessage());
                }
            }
        }, $orgIds);

        $this->newLine();
        $this->info('✅ Instagram Sync Completed');

        return Command::SUCCESS;
    }
}
