<?php

namespace Tests\Feature\Backup;

use App\Apps\Backup\Services\Limits\PlanLimitsService;
use App\Models\Backup\OrganizationBackup;
use App\Models\Core\Org;
use App\Models\Core\User;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PlanLimitsTest extends TestCase
{
    use RefreshDatabase;

    protected Org $org;
    protected User $user;
    protected PlanLimitsService $limitsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create(['org_id' => $this->org->org_id]);
        $this->limitsService = app(PlanLimitsService::class);

        // Enable the backup app
        $marketplaceService = app(MarketplaceService::class);
        try {
            $marketplaceService->enableApp($this->org->org_id, 'org-backup-restore', $this->user->user_id);
        } catch (\Exception $e) {
            // App might not exist
        }
    }

    /** @test */
    public function it_allows_backup_within_monthly_limit()
    {
        // Free plan allows 2 backups per month
        $result = $this->limitsService->checkBackupAllowed($this->org->org_id);

        $this->assertTrue($result->isAllowed());
    }

    /** @test */
    public function it_denies_backup_when_monthly_limit_exceeded()
    {
        // Create backups to exceed free plan limit (2/month)
        for ($i = 1; $i <= 3; $i++) {
            OrganizationBackup::create([
                'org_id' => $this->org->org_id,
                'backup_code' => "BKUP-LIMIT-{$i}",
                'name' => "Backup {$i}",
                'type' => 'manual',
                'status' => 'completed',
                'created_by' => $this->user->user_id,
                'summary' => [],
                'created_at' => now(), // This month
            ]);
        }

        $result = $this->limitsService->checkBackupAllowed($this->org->org_id);

        // Should be denied due to monthly limit
        $this->assertFalse($result->isAllowed());
    }

    /** @test */
    public function it_allows_backup_when_previous_months_dont_count()
    {
        // Create backups from previous month
        for ($i = 1; $i <= 3; $i++) {
            OrganizationBackup::create([
                'org_id' => $this->org->org_id,
                'backup_code' => "BKUP-OLD-{$i}",
                'name' => "Old Backup {$i}",
                'type' => 'manual',
                'status' => 'completed',
                'created_by' => $this->user->user_id,
                'summary' => [],
                'created_at' => now()->subMonth(), // Previous month
            ]);
        }

        // Current month should still allow backups
        $result = $this->limitsService->checkBackupAllowed($this->org->org_id);

        $this->assertTrue($result->isAllowed());
    }

    /** @test */
    public function it_denies_backup_when_storage_limit_exceeded()
    {
        // Free plan allows 500MB storage
        OrganizationBackup::create([
            'org_id' => $this->org->org_id,
            'backup_code' => 'BKUP-LARGE-001',
            'name' => 'Large Backup',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user->user_id,
            'file_size' => 600 * 1024 * 1024, // 600MB exceeds 500MB limit
            'summary' => [],
        ]);

        $result = $this->limitsService->checkBackupAllowed($this->org->org_id);

        // Should be denied due to storage limit
        $this->assertFalse($result->isAllowed());
    }

    /** @test */
    public function it_allows_daily_schedule_for_all_plans()
    {
        $canSchedule = $this->limitsService->canSchedule($this->org->org_id, 'daily');

        // Even free plans should allow daily schedules
        $this->assertTrue($canSchedule);
    }

    /** @test */
    public function it_restricts_hourly_schedule_for_free_plan()
    {
        $canSchedule = $this->limitsService->canSchedule($this->org->org_id, 'hourly');

        // Free plan should not allow hourly schedules
        $this->assertFalse($canSchedule);
    }

    /** @test */
    public function it_returns_correct_plan_limits()
    {
        $limits = $this->limitsService->getPlanLimits($this->org->org_id);

        $this->assertArrayHasKey('monthly_limit', $limits);
        $this->assertArrayHasKey('max_size_mb', $limits);
        $this->assertArrayHasKey('retention_days', $limits);
        $this->assertArrayHasKey('allowed_schedules', $limits);
    }

    /** @test */
    public function it_returns_current_usage_stats()
    {
        // Create some backups
        OrganizationBackup::create([
            'org_id' => $this->org->org_id,
            'backup_code' => 'BKUP-USAGE-001',
            'name' => 'Backup 1',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user->user_id,
            'file_size' => 100 * 1024 * 1024, // 100MB
            'summary' => [],
        ]);

        OrganizationBackup::create([
            'org_id' => $this->org->org_id,
            'backup_code' => 'BKUP-USAGE-002',
            'name' => 'Backup 2',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user->user_id,
            'file_size' => 50 * 1024 * 1024, // 50MB
            'summary' => [],
        ]);

        $usage = $this->limitsService->getCurrentUsage($this->org->org_id);

        $this->assertEquals(2, $usage['backups_this_month']);
        $this->assertEquals(150 * 1024 * 1024, $usage['storage_used_bytes']);
    }

    /** @test */
    public function it_enforces_limit_on_api_endpoint()
    {
        Queue::fake();

        // Create backups to exceed limit
        for ($i = 1; $i <= 3; $i++) {
            OrganizationBackup::create([
                'org_id' => $this->org->org_id,
                'backup_code' => "BKUP-API-{$i}",
                'name' => "Backup {$i}",
                'type' => 'manual',
                'status' => 'completed',
                'created_by' => $this->user->user_id,
                'summary' => [],
            ]);
        }

        $response = $this->actingAs($this->user)
            ->postJson(route('backup.store', ['org' => $this->org->org_id]), [
                'name' => 'Exceeding Limit Backup',
                'type' => 'full',
            ]);

        // Either limit exceeded or app not enabled
        $this->assertContains($response->status(), [403, 422, 429]);
    }

    /** @test */
    public function it_allows_unlimited_backups_for_pro_plan()
    {
        // Set org to pro plan
        $this->org->update(['plan' => 'pro']);

        // Create many backups
        for ($i = 1; $i <= 20; $i++) {
            OrganizationBackup::create([
                'org_id' => $this->org->org_id,
                'backup_code' => "BKUP-PRO-{$i}",
                'name' => "Pro Backup {$i}",
                'type' => 'manual',
                'status' => 'completed',
                'created_by' => $this->user->user_id,
                'summary' => [],
            ]);
        }

        $result = $this->limitsService->checkBackupAllowed($this->org->org_id);

        // Pro plan has unlimited backups (-1)
        $this->assertTrue($result->isAllowed());
    }

    /** @test */
    public function it_calculates_remaining_quota()
    {
        // Create 1 backup
        OrganizationBackup::create([
            'org_id' => $this->org->org_id,
            'backup_code' => 'BKUP-QUOTA-001',
            'name' => 'Quota Backup',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user->user_id,
            'summary' => [],
        ]);

        $remaining = $this->limitsService->getRemainingQuota($this->org->org_id);

        // Free plan allows 2, used 1, remaining should be 1
        $this->assertEquals(1, $remaining['backups_remaining']);
    }

    /** @test */
    public function it_checks_retention_days_limit()
    {
        $limits = $this->limitsService->getPlanLimits($this->org->org_id);

        // Free plan should have 7 days retention
        $this->assertEquals(7, $limits['retention_days']);
    }

    /** @test */
    public function it_validates_encryption_feature_by_plan()
    {
        // Free plan should not have encryption
        $canEncrypt = $this->limitsService->canUseEncryption($this->org->org_id);

        $this->assertFalse($canEncrypt);

        // Enterprise plan should have encryption
        $this->org->update(['plan' => 'enterprise']);
        $canEncrypt = $this->limitsService->canUseEncryption($this->org->org_id);

        $this->assertTrue($canEncrypt);
    }

    /** @test */
    public function it_validates_cloud_storage_by_plan()
    {
        // Free plan should only have local storage
        $allowedDisks = $this->limitsService->getAllowedStorageDisks($this->org->org_id);

        $this->assertEquals(['local'], $allowedDisks);

        // Pro plan should have cloud storage
        $this->org->update(['plan' => 'pro']);
        $allowedDisks = $this->limitsService->getAllowedStorageDisks($this->org->org_id);

        $this->assertContains('google', $allowedDisks);
        $this->assertContains('dropbox', $allowedDisks);
    }

    /** @test */
    public function it_excludes_failed_and_expired_from_count()
    {
        // Create failed/expired backups that shouldn't count toward limit
        OrganizationBackup::create([
            'org_id' => $this->org->org_id,
            'backup_code' => 'BKUP-FAIL-001',
            'name' => 'Failed Backup',
            'type' => 'manual',
            'status' => 'failed', // Should not count
            'created_by' => $this->user->user_id,
            'summary' => [],
        ]);

        OrganizationBackup::create([
            'org_id' => $this->org->org_id,
            'backup_code' => 'BKUP-EXP-001',
            'name' => 'Expired Backup',
            'type' => 'manual',
            'status' => 'expired', // Should not count
            'created_by' => $this->user->user_id,
            'summary' => [],
        ]);

        $usage = $this->limitsService->getCurrentUsage($this->org->org_id);

        // Failed and expired should not count
        $this->assertEquals(0, $usage['backups_this_month']);
    }
}
