<?php

namespace Database\Seeders;

use App\Models\Core\Org;
use App\Models\Marketplace\MarketplaceApp;
use App\Models\Marketplace\OrganizationApp;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Marketplace App Seeder
 *
 * Seeds organization_apps for existing organizations based on usage detection.
 * Run this seeder after the migration to enable apps for existing orgs.
 *
 * Usage:
 *   php artisan db:seed --class=MarketplaceAppSeeder
 */
class MarketplaceAppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding marketplace apps for existing organizations...');

        // Get all organizations
        $orgs = Org::withoutGlobalScopes()->get();

        if ($orgs->isEmpty()) {
            $this->command->warn('No organizations found. Skipping marketplace seeder.');
            return;
        }

        // Get all optional apps
        $optionalApps = MarketplaceApp::optional()->active()->get();

        if ($optionalApps->isEmpty()) {
            $this->command->warn('No optional apps found. Skipping marketplace seeder.');
            return;
        }

        // Define usage detection queries for each app
        $usageChecks = $this->getUsageChecks();

        // Get a system user ID (first admin or first user)
        $systemUserId = $this->getSystemUserId();

        $stats = [
            'orgs_processed' => 0,
            'apps_enabled' => 0,
        ];

        foreach ($orgs as $org) {
            $this->command->line("Processing organization: {$org->name} ({$org->org_id})");

            // Set RLS context for this org
            DB::statement("SET app.current_org_id = '{$org->org_id}'");

            $orgAppsEnabled = 0;

            foreach ($optionalApps as $app) {
                // Check if already enabled
                $existing = OrganizationApp::withoutGlobalScopes()
                    ->where('org_id', $org->org_id)
                    ->where('app_id', $app->app_id)
                    ->first();

                if ($existing && $existing->is_enabled) {
                    continue; // Already enabled
                }

                // Check usage for this app
                $hasUsage = false;

                if (isset($usageChecks[$app->slug])) {
                    try {
                        $hasUsage = $usageChecks[$app->slug]($org->org_id);
                    } catch (\Exception $e) {
                        // Table might not exist, assume no usage
                        Log::debug("Usage check failed for {$app->slug}: " . $e->getMessage());
                    }
                }

                if ($hasUsage) {
                    // Enable the app and its dependencies
                    $this->enableAppWithDependencies($org->org_id, $app, $systemUserId);
                    $orgAppsEnabled++;
                    $stats['apps_enabled']++;

                    $this->command->info("  + Enabled: {$app->slug}");
                }
            }

            if ($orgAppsEnabled === 0) {
                $this->command->line("  (no apps enabled based on usage)");
            }

            $stats['orgs_processed']++;
        }

        // Reset RLS context
        DB::statement("RESET app.current_org_id");

        $this->command->newLine();
        $this->command->info("Marketplace seeding completed!");
        $this->command->table(
            ['Metric', 'Count'],
            [
                ['Organizations Processed', $stats['orgs_processed']],
                ['Apps Enabled', $stats['apps_enabled']],
            ]
        );
    }

    /**
     * Get usage detection queries for each app.
     */
    protected function getUsageChecks(): array
    {
        return [
            'campaigns' => function ($orgId) {
                return DB::table('cmis.campaigns')
                    ->where('org_id', $orgId)
                    ->whereNull('deleted_at')
                    ->exists();
            },
            'analytics' => function ($orgId) {
                // Check if org has any campaign data (implies analytics usage)
                return DB::table('cmis.campaigns')
                    ->where('org_id', $orgId)
                    ->whereNull('deleted_at')
                    ->exists();
            },
            'audiences' => function ($orgId) {
                return DB::table('cmis_meta.audiences')
                    ->where('org_id', $orgId)
                    ->whereNull('deleted_at')
                    ->exists();
            },
            'creative-assets' => function ($orgId) {
                return DB::table('cmis.creative_assets')
                    ->where('org_id', $orgId)
                    ->whereNull('deleted_at')
                    ->exists();
            },
            'products' => function ($orgId) {
                return DB::table('cmis.products')
                    ->where('org_id', $orgId)
                    ->whereNull('deleted_at')
                    ->exists();
            },
            'workflows' => function ($orgId) {
                return DB::table('cmis.approval_workflows')
                    ->where('org_id', $orgId)
                    ->whereNull('deleted_at')
                    ->exists();
            },
            'alerts' => function ($orgId) {
                return DB::table('cmis.alerts')
                    ->where('org_id', $orgId)
                    ->whereNull('deleted_at')
                    ->exists();
            },
            'ai-assistant' => function ($orgId) {
                // Check if org has any AI embeddings
                return DB::table('cmis_ai.embeddings')
                    ->where('org_id', $orgId)
                    ->exists();
            },
        ];
    }

    /**
     * Enable an app with its dependencies.
     */
    protected function enableAppWithDependencies(string $orgId, MarketplaceApp $app, ?string $userId): void
    {
        // Get all dependencies
        $dependencies = $app->getAllDependencies();

        // Enable dependencies first
        foreach ($dependencies as $depSlug) {
            $depApp = MarketplaceApp::findBySlug($depSlug);
            if ($depApp) {
                $this->enableSingleApp($orgId, $depApp->app_id, $userId);
            }
        }

        // Enable the app itself
        $this->enableSingleApp($orgId, $app->app_id, $userId);
    }

    /**
     * Enable a single app for an organization.
     */
    protected function enableSingleApp(string $orgId, string $appId, ?string $userId): void
    {
        OrganizationApp::withoutGlobalScopes()->updateOrCreate(
            [
                'org_id' => $orgId,
                'app_id' => $appId,
            ],
            [
                'is_enabled' => true,
                'enabled_at' => now(),
                'enabled_by' => $userId,
                'disabled_at' => null,
                'disabled_by' => null,
            ]
        );
    }

    /**
     * Get a system user ID for recording who enabled apps.
     */
    protected function getSystemUserId(): ?string
    {
        // Try to get admin user
        $admin = DB::table('cmis.users')
            ->where('email', 'admin@cmis.test')
            ->first();

        if ($admin) {
            return $admin->user_id;
        }

        // Get first user as fallback
        $firstUser = DB::table('cmis.users')
            ->whereNull('deleted_at')
            ->first();

        return $firstUser?->user_id;
    }
}
