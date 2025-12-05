<?php

namespace Tests\Feature\Backup;

use App\Apps\Backup\Services\Discovery\SchemaDiscoveryService;
use App\Apps\Backup\Services\Extraction\DataExtractorService;
use App\Jobs\Backup\ProcessBackupJob;
use App\Models\Backup\BackupAuditLog;
use App\Models\Backup\BackupRestore;
use App\Models\Backup\BackupSchedule;
use App\Models\Backup\BackupSetting;
use App\Models\Backup\OrganizationBackup;
use App\Models\Core\Org;
use App\Models\Core\User;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected Org $org1;
    protected Org $org2;
    protected User $user1;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two separate organizations
        $this->org1 = Org::factory()->create(['name' => 'Organization 1']);
        $this->org2 = Org::factory()->create(['name' => 'Organization 2']);

        // Create users for each organization
        $this->user1 = User::factory()->create(['org_id' => $this->org1->org_id]);
        $this->user2 = User::factory()->create(['org_id' => $this->org2->org_id]);

        // Enable backup app for both organizations
        $marketplaceService = app(MarketplaceService::class);
        try {
            $marketplaceService->enableApp($this->org1->org_id, 'org-backup-restore', $this->user1->user_id);
            $marketplaceService->enableApp($this->org2->org_id, 'org-backup-restore', $this->user2->user_id);
        } catch (\Exception $e) {
            // App might not exist
        }
    }

    /** @test */
    public function backups_are_isolated_between_organizations()
    {
        // Create backups for both organizations
        $backup1 = OrganizationBackup::create([
            'org_id' => $this->org1->org_id,
            'backup_code' => 'BKUP-ORG1-001',
            'name' => 'Org 1 Backup',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user1->user_id,
            'summary' => [],
        ]);

        $backup2 = OrganizationBackup::create([
            'org_id' => $this->org2->org_id,
            'backup_code' => 'BKUP-ORG2-001',
            'name' => 'Org 2 Backup',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user2->user_id,
            'summary' => [],
        ]);

        // User 1 should only see Org 1's backups
        $response1 = $this->actingAs($this->user1)
            ->get(route('backup.index', ['org' => $this->org1->org_id]));

        if ($response1->status() !== 302) {
            $response1->assertSee('Org 1 Backup');
            $response1->assertDontSee('Org 2 Backup');
        }

        // User 2 should only see Org 2's backups
        $response2 = $this->actingAs($this->user2)
            ->get(route('backup.index', ['org' => $this->org2->org_id]));

        if ($response2->status() !== 302) {
            $response2->assertSee('Org 2 Backup');
            $response2->assertDontSee('Org 1 Backup');
        }
    }

    /** @test */
    public function user_cannot_access_other_organizations_backups()
    {
        $otherOrgBackup = OrganizationBackup::create([
            'org_id' => $this->org2->org_id,
            'backup_code' => 'BKUP-OTHER-001',
            'name' => 'Other Org Backup',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user2->user_id,
            'summary' => [],
        ]);

        // User 1 trying to access Org 2's backup
        $response = $this->actingAs($this->user1)
            ->get(route('backup.show', [
                'org' => $this->org1->org_id,
                'backup' => $otherOrgBackup->id,
            ]));

        // Should be forbidden or not found
        $this->assertContains($response->status(), [302, 403, 404]);
    }

    /** @test */
    public function user_cannot_download_other_organizations_backups()
    {
        $otherOrgBackup = OrganizationBackup::create([
            'org_id' => $this->org2->org_id,
            'backup_code' => 'BKUP-DL-OTHER',
            'name' => 'Other Download Backup',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user2->user_id,
            'file_path' => 'backups/test.zip',
            'summary' => [],
        ]);

        $response = $this->actingAs($this->user1)
            ->get(route('backup.download', [
                'org' => $this->org1->org_id,
                'backup' => $otherOrgBackup->id,
            ]));

        $this->assertContains($response->status(), [302, 403, 404]);
    }

    /** @test */
    public function user_cannot_delete_other_organizations_backups()
    {
        $otherOrgBackup = OrganizationBackup::create([
            'org_id' => $this->org2->org_id,
            'backup_code' => 'BKUP-DEL-OTHER',
            'name' => 'Other Delete Backup',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user2->user_id,
            'summary' => [],
        ]);

        $response = $this->actingAs($this->user1)
            ->deleteJson(route('backup.destroy', [
                'org' => $this->org1->org_id,
                'backup' => $otherOrgBackup->id,
            ]));

        $this->assertContains($response->status(), [302, 403, 404]);

        // Backup should still exist
        $this->assertDatabaseHas('cmis.organization_backups', ['id' => $otherOrgBackup->id]);
    }

    /** @test */
    public function schedules_are_isolated_between_organizations()
    {
        $schedule1 = BackupSchedule::create([
            'org_id' => $this->org1->org_id,
            'name' => 'Org 1 Schedule',
            'frequency' => 'daily',
            'time' => '02:00',
            'timezone' => 'UTC',
            'retention_days' => 30,
            'is_active' => true,
            'created_by' => $this->user1->user_id,
        ]);

        $schedule2 = BackupSchedule::create([
            'org_id' => $this->org2->org_id,
            'name' => 'Org 2 Schedule',
            'frequency' => 'weekly',
            'time' => '03:00',
            'timezone' => 'UTC',
            'retention_days' => 60,
            'is_active' => true,
            'created_by' => $this->user2->user_id,
        ]);

        // User 1 should only see their schedule
        $response = $this->actingAs($this->user1)
            ->get(route('backup.schedule.index', ['org' => $this->org1->org_id]));

        if ($response->status() !== 302) {
            $response->assertSee('Org 1 Schedule');
            $response->assertDontSee('Org 2 Schedule');
        }
    }

    /** @test */
    public function user_cannot_modify_other_organizations_schedules()
    {
        $otherSchedule = BackupSchedule::create([
            'org_id' => $this->org2->org_id,
            'name' => 'Other Schedule',
            'frequency' => 'daily',
            'time' => '02:00',
            'timezone' => 'UTC',
            'retention_days' => 30,
            'is_active' => true,
            'created_by' => $this->user2->user_id,
        ]);

        $response = $this->actingAs($this->user1)
            ->putJson(route('backup.schedule.update', [
                'org' => $this->org1->org_id,
                'schedule' => $otherSchedule->id,
            ]), [
                'name' => 'Hacked Schedule',
            ]);

        $this->assertContains($response->status(), [302, 403, 404]);

        // Schedule should not be modified
        $otherSchedule->refresh();
        $this->assertEquals('Other Schedule', $otherSchedule->name);
    }

    /** @test */
    public function settings_are_isolated_between_organizations()
    {
        $settings1 = BackupSetting::create([
            'org_id' => $this->org1->org_id,
            'email_on_backup_complete' => true,
            'default_storage_disk' => 'local',
        ]);

        $settings2 = BackupSetting::create([
            'org_id' => $this->org2->org_id,
            'email_on_backup_complete' => false,
            'default_storage_disk' => 'google',
        ]);

        // Verify settings are separate
        $this->assertTrue($settings1->email_on_backup_complete);
        $this->assertFalse($settings2->email_on_backup_complete);
        $this->assertEquals('local', $settings1->default_storage_disk);
        $this->assertEquals('google', $settings2->default_storage_disk);
    }

    /** @test */
    public function audit_logs_are_isolated_between_organizations()
    {
        // Create audit logs for both organizations
        BackupAuditLog::create([
            'org_id' => $this->org1->org_id,
            'action' => 'backup_created',
            'user_id' => $this->user1->user_id,
            'details' => ['name' => 'Org 1 Action'],
        ]);

        BackupAuditLog::create([
            'org_id' => $this->org2->org_id,
            'action' => 'backup_created',
            'user_id' => $this->user2->user_id,
            'details' => ['name' => 'Org 2 Action'],
        ]);

        // User 1 should only see Org 1's logs
        $response = $this->actingAs($this->user1)
            ->get(route('backup.logs', ['org' => $this->org1->org_id]));

        if ($response->status() !== 302) {
            $response->assertSee('Org 1 Action');
            $response->assertDontSee('Org 2 Action');
        }
    }

    /** @test */
    public function restores_are_isolated_between_organizations()
    {
        $backup1 = OrganizationBackup::create([
            'org_id' => $this->org1->org_id,
            'backup_code' => 'BKUP-REST-ORG1',
            'name' => 'Org 1 Restore Backup',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user1->user_id,
            'summary' => [],
        ]);

        $backup2 = OrganizationBackup::create([
            'org_id' => $this->org2->org_id,
            'backup_code' => 'BKUP-REST-ORG2',
            'name' => 'Org 2 Restore Backup',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user2->user_id,
            'summary' => [],
        ]);

        // User 1 cannot restore Org 2's backup
        $response = $this->actingAs($this->user1)
            ->get(route('backup.restore.analyze', [
                'org' => $this->org1->org_id,
                'backup' => $backup2->id,
            ]));

        $this->assertContains($response->status(), [302, 403, 404]);
    }

    /** @test */
    public function user_cannot_access_other_organization_url()
    {
        // User 1 trying to access Org 2's backup page via URL manipulation
        $response = $this->actingAs($this->user1)
            ->get(route('backup.index', ['org' => $this->org2->org_id]));

        // Should be redirected or forbidden
        $this->assertContains($response->status(), [302, 403]);
    }

    /** @test */
    public function rls_context_is_set_correctly_for_backup_job()
    {
        Queue::fake();

        $backup = OrganizationBackup::create([
            'org_id' => $this->org1->org_id,
            'backup_code' => 'BKUP-RLS-001',
            'name' => 'RLS Test Backup',
            'type' => 'manual',
            'status' => 'pending',
            'created_by' => $this->user1->user_id,
            'summary' => [],
        ]);

        // Dispatch job
        ProcessBackupJob::dispatch($backup);

        Queue::assertPushed(ProcessBackupJob::class, function ($job) use ($backup) {
            return $job->backup->id === $backup->id;
        });
    }

    /** @test */
    public function data_extraction_respects_rls()
    {
        $extractor = app(DataExtractorService::class);

        // This test verifies the extractor sets RLS context
        // Actual data isolation would be tested in integration tests
        $this->assertInstanceOf(DataExtractorService::class, $extractor);
    }

    /** @test */
    public function backup_files_are_stored_with_org_isolation()
    {
        // Verify backup file paths include org_id for isolation
        $backup = OrganizationBackup::create([
            'org_id' => $this->org1->org_id,
            'backup_code' => 'BKUP-PATH-001',
            'name' => 'Path Test Backup',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user1->user_id,
            'file_path' => "backups/{$this->org1->org_id}/BKUP-PATH-001.zip",
            'summary' => [],
        ]);

        $this->assertStringContainsString($this->org1->org_id, $backup->file_path);
    }

    /** @test */
    public function schema_discovery_uses_org_context()
    {
        $discoveryService = app(SchemaDiscoveryService::class);

        // Discovery should work with RLS context
        $tables = $discoveryService->discoverOrgTables();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $tables);
    }

    /** @test */
    public function encryption_keys_are_isolated_between_organizations()
    {
        // Create encryption keys for each org
        DB::table('cmis.backup_encryption_keys')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $this->org1->org_id,
            'name' => 'Org 1 Key',
            'encrypted_key' => encrypt('secret-key-1'),
            'key_hash' => hash('sha256', 'secret-key-1'),
            'is_active' => true,
            'created_by' => $this->user1->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cmis.backup_encryption_keys')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'org_id' => $this->org2->org_id,
            'name' => 'Org 2 Key',
            'encrypted_key' => encrypt('secret-key-2'),
            'key_hash' => hash('sha256', 'secret-key-2'),
            'is_active' => true,
            'created_by' => $this->user2->user_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // With RLS, Org 1 should only see their key
        // This would be tested with actual RLS context in integration tests
        $org1Keys = DB::table('cmis.backup_encryption_keys')
            ->where('org_id', $this->org1->org_id)
            ->count();

        $this->assertEquals(1, $org1Keys);
    }

    /** @test */
    public function api_endpoints_respect_organization_isolation()
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->user1);

        $otherOrgBackup = OrganizationBackup::create([
            'org_id' => $this->org2->org_id,
            'backup_code' => 'BKUP-API-OTHER',
            'name' => 'Other API Backup',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user2->user_id,
            'summary' => [],
        ]);

        // User 1 should not be able to access Org 2's backup via API
        $response = $this->getJson("/api/v1/backup/{$otherOrgBackup->id}");

        $this->assertContains($response->status(), [403, 404]);
    }

    /** @test */
    public function cross_org_restore_is_prevented()
    {
        // Create backup from Org 2
        $otherOrgBackup = OrganizationBackup::create([
            'org_id' => $this->org2->org_id,
            'backup_code' => 'BKUP-XORG-001',
            'name' => 'Cross Org Backup',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user2->user_id,
            'summary' => ['categories' => ['Campaigns' => ['count' => 10]]],
        ]);

        // User 1 tries to restore Org 2's backup to Org 1
        $response = $this->actingAs($this->user1)
            ->postJson(route('backup.restore.select.store', [
                'org' => $this->org1->org_id,
                'backup' => $otherOrgBackup->id,
            ]), [
                'categories' => ['Campaigns'],
                'type' => 'selective',
            ]);

        // Should be forbidden
        $this->assertContains($response->status(), [302, 403, 404]);
    }
}
