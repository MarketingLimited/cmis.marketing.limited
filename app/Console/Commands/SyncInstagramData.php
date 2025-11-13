<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Integration;
use App\Services\Social\InstagramSyncService;
use Illuminate\Support\Facades\Log;

class SyncInstagramData extends Command
{
    protected $signature = 'instagram:sync {account?} {--by=id} {--debug}';

    protected $description = 'Sync Instagram account posts and metrics.';

    public function handle(): int
    {
        $service = new InstagramSyncService();

        $account = $this->argument('account');
        $by = $this->option('by');

        if ($account) {
            $column = $by === 'username' ? 'username' : 'account_id';

            $integration = Integration::query()
                ->where('platform', 'instagram')
                ->where($column, $account)
                ->first();

            if (!$integration) {
                $this->error("No Instagram integration found for {$by}: {$account}");
                return 1;
            }

            $service->syncIntegrationByAccountId($integration);
        } else {
            $total = $service->syncAllActive();
            $this->info("Synced {$total} active Instagram integrations.");
        }

        return 0;
    }
}