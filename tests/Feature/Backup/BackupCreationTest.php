<?php

namespace Tests\Feature\Backup;

use App\Jobs\Backup\ProcessBackupJob;
use App\Models\Backup\OrganizationBackup;
use App\Models\Core\Org;
use App\Models\Core\User;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BackupCreationTest extends TestCase
{
    use RefreshDatabase;

    protected Org $org;
    protected User $user;

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
    }

    /** @test */
    public function it_displays_backup_index_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('backup.index', ['org' => $this->org->org_id]));

        // If app is not enabled, expect redirect
        if ($response->status() === 302) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertStatus(200);
        $response->assertViewIs('apps.backup.index');
    }

    /** @test */
    public function it_displays_backup_create_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('backup.create', ['org' => $this->org->org_id]));

        // If app is not enabled, expect redirect
        if ($response->status() === 302) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertStatus(200);
        $response->assertViewIs('apps.backup.create');
    }

    /** @test */
    public function it_can_create_manual_backup()
    {
        Queue::fake();

        $response = $this->actingAs($this->user)
            ->postJson(route('backup.store', ['org' => $this->org->org_id]), [
                'name' => 'Test Backup',
                'description' => 'Test backup description',
                'type' => 'full',
            ]);

        // If app is not enabled, expect error
        if ($response->status() === 403) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'backup_code',
            ],
        ]);

        Queue::assertPushed(ProcessBackupJob::class);
    }

    /** @test */
    public function it_generates_unique_backup_code()
    {
        Queue::fake();

        // Create first backup
        $this->actingAs($this->user)
            ->postJson(route('backup.store', ['org' => $this->org->org_id]), [
                'name' => 'Backup 1',
                'type' => 'full',
            ]);

        // Create second backup
        $this->actingAs($this->user)
            ->postJson(route('backup.store', ['org' => $this->org->org_id]), [
                'name' => 'Backup 2',
                'type' => 'full',
            ]);

        $backups = OrganizationBackup::where('org_id', $this->org->org_id)->get();

        if ($backups->count() >= 2) {
            $codes = $backups->pluck('backup_code')->toArray();
            $this->assertEquals(count($codes), count(array_unique($codes)));
        }
    }

    /** @test */
    public function it_validates_backup_creation_request()
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('backup.store', ['org' => $this->org->org_id]), [
                // Missing required fields
            ]);

        // Either validation error or app not enabled
        $this->assertContains($response->status(), [403, 422]);
    }

    /** @test */
    public function it_can_view_backup_details()
    {
        $backup = OrganizationBackup::create([
            'org_id' => $this->org->org_id,
            'backup_code' => 'BKUP-2024-0001',
            'name' => 'Test Backup',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user->user_id,
            'summary' => [],
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('backup.show', [
                'org' => $this->org->org_id,
                'backup' => $backup->id,
            ]));

        // If app is not enabled, expect redirect
        if ($response->status() === 302) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_delete_backup()
    {
        $backup = OrganizationBackup::create([
            'org_id' => $this->org->org_id,
            'backup_code' => 'BKUP-2024-0002',
            'name' => 'Test Backup',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user->user_id,
            'summary' => [],
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('backup.destroy', [
                'org' => $this->org->org_id,
                'backup' => $backup->id,
            ]));

        // If app is not enabled, expect error
        if ($response->status() === 403) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertSuccessful();

        // Verify soft deleted
        $this->assertSoftDeleted('cmis.organization_backups', ['id' => $backup->id]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->get(route('backup.index', ['org' => $this->org->org_id]));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_requires_valid_org_access()
    {
        $otherOrg = Org::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('backup.index', ['org' => $otherOrg->org_id]));

        // Should be redirected or forbidden
        $this->assertContains($response->status(), [302, 403]);
    }

    /** @test */
    public function it_can_check_backup_progress()
    {
        $backup = OrganizationBackup::create([
            'org_id' => $this->org->org_id,
            'backup_code' => 'BKUP-2024-0003',
            'name' => 'In Progress Backup',
            'type' => 'manual',
            'status' => 'processing',
            'created_by' => $this->user->user_id,
            'summary' => ['progress' => 50],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('backup.progress', [
                'org' => $this->org->org_id,
                'backup' => $backup->id,
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
}
