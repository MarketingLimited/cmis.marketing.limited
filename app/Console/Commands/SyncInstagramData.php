<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Integration;
use App\Services\Social\InstagramSyncService;
use Illuminate\Support\Str;

class SyncInstagramData extends Command
{
    protected $signature = 'instagram:sync {account?} {--by=id}';

    protected $description = 'Sync Instagram account content and metrics for one or all connected organizations.';

    public function handle()
    {
        $account = $this->argument('account');
        $by = $this->option('by');

        $service = new InstagramSyncService();

        if ($account) {
            $integration = Integration::where('platform', 'instagram')
                ->where(function ($q) use ($account) {
                    if (Str::isUuid($account)) {
                        $q->where('integration_id', $account);
                    } else {
                        $q->where('username', $account)
                          ->orWhere('account_id', $account);
                    }
                })
                ->first();

            if (!$integration) {
                $this->error('No Instagram integration found for the given account.');
                return 1;
            }

            $this->info("Syncing Instagram account: {$integration->account_id}");
            $service->syncIntegrationByAccountId($integration);
            $this->info('Single account synced successfully.');
            return 0;
        }

        $this->info('Syncing all Instagram integrations...');

        $service->syncAllActive();

        $this->info('All active Instagram accounts synced successfully.');
        return 0;
    }
}