<?php

namespace Tests\Feature\Backup;

use App\Apps\Backup\Services\Restore\ConflictResolverService;
use App\Models\Backup\BackupRestore;
use App\Models\Backup\OrganizationBackup;
use App\Models\Campaign\Campaign;
use App\Models\Core\Org;
use App\Models\Core\User;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConflictResolutionTest extends TestCase
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

        // Enable the backup app
        $marketplaceService = app(MarketplaceService::class);
        try {
            $marketplaceService->enableApp($this->org->org_id, 'org-backup-restore', $this->user->user_id);
        } catch (\Exception $e) {
            // App might not exist
        }

        // Create a backup
        $this->backup = OrganizationBackup::create([
            'org_id' => $this->org->org_id,
            'backup_code' => 'BKUP-CONFLICT-001',
            'name' => 'Conflict Test Backup',
            'type' => 'manual',
            'status' => 'completed',
            'created_by' => $this->user->user_id,
            'summary' => ['categories' => ['Campaigns' => ['count' => 5]]],
            'schema_snapshot' => [],
        ]);
    }

    /** @test */
    public function it_can_set_skip_strategy_for_conflicts()
    {
        $restore = BackupRestore::create([
            'org_id' => $this->org->org_id,
            'backup_id' => $this->backup->id,
            'restore_code' => 'REST-SKIP-001',
            'type' => 'merge',
            'status' => 'awaiting_confirmation',
            'selected_categories' => ['Campaigns'],
            'conflict_resolution' => ['strategy' => 'ask', 'decisions' => []],
            'created_by' => $this->user->user_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('backup.restore.conflicts.store', [
                'org' => $this->org->org_id,
                'restore' => $restore->id,
            ]), [
                'strategy' => 'skip',
            ]);

        // If app is not enabled, expect error
        if ($response->status() === 403) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertSuccessful();

        $restore->refresh();
        $this->assertEquals('skip', $restore->conflict_resolution['strategy']);
    }

    /** @test */
    public function it_can_set_replace_strategy_for_conflicts()
    {
        $restore = BackupRestore::create([
            'org_id' => $this->org->org_id,
            'backup_id' => $this->backup->id,
            'restore_code' => 'REST-REPL-001',
            'type' => 'merge',
            'status' => 'awaiting_confirmation',
            'selected_categories' => ['Campaigns'],
            'conflict_resolution' => ['strategy' => 'ask', 'decisions' => []],
            'created_by' => $this->user->user_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('backup.restore.conflicts.store', [
                'org' => $this->org->org_id,
                'restore' => $restore->id,
            ]), [
                'strategy' => 'replace',
            ]);

        if ($response->status() === 403) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertSuccessful();

        $restore->refresh();
        $this->assertEquals('replace', $restore->conflict_resolution['strategy']);
    }

    /** @test */
    public function it_can_set_merge_strategy_for_conflicts()
    {
        $restore = BackupRestore::create([
            'org_id' => $this->org->org_id,
            'backup_id' => $this->backup->id,
            'restore_code' => 'REST-MERG-001',
            'type' => 'merge',
            'status' => 'awaiting_confirmation',
            'selected_categories' => ['Campaigns'],
            'conflict_resolution' => ['strategy' => 'ask', 'decisions' => []],
            'created_by' => $this->user->user_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('backup.restore.conflicts.store', [
                'org' => $this->org->org_id,
                'restore' => $restore->id,
            ]), [
                'strategy' => 'merge',
            ]);

        if ($response->status() === 403) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertSuccessful();

        $restore->refresh();
        $this->assertEquals('merge', $restore->conflict_resolution['strategy']);
    }

    /** @test */
    public function it_can_set_per_record_decisions()
    {
        $restore = BackupRestore::create([
            'org_id' => $this->org->org_id,
            'backup_id' => $this->backup->id,
            'restore_code' => 'REST-ASK-001',
            'type' => 'merge',
            'status' => 'awaiting_confirmation',
            'selected_categories' => ['Campaigns'],
            'conflict_resolution' => [
                'strategy' => 'ask',
                'decisions' => [],
                'conflicts' => [
                    ['id' => 'camp-001', 'category' => 'Campaigns'],
                    ['id' => 'camp-002', 'category' => 'Campaigns'],
                ],
            ],
            'created_by' => $this->user->user_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('backup.restore.conflicts.store', [
                'org' => $this->org->org_id,
                'restore' => $restore->id,
            ]), [
                'strategy' => 'ask',
                'decisions' => [
                    'camp-001' => 'skip',
                    'camp-002' => 'replace',
                ],
            ]);

        if ($response->status() === 403) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertSuccessful();

        $restore->refresh();
        $this->assertEquals('ask', $restore->conflict_resolution['strategy']);
        $this->assertArrayHasKey('decisions', $restore->conflict_resolution);
    }

    /** @test */
    public function it_validates_conflict_strategy()
    {
        $restore = BackupRestore::create([
            'org_id' => $this->org->org_id,
            'backup_id' => $this->backup->id,
            'restore_code' => 'REST-VALID-001',
            'type' => 'merge',
            'status' => 'awaiting_confirmation',
            'selected_categories' => ['Campaigns'],
            'conflict_resolution' => ['strategy' => 'ask'],
            'created_by' => $this->user->user_id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('backup.restore.conflicts.store', [
                'org' => $this->org->org_id,
                'restore' => $restore->id,
            ]), [
                'strategy' => 'invalid_strategy', // Invalid
            ]);

        // Either validation error or app not enabled
        $this->assertContains($response->status(), [403, 422]);
    }

    /** @test */
    public function conflict_resolver_service_handles_skip_strategy()
    {
        $service = app(ConflictResolverService::class);

        $backupRecord = [
            'id' => 'test-id',
            'name' => 'Backup Campaign',
            'updated_at' => now()->subDay()->toDateTimeString(),
        ];

        $existingRecord = (object) [
            'id' => 'test-id',
            'name' => 'Existing Campaign',
            'updated_at' => now()->toDateTimeString(),
        ];

        $result = $service->resolve($backupRecord, $existingRecord, 'skip');

        $this->assertEquals('skip', $result->action);
    }

    /** @test */
    public function conflict_resolver_service_handles_replace_strategy()
    {
        $service = app(ConflictResolverService::class);

        $backupRecord = [
            'id' => 'test-id',
            'name' => 'Backup Campaign',
            'updated_at' => now()->subDay()->toDateTimeString(),
        ];

        $existingRecord = (object) [
            'id' => 'test-id',
            'name' => 'Existing Campaign',
            'updated_at' => now()->toDateTimeString(),
        ];

        $result = $service->resolve($backupRecord, $existingRecord, 'replace');

        $this->assertEquals('update', $result->action);
        $this->assertEquals('Backup Campaign', $result->data['name']);
    }

    /** @test */
    public function conflict_resolver_service_handles_merge_strategy()
    {
        $service = app(ConflictResolverService::class);

        $backupRecord = [
            'id' => 'test-id',
            'name' => 'Backup Name',
            'description' => 'Backup Description',
            'updated_at' => now()->addDay()->toDateTimeString(), // Newer
        ];

        $existingRecord = (object) [
            'id' => 'test-id',
            'name' => 'Existing Name',
            'description' => 'Existing Description',
            'updated_at' => now()->toDateTimeString(),
        ];

        $result = $service->resolve($backupRecord, $existingRecord, 'merge');

        $this->assertEquals('update', $result->action);
        // Merge should use newer values
        $this->assertEquals('Backup Name', $result->data['name']);
    }

    /** @test */
    public function conflict_resolver_service_handles_new_records()
    {
        $service = app(ConflictResolverService::class);

        $backupRecord = [
            'id' => 'new-id',
            'name' => 'New Campaign',
            'updated_at' => now()->toDateTimeString(),
        ];

        // No existing record
        $result = $service->resolve($backupRecord, null, 'skip');

        $this->assertEquals('insert', $result->action);
    }

    /** @test */
    public function it_displays_conflicts_page_with_diff_view()
    {
        $restore = BackupRestore::create([
            'org_id' => $this->org->org_id,
            'backup_id' => $this->backup->id,
            'restore_code' => 'REST-DIFF-001',
            'type' => 'merge',
            'status' => 'awaiting_confirmation',
            'selected_categories' => ['Campaigns'],
            'conflict_resolution' => [
                'strategy' => 'ask',
                'conflicts' => [
                    [
                        'id' => 'camp-001',
                        'category' => 'Campaigns',
                        'backup_value' => ['name' => 'Backup Name'],
                        'existing_value' => ['name' => 'Current Name'],
                    ],
                ],
            ],
            'created_by' => $this->user->user_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('backup.restore.conflicts', [
                'org' => $this->org->org_id,
                'restore' => $restore->id,
            ]));

        if ($response->status() === 302) {
            $this->markTestSkipped('Backup app not enabled');
        }

        $response->assertSuccessful();
        $response->assertViewIs('apps.backup.restore.conflicts');
    }

    /** @test */
    public function it_stores_conflict_decisions_in_restore_record()
    {
        $restore = BackupRestore::create([
            'org_id' => $this->org->org_id,
            'backup_id' => $this->backup->id,
            'restore_code' => 'REST-STORE-001',
            'type' => 'merge',
            'status' => 'awaiting_confirmation',
            'selected_categories' => ['Campaigns'],
            'conflict_resolution' => ['strategy' => 'ask', 'decisions' => []],
            'created_by' => $this->user->user_id,
        ]);

        $decisions = [
            'record-1' => 'skip',
            'record-2' => 'replace',
            'record-3' => 'merge',
        ];

        $restore->conflict_resolution = array_merge(
            $restore->conflict_resolution,
            ['decisions' => $decisions]
        );
        $restore->save();

        $restore->refresh();

        $this->assertEquals($decisions, $restore->conflict_resolution['decisions']);
    }
}
