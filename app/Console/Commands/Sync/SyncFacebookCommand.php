<?php

namespace App\Console\Commands\Sync;

use Illuminate\Console\Command;
use App\Console\Traits\HandlesOrgContext;
use App\Models\Integration;

class SyncFacebookCommand extends Command
{
    use HandlesOrgContext;

    protected $signature = 'sync:facebook
                            {--org=* : Specific org IDs}';

    protected $description = 'Sync Facebook data';

    public function handle()
    {
        $this->info('🚀 Starting Facebook Sync');
        $this->newLine();

        $orgIds = $this->option('org') ?: null;

        $this->executePerOrg(function ($org) {
            $integrations = Integration::where('org_id', $org->org_id)
                ->where('platform', 'facebook')
                ->where('status', 'active')
                ->get();

            if ($integrations->isEmpty()) {
                $this->warn("  ⚠️  No active Facebook integrations");
                return;
            }

            foreach ($integrations as $integration) {
                $this->info("  📘 Account: {$integration->account_username}");

                try {
                    // TODO: Implement Facebook sync
                    $this->info("     ✓ Sync completed (placeholder)");

                } catch (\Exception $e) {
                    $this->error("     ✗ Error: " . $e->getMessage());
                }
            }
        }, $orgIds);

        $this->newLine();
        $this->info('✅ Facebook Sync Completed');

        return Command::SUCCESS;
    }
}
