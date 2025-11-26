<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

class PlatformConnectionsSeeder extends Seeder
{
    /**
     * Seed platform connections with real Meta/Facebook credentials.
     *
     * This seeder adds Meta platform connection for the CMIS organization
     * with production credentials for testing and development.
     */
    public function run(): void
    {
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        // Set RLS context for CMIS organization
        DB::statement("SELECT set_config('app.current_org_id', ?, false)", [SeederConstants::ORG_CMIS]);

        $connections = [
            [
                'connection_id' => '4a5b6c7d-8e9f-0a1b-2c3d-4e5f6a7b8c9d',
                'org_id' => SeederConstants::ORG_CMIS,
                'platform' => 'meta',
                'account_id' => '825640513529658',
                'account_name' => 'CMIS Meta Ad Account',
                'status' => 'active',
                'access_token' => Crypt::encryptString('EAAasZCsTCe14BQOALZBSi5RBQo5Ng4oXRswyRFutpBNu2RisoRzUI1fJG8sRIX4dtba4ZCZBtDiSqVMUAabBBscTfV8ZC0F5I7poUHsUn7Y1SP4m5V8xvzZBbjTOZBShH1bO2KMstES2FEv5b4xOEk4C07mpl15SsmvWSzxQVEgMeBWTcyEZAq9juVSWkDEqgPIJSQZDZD'),
                'refresh_token' => null,
                'token_expires_at' => now()->addDays(60), // Meta tokens typically last 60 days
                'scopes' => json_encode([
                    'ads_management',
                    'ads_read',
                    'business_management',
                    'catalog_management',
                    'instagram_basic',
                    'instagram_content_publish',
                    'pages_manage_posts',
                    'pages_read_engagement',
                ]),
                'account_metadata' => json_encode([
                    'facebook_page_id' => '106516668655238',
                    'instagram_account_id' => '17841478885244751',
                    'dataset_pixel_id' => '1727868431240536',
                    'catalog_id' => '1176379064460631',
                    'business_id' => '486991302969759',
                    'ad_account_id' => '825640513529658',
                    'ad_account_name' => 'CMIS Meta Ad Account',
                    'business_name' => 'CMIS',
                    'currency' => 'USD',
                    'timezone' => 'America/Los_Angeles',
                    // Selected assets for publishing
                    'selected_assets' => [
                        'pages' => ['106516668655238'],
                        'instagram_accounts' => ['17841478885244751'],
                        'ad_accounts' => ['825640513529658'],
                        'pixels' => ['1727868431240536'],
                        'catalogs' => ['1176379064460631'],
                    ],
                    'assets_updated_at' => now()->toIso8601String(),
                ]),
                'last_sync_at' => now(),
                'last_error_at' => null,
                'last_error_message' => null,
                'auto_sync' => true,
                'sync_frequency_minutes' => 15,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ];

        foreach ($connections as $connection) {
            // Insert or update platform_connections
            DB::table('cmis.platform_connections')->upsert(
                [$connection],
                ['connection_id'],
                array_keys($connection)
            );

            // Also create/update linked integration record for FK compatibility
            $integrationId = $this->createLinkedIntegration($connection);

            $this->command->info("✓ Platform connection created: {$connection['account_name']}");
            $this->command->info("  → Linked integration_id: {$integrationId}");
        }

        $this->command->info('Platform connections seeded successfully!');
        $this->command->info('✓ CMIS organization connected to Meta with production credentials');
    }

    /**
     * Create a linked integration record for FK compatibility.
     * This ensures social_posts and other tables can reference the integration.
     */
    private function createLinkedIntegration(array $connection): string
    {
        // Generate a deterministic UUID based on connection_id for consistency
        // This ensures the same integration_id is generated for the same connection
        $integrationId = $this->generateDeterministicUuid($connection['connection_id'], 'integration');

        // Map platform name for integrations table
        $platform = match($connection['platform']) {
            'meta' => 'meta', // Keep as 'meta' for consistency
            default => $connection['platform'],
        };

        $metadata = json_decode($connection['account_metadata'], true);
        $selectedAssets = $metadata['selected_assets'] ?? [];

        $integrationData = [
            'integration_id' => $integrationId,
            'org_id' => $connection['org_id'],
            'platform' => $platform,
            'account_id' => $connection['account_id'],
            'access_token' => $connection['access_token'],
            'is_active' => $connection['status'] === 'active',
            'business_id' => $metadata['business_id'] ?? null,
            'username' => $metadata['ad_account_name'] ?? $connection['account_name'],
            'platform_connection_id' => $connection['connection_id'],
            'selected_assets' => json_encode($selectedAssets),
            'token_expires_at' => $connection['token_expires_at'],
            'last_sync_at' => $connection['last_sync_at'],
            'account_metadata' => $connection['account_metadata'],
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Upsert to handle both insert and update
        DB::table('cmis.integrations')->upsert(
            [$integrationData],
            ['integration_id'],
            ['platform', 'account_id', 'access_token', 'is_active', 'business_id', 'username',
             'platform_connection_id', 'selected_assets', 'token_expires_at', 'last_sync_at',
             'account_metadata', 'updated_at']
        );

        return $integrationId;
    }

    /**
     * Generate a deterministic UUID based on input strings.
     * This ensures consistent UUIDs across seeder runs.
     */
    private function generateDeterministicUuid(string $baseId, string $suffix): string
    {
        // Create a hash from the input and convert to UUID format
        $hash = md5($baseId . '-' . $suffix);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12)
        );
    }
}
