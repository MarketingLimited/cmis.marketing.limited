<?php

namespace App\Console\Commands\Sync;

use Illuminate\Console\Command;
use App\Console\Traits\HandlesOrgContext;
use App\Models\Integration;
use Carbon\Carbon;

class SyncInstagramCommand extends Command
{
    use HandlesOrgContext;

    protected $signature = 'sync:instagram
                            {--org=* : Specific org IDs}
                            {--from= : Start date (YYYY-MM-DD)}
                            {--to= : End date (YYYY-MM-DD)}
                            {--limit=25 : Posts limit}';

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
                    // Placeholder for actual sync logic
                    $this->line("     → Syncing posts from {$from->toDateString()} to {$to->toDateString()}");
                    $this->line("     → Limit: {$limit} posts");

                    // TODO: Implement actual Instagram API sync
                    // $service->syncAccount($integration);
                    // $service->syncPosts($integration, $from, $to, $limit);

                    $this->info("     ✓ Sync completed (placeholder)");

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
