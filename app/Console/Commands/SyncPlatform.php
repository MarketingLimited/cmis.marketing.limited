<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Connectors\ConnectorFactory;
use App\Models\Core\Integration;
use Illuminate\Support\Facades\Log;

class SyncPlatform extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:platform {platform : The platform to sync (e.g., meta, google)}
                            {--org=* : Specify one or more organization IDs to sync}
                            {--type=all : The type of data to sync (e.g., all, campaigns, posts)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs data from a specified external platform for one or all organizations.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $platform = $this->argument('platform');
        $orgIds = $this->option('org');
        $syncType = $this->option('type');

        $this->info("🚀 Starting sync for platform: [{$platform}]");

        try {
            $connector = ConnectorFactory::make($platform);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 1;
        }

        $integrationsQuery = Integration::where('platform', $platform)->where('is_active', true);

        if (!empty($orgIds)) {
            $integrationsQuery->whereIn('org_id', $orgIds);
            $this->info("Targeting specific organizations: " . implode(', ', $orgIds));
        }

        $integrations = $integrationsQuery->get();

        if ($integrations->isEmpty()) {
            $this->warn("No active integrations found for platform [{$platform}].");
            return 0;
        }

        $this->info("Found {$integrations->count()} active integration(s) to process.");

        foreach ($integrations as $integration) {
            $this->line("Processing integration for Org ID: {$integration->org_id}");

            try {
                // Here you would use a more sophisticated context handler like the
                // HandlesOrgContext trait planned in the main backend implementation
                // For now, this is a simplified direct call.

                if ($syncType === 'all' || $syncType === 'campaigns') {
                    $this->info("   -> Syncing campaigns...");
                    $campaigns = $connector->syncCampaigns($integration);
                    $this->info("   ✅ Synced {$campaigns->count()} campaigns.");
                }

                if ($syncType === 'all' || $syncType === 'posts') {
                    $this->info("   -> Syncing posts...");
                    $posts = $connector->syncPosts($integration);
                    $this->info("   ✅ Synced {$posts->count()} posts.");
                }

            } catch (\Exception $e) {
                $this->error("   ❌ Failed to process integration for Org ID {$integration->org_id}: {$e->getMessage()}");
                Log::error("Sync failed for platform {$platform}, org {$integration->org_id}", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("🏁 Sync finished for platform: [{$platform}]");
        return 0;
    }
}
