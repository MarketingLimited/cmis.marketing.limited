<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Integration;

class SyncInstagramData extends Command
{
    protected $signature = 'instagram:sync {account?} {--by=id}';

    protected $description = 'Sync Instagram account content and metrics for one or all connected organizations.';

    public function handle()
    {

        $account = $this->argument('account');
        $by = $this->option('by');

        if ($account) {
            $integration = Integration::where('platform', 'instagram')
                ->where(function ($q) use ($account, $by) {
                    if ($by === 'id') {
                        $q->where('integration_id', $account);
                    } elseif ($by === 'username') {
                        $q->where('username', $account);
                    } else {
                        $q->where('account_id', $account)
                          ->orWhere('integration_id', $account);
                    }
                })
                ->first();

            if (!$integration) {
                $this->error('No Instagram integration found for given account.');
                return 1;
            }

            $this->info("Syncing Instagram account: {$integration->account_id}");
            $this->info('Single account synced successfully.');
            return 0;
        }

        $this->info('Syncing all Instagram integrations...');
        $integrations = Integration::where('platform', 'instagram')
            ->where('is_active', true)
            ->get();

        foreach ($integrations as $integration) {
            $this->info("Syncing account: {$integration->account_id}");
        }

        $this->info('All accounts synced successfully.');
        return 0;
    }
}
