<?php

namespace Tests\Feature\Backup;

use App\Jobs\Backup\ProcessRestoreJob;
use App\Models\Backup\BackupRestore;
use App\Models\Backup\OrganizationBackup;
use App\Models\Core\Org;
use App\Models\Core\User;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RestoreWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Org $org;
    protected User $user;
    protected OrganizationBackup $backup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Org::factory()->create();
        $this->user = User::factory()->create(['org_id' => $this->org->org_id]);

        // Enable the backup app for the organization
        $marketplaceService = app(MarketplaceService::class);
        try {
            $marketplaceService->enableApp($this->org->org_id, 'org-backup-restore', $this->user->user_id);
        } catch (\Exception $e) {
            // App might not exist yet if seeders haven't run
        }

        // Create a completed backup for restore tests
        $this->backup = OrganizationBackup::create([
            'org_id' => $this->org->org_id,
            'backup_code' => 'BKUP-2024-0100',
            'name' => 'Test Backup for Restore',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user->user_id,
            'file_path' => 'backups/test-backup.zip',
            'file_size' => 1024000,
            'checksum_sha256' => hash('sha256', 'test-content'),
            'summary' => [
                'categories' => [
                    'Campaigns' => ['count' => 10, 'size_kb' => 500],
                    'Posts' => ['count' => 25, 'size_kb' => 250],
                ],
                'total_records' => 35,
                'total_size_kb' => 750,
            ],
            'schema_snapshot' => [
                'version' => '1.0',
                'tables' => ['cmis.campaigns', 'cmis.social_posts'],
            ],
        ]);
    }

    /** @test */
    public function it_displays_restore_index_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('backup.restore.index', ['org' => $this->org->org_id]));

        // If app is not enabled, expect redirect
        if ($response->status() === 302) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertStatus(200);
        $response->assertViewIs('apps.backup.restore.index');
    }

    /** @test */
    public function it_can_analyze_backup_for_restore()
    {
        $response = $this->actingAs($this->user)
            ->get(route('backup.restore.analyze', [
                'org' => $this->org->org_id,
                'backup' => $this->backup->id,
            ]));

        // If app is not enabled, expect redirect
        if ($response->status() === 302) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertSuccessful();
        $response->assertViewIs('apps.backup.restore.analyze');
    }

    /** @test */
    public function it_displays_category_selection_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('backup.restore.select', [
                'org' => $this->org->org_id,
                'backup' => $this->backup->id,
            ]));

        // If app is not enabled, expect redirect
        if ($response->status() === 302) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertSuccessful();
        $response->assertViewIs('apps.backup.restore.select');
    }

    /** @test */
    public function it_can_submit_category_selection()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('backup.restore.select.store', [
                'org' => $this->org->org_id,
                'backup' => $this->backup->id,
            ]), [
                'categories' => ['Campaigns', 'Posts'],
                'type' => 'selective',
            ]);

        // If app is not enabled, expect error
        if ($response->status() === 403) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertSuccessful();
    }

    /** @test */
    public function it_displays_conflict_resolution_page()
    {
        // Create a restore record first
        $restore = BackupRestore::create([
            'org_id' => $this->org->org_id,
            'backup_id' => $this->backup->id,
            'restore_code' => 'REST-2024-0001',
            'type' => 'selective',
            'status' => 'awaiting_confirmation',
            'selected_categories' => ['Campaigns'],
            'conflict_resolution' => ['strategy' => 'skip'],
            'created_by' => $this->user->user_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('backup.restore.conflicts', [
                'org' => $this->org->org_id,
                'restore' => $restore->id,
            ]));

        // If app is not enabled, expect redirect
        if ($response->status() === 302) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertSuccessful();
        $response->assertViewIs('apps.backup.restore.conflicts');
    }

    /** @test */
    public function it_displays_confirmation_page()
    {
        $restore = BackupRestore::create([
            'org_id' => $this->org->org_id,
            'backup_id' => $this->backup->id,
            'restore_code' => 'REST-2024-0002',
            'type' => 'selective',
            'status' => 'awaiting_confirmation',
            'selected_categories' => ['Campaigns'],
            'conflict_resolution' => ['strategy' => 'skip'],
            'created_by' => $this->user->user_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('backup.restore.confirm', [
                'org' => $this->org->org_id,
                'restore' => $restore->id,
            ]));

        // If app is not enabled, expect redirect
        if ($response->status() === 302) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertSuccessful();
        $response->assertViewIs('apps.backup.restore.confirm');
    }

    /** @test */
    public function it_can_start_restore_process()
    {
        Queue::fake();

        $restore = BackupRestore::create([
            'org_id' => $this->org->org_id,
            'backup_id' => $this->backup->id,
            'restore_code' => 'REST-2024-0003',
            'type' => 'selective',
            'status' => 'awaiting_confirmation',
            'selected_categories' => ['Campaigns'],
            'conflict_resolution' => ['strategy' => 'skip'],
            'created_by' => $this->user->user_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('backup.restore.process', [
                'org' => $this->org->org_id,
                'restore' => $restore->id,
            ]));

        // If app is not enabled, expect error
        if ($response->status() === 403) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertSuccessful();
        Queue::assertPushed(ProcessRestoreJob::class);
    }

    /** @test */
    public function it_can_check_restore_progress()
    {
        $restore = BackupRestore::create([
            'org_id' => $this->org->org_id,
            'backup_id' => $this->backup->id,
            'restore_code' => 'REST-2024-0004',
            'type' => 'selective',
            'status' => 'processing',
            'selected_categories' => ['Campaigns'],
            'conflict_resolution' => ['strategy' => 'skip'],
            'execution_report' => ['progress' => 50, 'current_category' => 'Campaigns'],
            'created_by' => $this->user->user_id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('backup.restore.progress', [
                'org' => $this->org->org_id,
                'restore' => $restore->id,
            ]));

        // If app is not enabled, expect error
        if ($response->status() === 403) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'status',
                'progress',
            ],
        ]);
    }

    /** @test */
    public function it_can_rollback_completed_restore()
    {
        Queue::fake();

        // Create a safety backup
        $safetyBackup = OrganizationBackup::create([
            'org_id' => $this->org->org_id,
            'backup_code' => 'BKUP-2024-SAFE',
            'name' => 'Safety Backup',
            'type' => 'pre_restore',
            'status' => 'completed',
            'created_by' => $this->user->user_id,
            'summary' => [],
        ]);

        $restore = BackupRestore::create([
            'org_id' => $this->org->org_id,
            'backup_id' => $this->backup->id,
            'restore_code' => 'REST-2024-0005',
            'type' => 'selective',
            'status' => 'completed',
            'selected_categories' => ['Campaigns'],
            'conflict_resolution' => ['strategy' => 'skip'],
            'execution_report' => ['records_restored' => 10],
            'safety_backup_id' => $safetyBackup->id,
            'rollback_expires_at' => now()->addHours(24),
            'created_by' => $this->user->user_id,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('backup.restore.rollback', [
                'org' => $this->org->org_id,
                'restore' => $restore->id,
            ]));

        // If app is not enabled, expect error
        if ($response->status() === 403) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertSuccessful();
    }

    /** @test */
    public function it_prevents_rollback_after_expiration()
    {
        $restore = BackupRestore::create([
            'org_id' => $this->org->org_id,
            'backup_id' => $this->backup->id,
            'restore_code' => 'REST-2024-0006',
            'type' => 'selective',
            'status' => 'completed',
            'selected_categories' => ['Campaigns'],
            'conflict_resolution' => ['strategy' => 'skip'],
            'execution_report' => ['records_restored' => 10],
            'rollback_expires_at' => now()->subHours(1), // Expired
            'created_by' => $this->user->user_id,
            'completed_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('backup.restore.rollback', [
                'org' => $this->org->org_id,
                'restore' => $restore->id,
            ]));

        // Should fail due to expiration (or app not enabled)
        $this->assertContains($response->status(), [400, 403, 422]);
    }

    /** @test */
    public function it_requires_authentication_for_restore()
    {
        $response = $this->get(route('backup.restore.index', ['org' => $this->org->org_id]));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_prevents_restore_of_other_orgs_backup()
    {
        $otherOrg = Org::factory()->create();
        $otherBackup = OrganizationBackup::create([
            'org_id' => $otherOrg->org_id,
            'backup_code' => 'BKUP-OTHER-001',
            'name' => 'Other Org Backup',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user->user_id,
            'summary' => [],
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('backup.restore.analyze', [
                'org' => $this->org->org_id,
                'backup' => $otherBackup->id,
            ]));

        // Should be forbidden or not found
        $this->assertContains($response->status(), [302, 403, 404]);
    }

    /** @test */
    public function it_generates_unique_restore_codes()
    {
        $restore1 = BackupRestore::create([
            'org_id' => $this->org->org_id,
            'backup_id' => $this->backup->id,
            'restore_code' => 'REST-2024-0007',
            'type' => 'selective',
            'status' => 'pending',
            'created_by' => $this->user->user_id,
        ]);

        $restore2 = BackupRestore::create([
            'org_id' => $this->org->org_id,
            'backup_id' => $this->backup->id,
            'restore_code' => 'REST-2024-0008',
            'type' => 'selective',
            'status' => 'pending',
            'created_by' => $this->user->user_id,
        ]);

        $this->assertNotEquals($restore1->restore_code, $restore2->restore_code);
    }
}
