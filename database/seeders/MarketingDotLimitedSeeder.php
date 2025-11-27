<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use App\Models\Core\Org;
use App\Models\Core\Role;
use App\Models\Core\UserOrg;

class MarketingDotLimitedSeeder extends Seeder
{
    /**
     * Organization UUID - deterministic for consistency
     */
    private const ORG_ID = '11111111-2222-3333-4444-555555555555';

    /**
     * Seed the Marketing Dot Limited organization with Meta platform connections.
     */
    public function run(): void
    {
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        $this->command->info('🚀 Seeding Marketing Dot Limited organization...');

        // Step 1: Create Organization
        $org = $this->createOrganization();
        $this->command->info("✓ Organization created: {$org->name}");

        // Step 2: Create Platform Connections
        $this->createPlatformConnections($org->org_id);
        $this->command->info('✓ Platform connections created');

        // Step 3: Create Services and Products
        $this->createOfferings($org->org_id);
        $this->command->info('✓ Services and products created');

        $this->command->info('🎉 Marketing Dot Limited seeded successfully!');
        $this->command->info("   Organization ID: {$org->org_id}");
        $this->command->info("   View at: /orgs/{$org->org_id}");
        $this->command->info("   Platform connections: /orgs/{$org->org_id}/settings/platform-connections");
    }

    /**
     * Create the Marketing Dot Limited organization
     */
    private function createOrganization(): Org
    {
        $orgData = [
            'org_id' => self::ORG_ID,
            'name' => 'Marketing Dot Limited',
            'default_locale' => 'ar-BH',
            'currency' => 'BHD',
            'provider' => 'Marketing Dot Limited', // Store contact info in provider field for now
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Upsert organization
        DB::table('cmis.orgs')->upsert(
            [$orgData],
            ['org_id'],
            ['name', 'default_locale', 'currency', 'provider', 'updated_at']
        );

        // Store contact info in account_metadata of platform connection
        return Org::find(self::ORG_ID);
    }

    /**
     * Create Meta platform connections with all Facebook Pages and Instagram accounts
     */
    private function createPlatformConnections(string $orgId): void
    {
        // Set RLS context
        DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

        $accessToken = 'EAAasZCsTCe14BQOALZBSi5RBQo5Ng4oXRswyRFutpBNu2RisoRzUI1fJG8sRIX4dtba4ZCZBtDiSqVMUAabBBscTfV8ZC0F5I7poUHsUn7Y1SP4m5V8xvzZBbjTOZBShH1bO2KMstES2FEv5b4xOEk4C07mpl15SsmvWSzxQVEgMeBWTcyEZAq9juVSWkDEqgPIJSQZDZD';

        // Facebook Pages
        $facebookPages = [
            ['id' => '108501945500897', 'name' => 'Marketing Dot Limited - Main Page'],
            ['id' => '717997244966796', 'name' => 'Marketing Dot Limited - Page 2'],
            ['id' => '109143377540388', 'name' => 'Marketing Dot Limited - Page 3'],
        ];

        // Instagram Accounts
        $instagramAccounts = [
            ['id' => '17841403756121424', 'name' => 'Marketing Dot Limited Instagram'],
            ['id' => '17841458394687915', 'name' => 'Marketing Dot Limited Instagram 2'],
            ['id' => '17841403756121424', 'name' => 'Marketing Dot Limited Instagram 3'],
        ];

        // Meta Business & Ad Account Info
        $metaBusinessId = '486991302969759';
        $metaAdAccountId = '3048183365459787';

        // Create main Meta connection with all assets
        $connection = [
            'connection_id' => $this->generateUuid('meta-main-connection'),
            'org_id' => $orgId,
            'platform' => 'meta',
            'account_id' => $metaAdAccountId,
            'account_name' => 'Marketing Dot Limited - Meta Business',
            'status' => 'active',
            'access_token' => Crypt::encryptString($accessToken),
            'refresh_token' => null,
            'token_expires_at' => now()->addDays(60),
            'scopes' => json_encode([
                'ads_management',
                'ads_read',
                'business_management',
                'catalog_management',
                'instagram_basic',
                'instagram_content_publish',
                'instagram_manage_comments',
                'instagram_manage_insights',
                'pages_manage_posts',
                'pages_read_engagement',
                'pages_manage_metadata',
                'pages_read_user_content',
                'public_profile',
            ]),
            'account_metadata' => json_encode([
                'business_id' => $metaBusinessId,
                'ad_account_id' => $metaAdAccountId,
                'ad_account_name' => 'Marketing Dot Limited Ad Account',
                'business_name' => 'Marketing Dot Limited',
                'currency' => 'BHD',
                'timezone' => 'Asia/Bahrain',
                // All Facebook Pages
                'facebook_pages' => array_map(fn($page) => [
                    'id' => $page['id'],
                    'name' => $page['name'],
                ], $facebookPages),
                // All Instagram Accounts
                'instagram_accounts' => array_map(fn($account) => [
                    'id' => $account['id'],
                    'name' => $account['name'],
                ], $instagramAccounts),
                // Selected assets for publishing
                'selected_assets' => [
                    'pages' => array_column($facebookPages, 'id'),
                    'instagram_accounts' => array_column($instagramAccounts, 'id'),
                    'ad_accounts' => [$metaAdAccountId],
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
        ];

        // Insert platform connection
        DB::table('cmis.platform_connections')->upsert(
            [$connection],
            ['connection_id'],
            array_keys($connection)
        );

        // Create linked integration record
        $integrationId = $this->createLinkedIntegration($connection);

        $this->command->info("  → Meta connection: {$connection['account_name']}");
        $this->command->info("  → Facebook Pages: " . count($facebookPages));
        $this->command->info("  → Instagram Accounts: " . count($instagramAccounts));
        $this->command->info("  → Integration ID: {$integrationId}");
    }

    /**
     * Create a linked integration record for FK compatibility
     */
    private function createLinkedIntegration(array $connection): string
    {
        $integrationId = $this->generateUuid($connection['connection_id'] . '-integration');
        $metadata = json_decode($connection['account_metadata'], true);
        $selectedAssets = $metadata['selected_assets'] ?? [];

        $integrationData = [
            'integration_id' => $integrationId,
            'org_id' => $connection['org_id'],
            'platform' => 'meta',
            'account_id' => $connection['account_id'],
            'access_token' => $connection['access_token'],
            'is_active' => true,
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

        DB::table('cmis.integrations')->upsert(
            [$integrationData],
            ['integration_id'],
            ['platform', 'account_id', 'access_token', 'is_active', 'business_id',
             'username', 'platform_connection_id', 'selected_assets', 'token_expires_at',
             'last_sync_at', 'account_metadata', 'updated_at']
        );

        return $integrationId;
    }

    /**
     * Create services and products offerings for Marketing Dot Limited
     */
    private function createOfferings(string $orgId): void
    {
        $offerings = [
            // Marketing Services
            [
                'offering_id' => $this->generateUuid('service-social-media'),
                'org_id' => $orgId,
                'kind' => 'service',
                'name' => 'Social Media Management',
                'description' => 'Comprehensive social media management across all major platforms including content creation, scheduling, and engagement tracking',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'offering_id' => $this->generateUuid('service-paid-ads'),
                'org_id' => $orgId,
                'kind' => 'service',
                'name' => 'Paid Advertising Campaigns',
                'description' => 'Strategic paid advertising campaigns on Meta, Google, TikTok, LinkedIn, Twitter, and Snapchat with ROI optimization',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'offering_id' => $this->generateUuid('service-content-creation'),
                'org_id' => $orgId,
                'kind' => 'service',
                'name' => 'Content Creation & Design',
                'description' => 'Professional content creation including graphics, videos, copywriting, and brand storytelling in Arabic and English',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'offering_id' => $this->generateUuid('service-brand-strategy'),
                'org_id' => $orgId,
                'kind' => 'service',
                'name' => 'Brand Strategy & Consulting',
                'description' => 'Strategic brand positioning, market analysis, and comprehensive marketing roadmap development',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'offering_id' => $this->generateUuid('service-influencer'),
                'org_id' => $orgId,
                'kind' => 'service',
                'name' => 'Influencer Marketing',
                'description' => 'Influencer identification, outreach, campaign management, and performance tracking',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
            // Marketing Products/Packages
            [
                'offering_id' => $this->generateUuid('product-starter-package'),
                'org_id' => $orgId,
                'kind' => 'product',
                'name' => 'Startup Marketing Package',
                'description' => 'Essential marketing package for startups including social media setup, basic content calendar, and monthly reporting',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'offering_id' => $this->generateUuid('product-growth-package'),
                'org_id' => $orgId,
                'kind' => 'product',
                'name' => 'Business Growth Package',
                'description' => 'Comprehensive growth package with multi-platform advertising, content creation, and advanced analytics',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'offering_id' => $this->generateUuid('product-enterprise-package'),
                'org_id' => $orgId,
                'kind' => 'product',
                'name' => 'Enterprise Marketing Suite',
                'description' => 'Full-service enterprise marketing solution with dedicated account management, custom reporting, and priority support',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'offering_id' => $this->generateUuid('product-campaign-package'),
                'org_id' => $orgId,
                'kind' => 'product',
                'name' => 'Seasonal Campaign Package',
                'description' => 'Limited-time seasonal campaign package for holidays, events, and special promotions',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
            [
                'offering_id' => $this->generateUuid('product-rebranding-package'),
                'org_id' => $orgId,
                'kind' => 'product',
                'name' => 'Complete Rebranding Package',
                'description' => 'Full brand refresh including logo design, brand guidelines, website redesign, and launch campaign',
                'created_at' => now(),
                'deleted_at' => null,
                'provider' => null,
            ],
        ];

        foreach ($offerings as $offering) {
            // Check if offering already exists
            $exists = DB::table('cmis.offerings_old')
                ->where('offering_id', $offering['offering_id'])
                ->exists();

            if (!$exists) {
                DB::table('cmis.offerings_old')->insert($offering);
            }
        }

        $this->command->info("  → Services: 5");
        $this->command->info("  → Products: 5");
    }

    /**
     * Generate a deterministic UUID
     */
    private function generateUuid(string $input): string
    {
        $hash = md5(self::ORG_ID . '-' . $input);

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
