<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Core\Org;
use App\Models\Core\Role;
use App\Models\Core\UserOrg;
use App\Models\Campaign;
use App\Models\Content\ContentPlan;
use App\Models\Creative\ContentItem;
use App\Models\Core\Integration;
use App\Models\SocialAccount;
use App\Models\Social\SocialPost;
use App\Models\Social\ScheduledSocialPost;
use App\Models\AdPlatform\AdCampaign;
use App\Models\AdPlatform\AdAccount;
use App\Models\CreativeAsset;

/**
 * Comprehensive test data seeder for CMIS application.
 * Creates multi-org test environments with complete data ecosystems.
 */
class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Disable foreign key checks for seeding
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        // Create two test organizations
        $org1 = $this->createOrganization('Alpha Marketing Agency', 'ar-BH', 'BHD');
        $org2 = $this->createOrganization('Beta Digital Solutions', 'en-US', 'USD');

        // Create users for each organization
        $org1Data = $this->createOrganizationUsers($org1);
        $org2Data = $this->createOrganizationUsers($org2);

        // Create complete ecosystem for org1
        $this->createOrganizationEcosystem($org1, $org1Data['admin'], $org1Data['manager'], $org1Data['creator']);

        // Create complete ecosystem for org2
        $this->createOrganizationEcosystem($org2, $org2Data['admin'], $org2Data['manager'], $org2Data['creator']);

        $this->command->info('Test data seeded successfully for 2 organizations!');
    }

    /**
     * Create a test organization.
     *
     * @param string $name
     * @param string $locale
     * @param string $currency
     * @return Org
     */
    private function createOrganization(string $name, string $locale, string $currency): Org
    {
        return Org::factory()->create([
            'name' => $name,
            'default_locale' => $locale,
            'currency' => $currency,
        ]);
    }

    /**
     * Create users for an organization with different roles.
     *
     * @param Org $org
     * @return array
     */
    private function createOrganizationUsers(Org $org): array
    {
        // Create roles
        $adminRole = Role::factory()->create([
            'org_id' => $org->org_id,
            'role_code' => 'admin',
            'role_name' => 'Administrator',
            'is_system' => true,
        ]);

        $managerRole = Role::factory()->create([
            'org_id' => $org->org_id,
            'role_code' => 'manager',
            'role_name' => 'Campaign Manager',
            'is_system' => true,
        ]);

        $creatorRole = Role::factory()->create([
            'org_id' => $org->org_id,
            'role_code' => 'creator',
            'role_name' => 'Content Creator',
            'is_system' => false,
        ]);

        // Create users
        $admin = $this->createUserForOrg($org, $adminRole, 'Admin');
        $manager = $this->createUserForOrg($org, $managerRole, 'Manager');
        $creator = $this->createUserForOrg($org, $creatorRole, 'Creator');

        return [
            'admin' => $admin,
            'manager' => $manager,
            'creator' => $creator,
            'roles' => [
                'admin' => $adminRole,
                'manager' => $managerRole,
                'creator' => $creatorRole,
            ],
        ];
    }

    /**
     * Create a user and associate with organization and role.
     *
     * @param Org $org
     * @param Role $role
     * @param string $namePrefix
     * @return User
     */
    private function createUserForOrg(Org $org, Role $role, string $namePrefix): User
    {
        $user = User::factory()->create([
            'name' => $namePrefix . ' ' . $org->name,
            'email' => strtolower($namePrefix) . '@' . str_replace(' ', '', strtolower($org->name)) . '.test',
        ]);

        UserOrg::factory()->create([
            'user_id' => $user->user_id,
            'org_id' => $org->org_id,
            'role_id' => $role->role_id,
            'is_active' => true,
        ]);

        return $user;
    }

    /**
     * Create complete data ecosystem for an organization.
     *
     * @param Org $org
     * @param User $admin
     * @param User $manager
     * @param User $creator
     * @return void
     */
    private function createOrganizationEcosystem(Org $org, User $admin, User $manager, User $creator): void
    {
        // Set transaction context for RLS
        DB::statement('SELECT cmis.init_transaction_context(?, ?)', [$admin->user_id, $org->org_id]);

        // Create platform integrations
        $metaIntegration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'meta',
            'is_active' => true,
        ]);

        $googleIntegration = Integration::factory()->create([
            'org_id' => $org->org_id,
            'platform' => 'google',
            'is_active' => true,
        ]);

        // Create social accounts
        $facebookAccount = SocialAccount::factory()->create([
            'org_id' => $org->org_id,
            'integration_id' => $metaIntegration->integration_id,
        ]);

        $instagramAccount = SocialAccount::factory()->create([
            'org_id' => $org->org_id,
            'integration_id' => $metaIntegration->integration_id,
        ]);

        // Create ad accounts
        $metaAdAccount = AdAccount::factory()->create([
            'org_id' => $org->org_id,
            'integration_id' => $metaIntegration->integration_id,
        ]);

        // Create campaigns with different statuses
        $activeCampaign = Campaign::factory()->active()->create([
            'org_id' => $org->org_id,
            'name' => 'Summer Sale Campaign - ' . $org->name,
        ]);

        $draftCampaign = Campaign::factory()->draft()->create([
            'org_id' => $org->org_id,
            'name' => 'Upcoming Product Launch - ' . $org->name,
        ]);

        $completedCampaign = Campaign::factory()->completed()->create([
            'org_id' => $org->org_id,
            'name' => 'Q1 Brand Awareness - ' . $org->name,
        ]);

        // Create content plans
        $contentPlan = ContentPlan::factory()->create([
            'org_id' => $org->org_id,
            'campaign_id' => $activeCampaign->campaign_id,
            'name' => 'Social Media Content Plan',
        ]);

        // Create content items
        ContentItem::factory()->count(5)->create([
            'org_id' => $org->org_id,
            'plan_id' => $contentPlan->plan_id,
        ]);

        // Create creative assets
        CreativeAsset::factory()->count(10)->create([
            'org_id' => $org->org_id,
            'campaign_id' => $activeCampaign->campaign_id,
        ]);

        // Create social posts
        SocialPost::factory()->count(15)->create([
            'org_id' => $org->org_id,
            'integration_id' => $metaIntegration->integration_id,
        ]);

        // Create scheduled posts
        ScheduledSocialPost::factory()->count(8)->scheduled()->create([
            'org_id' => $org->org_id,
        ]);

        // Create ad campaigns
        AdCampaign::factory()->count(5)->create([
            'org_id' => $org->org_id,
            'integration_id' => $metaIntegration->integration_id,
        ]);

        // Clear transaction context
        DB::statement('SELECT cmis.clear_transaction_context()');

        $this->command->info("Created ecosystem for {$org->name}:");
        $this->command->info("  - 3 Campaigns (1 active, 1 draft, 1 completed)");
        $this->command->info("  - 1 Content Plan with 5 Content Items");
        $this->command->info("  - 10 Creative Assets");
        $this->command->info("  - 15 Social Posts");
        $this->command->info("  - 8 Scheduled Posts");
        $this->command->info("  - 5 Ad Campaigns");
        $this->command->info("  - 2 Platform Integrations (Meta, Google)");
        $this->command->info("  - 2 Social Accounts");
    }
}
