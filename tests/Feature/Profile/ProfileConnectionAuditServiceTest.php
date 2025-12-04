<?php

namespace Tests\Feature\Profile;

use App\Models\Core\Integration;
use App\Models\Core\Org;
use App\Models\Core\User;
use App\Models\Platform\BoostRule;
use App\Models\Platform\PlatformConnection;
use App\Models\Social\IntegrationQueueSettings;
use App\Models\Social\ProfileGroup;
use App\Services\Profile\ProfileConnectionAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfileConnectionAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProfileConnectionAuditService $service;
    protected Org $org;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ProfileConnectionAuditService::class);

        // Create test organization and user
        $this->org = Org::factory()->create();
        $this->user = User::factory()->create(['org_id' => $this->org->org_id]);
    }

    /**
     * Helper to create a platform connection
     */
    protected function createConnection(string $platform, array $selectedAssets = [], string $status = 'active'): PlatformConnection
    {
        return PlatformConnection::create([
            'connection_id' => (string) Str::uuid(),
            'org_id' => $this->org->org_id,
            'platform' => $platform,
            'account_id' => (string) fake()->numberBetween(100000000, 999999999),
            'account_name' => fake()->company(),
            'status' => $status,
            'access_token' => Str::random(64),
            'account_metadata' => ['selected_assets' => $selectedAssets],
        ]);
    }

    /**
     * Helper to create an integration (profile)
     */
    protected function createIntegration(string $platform, string $accountId, ?string $connectionId = null): Integration
    {
        return Integration::create([
            'integration_id' => (string) Str::uuid(),
            'org_id' => $this->org->org_id,
            'platform' => $platform,
            'account_id' => $accountId,
            'account_name' => fake()->company(),
            'username' => fake()->userName(),
            'access_token' => Str::random(64),
            'is_active' => true,
            'status' => 'active',
            'metadata' => $connectionId ? ['connection_id' => $connectionId] : [],
        ]);
    }

    /**
     * Helper to create queue settings for an integration
     */
    protected function createQueueSettings(Integration $integration): IntegrationQueueSettings
    {
        return IntegrationQueueSettings::create([
            'org_id' => $this->org->org_id,
            'integration_id' => $integration->integration_id,
            'queue_enabled' => true,
            'posting_times' => ['09:00', '13:00', '18:00'],
            'days_enabled' => [1, 2, 3, 4, 5],
            'posts_per_day' => 3,
        ]);
    }

    /**
     * Helper to create a boost rule that includes an integration
     */
    protected function createBoostRuleWithProfile(Integration $integration): BoostRule
    {
        $profileGroup = ProfileGroup::create([
            'group_id' => (string) Str::uuid(),
            'org_id' => $this->org->org_id,
            'name' => fake()->company() . ' Group',
        ]);

        return BoostRule::create([
            'boost_rule_id' => (string) Str::uuid(),
            'org_id' => $this->org->org_id,
            'profile_group_id' => $profileGroup->group_id,
            'name' => 'Test Boost Rule',
            'is_active' => true,
            'trigger_type' => 'manual',
            'apply_to_social_profiles' => [$integration->integration_id],
            'boost_config' => ['budget_amount' => 100],
            'created_by' => $this->user->user_id,
        ]);
    }

    // ===== Find Orphaned Profiles Tests =====

    /** @test */
    public function it_finds_orphaned_profiles_with_deleted_connection()
    {
        $accountId = '123456789';

        // Create connection
        $connection = $this->createConnection('meta', ['page' => [$accountId]]);

        // Create profile linked to this connection
        $integration = $this->createIntegration('facebook', $accountId, $connection->connection_id);

        // Delete the connection
        $connection->delete();

        // Find orphaned profiles
        $orphaned = $this->service->findOrphanedProfiles($this->org->org_id);

        $this->assertCount(1, $orphaned);
        $this->assertEquals($integration->integration_id, $orphaned->first()->integration_id);
    }

    /** @test */
    public function it_finds_orphaned_profiles_with_inactive_connection()
    {
        $accountId = '123456789';

        // Create inactive connection
        $connection = $this->createConnection('meta', ['page' => [$accountId]], 'inactive');

        // Create profile linked to this connection
        $integration = $this->createIntegration('facebook', $accountId, $connection->connection_id);

        // Find orphaned profiles
        $orphaned = $this->service->findOrphanedProfiles($this->org->org_id);

        $this->assertCount(1, $orphaned);
        $this->assertEquals($integration->integration_id, $orphaned->first()->integration_id);
    }

    /** @test */
    public function it_does_not_mark_profiles_with_active_connection_as_orphaned()
    {
        $accountId = '123456789';

        // Create active connection
        $connection = $this->createConnection('meta', ['page' => [$accountId]], 'active');

        // Create profile linked to this connection
        $this->createIntegration('facebook', $accountId, $connection->connection_id);

        // Find orphaned profiles
        $orphaned = $this->service->findOrphanedProfiles($this->org->org_id);

        $this->assertCount(0, $orphaned);
    }

    // ===== Find Missing Profiles Tests =====

    /** @test */
    public function it_finds_missing_profiles_for_selected_assets()
    {
        $accountId = '123456789';

        // Create connection with selected asset but NO corresponding profile
        $this->createConnection('meta', ['page' => [$accountId]]);

        // Find missing profiles
        $missing = $this->service->findMissingProfiles($this->org->org_id);

        $this->assertCount(1, $missing);
        $this->assertEquals('facebook', $missing[0]['platform']);
        $this->assertEquals($accountId, $missing[0]['asset_id']);
    }

    /** @test */
    public function it_does_not_report_missing_when_profile_exists()
    {
        $accountId = '123456789';

        // Create connection with selected asset
        $connection = $this->createConnection('meta', ['page' => [$accountId]]);

        // Create corresponding profile
        $this->createIntegration('facebook', $accountId, $connection->connection_id);

        // Find missing profiles
        $missing = $this->service->findMissingProfiles($this->org->org_id);

        $this->assertCount(0, $missing);
    }

    /** @test */
    public function it_finds_multiple_missing_profiles_across_asset_types()
    {
        $pageId = '111111111';
        $instagramId = '222222222';

        // Create connection with multiple asset types
        $this->createConnection('meta', [
            'page' => [$pageId],
            'instagram_account' => [$instagramId],
        ]);

        // Find missing profiles
        $missing = $this->service->findMissingProfiles($this->org->org_id);

        $this->assertCount(2, $missing);
    }

    // ===== Find Profiles to Soft Delete Tests =====

    /** @test */
    public function it_finds_profiles_to_soft_delete_when_asset_not_selected()
    {
        $accountId = '123456789';

        // Create connection WITHOUT the asset selected
        $connection = $this->createConnection('meta', []);

        // Create profile that should be deleted
        $integration = $this->createIntegration('facebook', $accountId, $connection->connection_id);

        // Find profiles to soft delete
        $toDelete = $this->service->findProfilesToSoftDelete($this->org->org_id);

        $this->assertCount(1, $toDelete);
        $this->assertEquals($integration->integration_id, $toDelete->first()->integration_id);
    }

    /** @test */
    public function it_does_not_find_profiles_to_delete_when_asset_is_selected()
    {
        $accountId = '123456789';

        // Create connection WITH the asset selected
        $connection = $this->createConnection('meta', ['page' => [$accountId]]);

        // Create profile
        $this->createIntegration('facebook', $accountId, $connection->connection_id);

        // Find profiles to soft delete
        $toDelete = $this->service->findProfilesToSoftDelete($this->org->org_id);

        $this->assertCount(0, $toDelete);
    }

    // ===== Find Profiles to Restore Tests =====

    /** @test */
    public function it_finds_profiles_to_restore_when_asset_is_selected()
    {
        $accountId = '123456789';

        // Create connection with selected asset
        $connection = $this->createConnection('meta', ['page' => [$accountId]]);

        // Create soft-deleted profile
        $integration = $this->createIntegration('facebook', $accountId, $connection->connection_id);
        $integration->delete();

        // Find profiles to restore
        $toRestore = $this->service->findProfilesToRestore($this->org->org_id);

        $this->assertCount(1, $toRestore);
        $this->assertEquals($integration->integration_id, $toRestore->first()->integration_id);
    }

    /** @test */
    public function it_does_not_find_profiles_to_restore_when_asset_not_selected()
    {
        $accountId = '123456789';

        // Create connection WITHOUT the asset selected
        $connection = $this->createConnection('meta', []);

        // Create soft-deleted profile
        $integration = $this->createIntegration('facebook', $accountId, $connection->connection_id);
        $integration->delete();

        // Find profiles to restore
        $toRestore = $this->service->findProfilesToRestore($this->org->org_id);

        $this->assertCount(0, $toRestore);
    }

    // ===== Find Stale Queue Settings Tests =====

    /** @test */
    public function it_finds_stale_queue_settings_for_deleted_profiles()
    {
        $accountId = '123456789';

        $connection = $this->createConnection('meta', ['page' => [$accountId]]);
        $integration = $this->createIntegration('facebook', $accountId, $connection->connection_id);
        $queueSettings = $this->createQueueSettings($integration);

        // Soft delete profile but NOT queue settings (simulating stale data)
        $integration->delete();
        // Manually restore queue settings to simulate stale state
        IntegrationQueueSettings::withTrashed()
            ->where('integration_id', $integration->integration_id)
            ->restore();

        // Find stale queue settings
        $stale = $this->service->findStaleQueueSettings($this->org->org_id);

        $this->assertCount(1, $stale);
    }

    // ===== Find Invalid Boost Rule Refs Tests =====

    /** @test */
    public function it_finds_boost_rules_with_deleted_profile_references()
    {
        $accountId = '123456789';

        $connection = $this->createConnection('meta', ['page' => [$accountId]]);
        $integration = $this->createIntegration('facebook', $accountId, $connection->connection_id);
        $boostRule = $this->createBoostRuleWithProfile($integration);

        // Soft delete the profile (this normally removes from boost rules via observer)
        // But let's simulate stale data by re-adding the reference
        $integration->delete();
        $boostRule->update(['apply_to_social_profiles' => [$integration->integration_id]]);

        // Find invalid boost rule refs
        $invalid = $this->service->findInvalidBoostRuleRefs($this->org->org_id);

        $this->assertCount(1, $invalid);
    }

    // ===== Fix Methods Tests =====

    /** @test */
    public function it_soft_deletes_orphaned_profiles()
    {
        $accountId = '123456789';

        // Create connection and profile
        $connection = $this->createConnection('meta', ['page' => [$accountId]]);
        $integration = $this->createIntegration('facebook', $accountId, $connection->connection_id);

        // Delete the connection to orphan the profile
        $connection->delete();

        // Fix orphaned profiles
        $count = $this->service->softDeleteOrphanedProfiles($this->org->org_id);

        $this->assertEquals(1, $count);
        $this->assertSoftDeleted('cmis.integrations', [
            'integration_id' => $integration->integration_id,
        ]);
    }

    /** @test */
    public function it_soft_deletes_deselected_profiles()
    {
        $accountId = '123456789';

        // Create connection WITHOUT asset selected
        $connection = $this->createConnection('meta', []);

        // Create profile that should be deleted
        $integration = $this->createIntegration('facebook', $accountId, $connection->connection_id);

        // Fix deselected profiles
        $count = $this->service->softDeleteDeselectedProfiles($this->org->org_id);

        $this->assertEquals(1, $count);
        $this->assertSoftDeleted('cmis.integrations', [
            'integration_id' => $integration->integration_id,
        ]);
    }

    /** @test */
    public function it_restores_selected_profiles()
    {
        $accountId = '123456789';

        // Create connection with selected asset
        $connection = $this->createConnection('meta', ['page' => [$accountId]]);

        // Create soft-deleted profile
        $integration = $this->createIntegration('facebook', $accountId, $connection->connection_id);
        $integration->delete();

        // Fix - restore selected profiles
        $count = $this->service->restoreSelectedProfiles($this->org->org_id);

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('cmis.integrations', [
            'integration_id' => $integration->integration_id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_creates_missing_profiles_by_dispatching_sync_jobs()
    {
        Queue::fake();

        $accountId = '123456789';

        // Create connection with selected asset but no profile
        $this->createConnection('meta', ['page' => [$accountId]]);

        // Fix - create missing profiles
        $count = $this->service->createMissingProfiles($this->org->org_id);

        $this->assertEquals(1, $count);
        Queue::assertPushed(\App\Jobs\SyncMetaIntegrationRecords::class);
    }

    /** @test */
    public function it_cleans_up_stale_queue_settings()
    {
        $accountId = '123456789';

        $connection = $this->createConnection('meta', ['page' => [$accountId]]);
        $integration = $this->createIntegration('facebook', $accountId, $connection->connection_id);
        $queueSettings = $this->createQueueSettings($integration);

        // Soft delete profile but restore queue settings to simulate stale state
        $integration->delete();
        IntegrationQueueSettings::withTrashed()
            ->where('integration_id', $integration->integration_id)
            ->restore();

        // Clean up stale data
        $stats = $this->service->cleanupStaleData($this->org->org_id);

        $this->assertEquals(1, $stats['queue_settings']);
        $this->assertSoftDeleted('cmis.integration_queue_settings', [
            'integration_id' => $integration->integration_id,
        ]);
    }

    /** @test */
    public function it_cleans_up_invalid_boost_rule_references()
    {
        $accountId = '123456789';

        $connection = $this->createConnection('meta', ['page' => [$accountId]]);
        $integration = $this->createIntegration('facebook', $accountId, $connection->connection_id);
        $boostRule = $this->createBoostRuleWithProfile($integration);

        // Soft delete profile and re-add invalid reference
        $integration->delete();
        $boostRule->update(['apply_to_social_profiles' => [$integration->integration_id]]);

        // Clean up stale data
        $stats = $this->service->cleanupStaleData($this->org->org_id);

        $this->assertEquals(1, $stats['boost_rules']);
        $boostRule->refresh();
        $this->assertEmpty($boostRule->apply_to_social_profiles);
    }

    // ===== Full Audit Tests =====

    /** @test */
    public function it_runs_full_audit_in_dry_run_mode()
    {
        $accountId = '123456789';

        // Create orphaned profile
        $connection = $this->createConnection('meta', ['page' => [$accountId]]);
        $integration = $this->createIntegration('facebook', $accountId, $connection->connection_id);
        $connection->delete();

        // Run audit in dry-run mode
        $results = $this->service->runFullAudit($this->org->org_id, false);

        // Should find issues but not fix them
        $this->assertEquals(1, $results['orphaned_profiles']['found']);
        $this->assertEquals(0, $results['orphaned_profiles']['fixed']);

        // Profile should still exist (not deleted)
        $this->assertDatabaseHas('cmis.integrations', [
            'integration_id' => $integration->integration_id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_runs_full_audit_in_fix_mode()
    {
        $accountId = '123456789';

        // Create orphaned profile
        $connection = $this->createConnection('meta', ['page' => [$accountId]]);
        $integration = $this->createIntegration('facebook', $accountId, $connection->connection_id);
        $connection->delete();

        // Run audit in fix mode
        $results = $this->service->runFullAudit($this->org->org_id, true);

        // Should find and fix issues
        $this->assertEquals(1, $results['orphaned_profiles']['found']);
        $this->assertEquals(1, $results['orphaned_profiles']['fixed']);

        // Profile should be soft deleted
        $this->assertSoftDeleted('cmis.integrations', [
            'integration_id' => $integration->integration_id,
        ]);
    }

    /** @test */
    public function it_returns_duration_in_audit_results()
    {
        $results = $this->service->runFullAudit($this->org->org_id, false);

        $this->assertArrayHasKey('duration_seconds', $results);
        $this->assertIsNumeric($results['duration_seconds']);
    }

    // ===== Multi-Org Isolation Tests =====

    /** @test */
    public function it_only_audits_specified_org()
    {
        // Create another org
        $otherOrg = Org::factory()->create();

        // Create orphaned profile in this org
        $connection1 = $this->createConnection('meta', ['page' => ['111111111']]);
        $integration1 = $this->createIntegration('facebook', '111111111', $connection1->connection_id);
        $connection1->delete();

        // Create orphaned profile in other org
        $connection2 = PlatformConnection::create([
            'connection_id' => (string) Str::uuid(),
            'org_id' => $otherOrg->org_id,
            'platform' => 'meta',
            'account_id' => (string) fake()->numberBetween(100000000, 999999999),
            'account_name' => fake()->company(),
            'status' => 'active',
            'access_token' => Str::random(64),
            'account_metadata' => ['selected_assets' => ['page' => ['222222222']]],
        ]);

        $integration2 = Integration::create([
            'integration_id' => (string) Str::uuid(),
            'org_id' => $otherOrg->org_id,
            'platform' => 'facebook',
            'account_id' => '222222222',
            'account_name' => fake()->company(),
            'username' => fake()->userName(),
            'access_token' => Str::random(64),
            'is_active' => true,
            'status' => 'active',
            'metadata' => ['connection_id' => $connection2->connection_id],
        ]);
        $connection2->delete();

        // Audit only this org
        $results = $this->service->runFullAudit($this->org->org_id, true);

        // Should only fix this org's orphaned profile
        $this->assertEquals(1, $results['orphaned_profiles']['fixed']);

        // Other org's profile should still exist
        $this->assertDatabaseHas('cmis.integrations', [
            'integration_id' => $integration2->integration_id,
            'deleted_at' => null,
        ]);
    }

    // ===== Command Test =====

    /** @test */
    public function artisan_command_runs_successfully()
    {
        $this->artisan('profiles:audit-sync')
            ->assertSuccessful();
    }

    /** @test */
    public function artisan_command_accepts_org_option()
    {
        $this->artisan('profiles:audit-sync', ['--org' => $this->org->org_id])
            ->assertSuccessful();
    }

    /** @test */
    public function artisan_command_accepts_fix_option()
    {
        $this->artisan('profiles:audit-sync', ['--fix' => true])
            ->assertSuccessful();
    }

    /** @test */
    public function artisan_command_accepts_type_option()
    {
        $this->artisan('profiles:audit-sync', ['--org' => $this->org->org_id, '--type' => 'orphaned'])
            ->assertSuccessful();
    }
}
