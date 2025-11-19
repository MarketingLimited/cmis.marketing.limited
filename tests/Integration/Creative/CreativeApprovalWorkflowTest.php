<?php

namespace Tests\Integration\Creative;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\ApprovalWorkflowService;
use App\Services\CreativeService;

/**
 * Complete Creative Approval Workflow Test
 */
class CreativeApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected ApprovalWorkflowService $approvalService;
    protected CreativeService $creativeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->approvalService = app(ApprovalWorkflowService::class);
        $this->creativeService = app(CreativeService::class);
    }

    /** @test */
    public function it_completes_creative_brief_to_approval_workflow()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        // Step 1: Create Creative Brief
        $brief = $this->createTestCreativeBrief($org->org_id, [
            'name' => 'Summer Campaign Brief',
            'brief_data' => [
                'objective' => 'brand_awareness',
                'target_audience' => 'Young professionals 25-40',
                'key_message' => 'Stay cool this summer',
                'tone' => 'friendly',
                'channels' => ['facebook', 'instagram'],
            ],
        ]);

        $this->assertDatabaseHas('cmis.creative_briefs', [
            'brief_id' => $brief->brief_id,
            'org_id' => $org->org_id,
        ]);

        // Step 2: Generate Creative Asset from Brief
        $asset = $this->createTestCreativeAsset($org->org_id, null, [
            'brief_id' => $brief->brief_id,
            'channel_id' => 1,
            'status' => 'draft',
            'final_copy' => [
                'headline' => 'Beat the Heat!',
                'body' => 'Stay cool with our summer collection',
                'cta' => 'Shop Now',
            ],
        ]);

        $this->assertDatabaseHas('cmis.creative_assets', [
            'asset_id' => $asset->asset_id,
            'brief_id' => $brief->brief_id,
            'status' => 'draft',
        ]);

        // Step 3: Submit for Approval
        $approval = $this->approvalService->requestApproval(
            $asset->asset_id,
            'creative_asset',
            $user->user_id
        );

        $this->assertNotNull($approval);
        $this->assertEquals('pending', $approval->status);

        // Step 4: Approve Creative
        $approvedResult = $this->approvalService->approve(
            $approval->id,
            $user->user_id,
            'Looks great!'
        );

        $this->assertTrue($approvedResult);

        // Verify asset status updated
        $asset = $asset->fresh();
        $this->assertEquals('approved', $asset->status);

        $this->logTestResult('passed', [
            'workflow' => 'creative_approval',
            'steps_completed' => 4,
        ]);
    }

    /** @test */
    public function it_handles_creative_rejection_and_revision()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        // Create asset
        $asset = $this->createTestCreativeAsset($org->org_id);

        // Request approval
        $approval = $this->approvalService->requestApproval(
            $asset->asset_id,
            'creative_asset',
            $user->user_id
        );

        // Reject with feedback
        $rejectedResult = $this->approvalService->reject(
            $approval->id,
            $user->user_id,
            'Please update the headline to be more engaging'
        );

        $this->assertTrue($rejectedResult);

        // Verify asset status
        $asset = $asset->fresh();
        $this->assertEquals('rejected', $asset->status);

        // Revise and resubmit
        $asset->update([
            'final_copy' => [
                'headline' => 'Amazing Summer Deals!',
                'body' => 'Updated body text',
            ],
            'status' => 'draft',
        ]);

        $newApproval = $this->approvalService->requestApproval(
            $asset->asset_id,
            'creative_asset',
            $user->user_id
        );

        $this->assertEquals('pending', $newApproval->status);

        $this->logTestResult('passed', [
            'workflow' => 'creative_approval',
            'step' => 'rejection_and_revision',
        ]);
    }

    /** @test */
    public function it_supports_multi_level_approval()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        // Create additional approvers
        $approver1 = \App\Models\User::create([
            'user_id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Approver 1',
            'email' => 'approver1@example.com',
            'password' => bcrypt('password'),
        ]);

        $approver2 = \App\Models\User::create([
            'user_id' => \Illuminate\Support\Str::uuid(),
            'name' => 'Approver 2',
            'email' => 'approver2@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAsUserInOrg($user, $org);

        $asset = $this->createTestCreativeAsset($org->org_id);

        // Level 1 approval
        $approval1 = $this->approvalService->requestApproval(
            $asset->asset_id,
            'creative_asset',
            $approver1->user_id
        );

        $this->approvalService->approve($approval1->id, $approver1->user_id);

        // Level 2 approval
        $approval2 = $this->approvalService->requestApproval(
            $asset->asset_id,
            'creative_asset',
            $approver2->user_id
        );

        $this->approvalService->approve($approval2->id, $approver2->user_id);

        $asset = $asset->fresh();
        $this->assertEquals('approved', $asset->status);

        $this->logTestResult('passed', [
            'workflow' => 'creative_approval',
            'step' => 'multi_level_approval',
        ]);
    }

    /** @test */
    public function it_tracks_approval_history()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $asset = $this->createTestCreativeAsset($org->org_id);

        // Multiple approval cycles
        for ($i = 1; $i <= 3; $i++) {
            $approval = $this->approvalService->requestApproval(
                $asset->asset_id,
                'creative_asset',
                $user->user_id
            );

            if ($i < 3) {
                $this->approvalService->reject(
                    $approval->id,
                    $user->user_id,
                    "Revision {$i} needed"
                );
            } else {
                $this->approvalService->approve(
                    $approval->id,
                    $user->user_id
                );
            }
        }

        $history = $this->approvalService->getApprovalHistory(
            $asset->asset_id,
            'creative_asset'
        );

        $this->assertCount(3, $history);

        $this->logTestResult('passed', [
            'workflow' => 'creative_approval',
            'step' => 'approval_history_tracking',
        ]);
    }
}
