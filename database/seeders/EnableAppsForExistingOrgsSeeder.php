<?php

namespace Database\Seeders;

use App\Models\Core\Org;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * Enable marketplace apps for existing organizations based on their data usage.
 *
 * This seeder detects which features each organization is already using
 * and enables the corresponding marketplace apps.
 */
class EnableAppsForExistingOrgsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marketplace = app(MarketplaceService::class);

        // Get all organizations
        $orgs = Org::withoutGlobalScopes()->get();

        $this->command->info("Processing {$orgs->count()} organizations...");

        $progressBar = $this->command->getOutput()->createProgressBar($orgs->count());
        $progressBar->start();

        $processed = 0;
        $enabled = 0;

        foreach ($orgs as $org) {
            try {
                // Initialize marketplace for this org with usage detection
                $marketplace->initializeForOrg(
                    orgId: $org->org_id,
                    userId: $org->owner_id ?? 'system',
                    detectUsage: true
                );

                $processed++;

                // Count enabled apps for this org
                $enabledCount = $marketplace->getEnabledAppSlugs($org->org_id);
                if (count($enabledCount) > 0) {
                    $enabled++;
                }

                Log::info("Marketplace: Initialized apps for org {$org->org_id}", [
                    'org_name' => $org->name,
                    'enabled_apps' => $enabledCount,
                ]);
            } catch (\Exception $e) {
                Log::error("Marketplace: Failed to initialize apps for org {$org->org_id}", [
                    'error' => $e->getMessage(),
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->newLine();

        $this->command->info("Processed: {$processed} organizations");
        $this->command->info("Organizations with apps enabled: {$enabled}");
    }
}
