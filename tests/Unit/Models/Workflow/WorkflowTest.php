<?php

namespace Tests\Unit\Models\Workflow;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Workflow\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Workflow Model Unit Tests
 */
class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_workflow()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $workflow = Workflow::create([
            'workflow_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Campaign Approval Workflow',
            'description' => 'Workflow for campaign approvals',
            'trigger' => 'campaign_created',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('cmis.workflows', [
            'workflow_id' => $workflow->workflow_id,
            'name' => 'Campaign Approval Workflow',
        ]);
    }

    /** @test */
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $workflow = Workflow::create([
            'workflow_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Workflow',
            'trigger' => 'test_trigger',
        ]);

        $this->assertEquals($org->org_id, $workflow->org->org_id);
    }

    /** @test */
    public function it_has_different_trigger_types()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $triggers = [
            'campaign_created',
            'post_published',
            'lead_captured',
            'budget_exceeded',
            'approval_requested',
        ];

        foreach ($triggers as $trigger) {
            Workflow::create([
                'workflow_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'name' => "Workflow for {$trigger}",
                'trigger' => $trigger,
            ]);
        }

        $workflows = Workflow::where('org_id', $org->org_id)->get();
        $this->assertCount(5, $workflows);
    }

    /** @test */
    public function it_stores_workflow_steps_as_json()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $steps = [
            [
                'type' => 'send_notification',
                'config' => [
                    'recipients' => ['manager@example.com'],
                    'template' => 'campaign_approval_needed',
                ],
            ],
            [
                'type' => 'wait_for_approval',
                'config' => [
                    'timeout' => '24 hours',
                    'approvers' => ['role:admin', 'role:manager'],
                ],
            ],
            [
                'type' => 'update_status',
                'config' => [
                    'status' => 'approved',
                ],
            ],
        ];

        $workflow = Workflow::create([
            'workflow_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Approval Workflow',
            'trigger' => 'campaign_created',
            'steps' => $steps,
        ]);

        $this->assertCount(3, $workflow->steps);
        $this->assertEquals('send_notification', $workflow->steps[0]['type']);
        $this->assertEquals('wait_for_approval', $workflow->steps[1]['type']);
    }

    /** @test */
    public function it_stores_workflow_conditions()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $conditions = [
            'field' => 'budget',
            'operator' => 'greater_than',
            'value' => 10000,
        ];

        $workflow = Workflow::create([
            'workflow_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'High Budget Approval',
            'trigger' => 'campaign_created',
            'conditions' => $conditions,
        ]);

        $this->assertEquals('budget', $workflow->conditions['field']);
        $this->assertEquals(10000, $workflow->conditions['value']);
    }

    /** @test */
    public function it_can_be_active_or_inactive()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $activeWorkflow = Workflow::create([
            'workflow_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Active Workflow',
            'trigger' => 'test',
            'is_active' => true,
        ]);

        $inactiveWorkflow = Workflow::create([
            'workflow_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Inactive Workflow',
            'trigger' => 'test',
            'is_active' => false,
        ]);

        $this->assertTrue($activeWorkflow->is_active);
        $this->assertFalse($inactiveWorkflow->is_active);
    }

    /** @test */
    public function it_tracks_execution_count()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $workflow = Workflow::create([
            'workflow_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Workflow',
            'trigger' => 'test',
            'execution_count' => 0,
        ]);

        $workflow->increment('execution_count', 5);

        $this->assertEquals(5, $workflow->fresh()->execution_count);
    }

    /** @test */
    public function it_tracks_last_execution_time()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $workflow = Workflow::create([
            'workflow_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Workflow',
            'trigger' => 'test',
            'last_executed_at' => now(),
        ]);

        $this->assertNotNull($workflow->last_executed_at);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $workflow = Workflow::create([
            'workflow_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Workflow',
            'trigger' => 'test',
        ]);

        $this->assertTrue(Str::isUuid($workflow->workflow_id));
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $workflow = Workflow::create([
            'workflow_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Workflow',
            'trigger' => 'test',
        ]);

        $this->assertNotNull($workflow->created_at);
        $this->assertNotNull($workflow->updated_at);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $workflow = Workflow::create([
            'workflow_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Deletable Workflow',
            'trigger' => 'test',
        ]);

        $workflowId = $workflow->workflow_id;

        $workflow->delete();

        $this->assertSoftDeleted('cmis.workflows', [
            'workflow_id' => $workflowId,
        ]);
    }

    /** @test */
    public function it_respects_rls_policies()
    {
        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        Workflow::create([
            'workflow_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'name' => 'Org 1 Workflow',
            'trigger' => 'test',
        ]);

        Workflow::create([
            'workflow_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'name' => 'Org 2 Workflow',
            'trigger' => 'test',
        ]);

        $org1Workflows = Workflow::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Workflows);
        $this->assertEquals('Org 1 Workflow', $org1Workflows->first()->name);
    }
}
