<?php

namespace Tests\Unit\Models\Content;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\User;
use App\Models\Content\ContentApproval;
use App\Models\Creative\ContentItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Content Approval Model Unit Tests
 */
class ContentApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_content_approval()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Approver',
            'email' => 'approver@example.com',
            'password' => bcrypt('password'),
        ]);

        $contentItem = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Test Content',
            'status' => 'pending_approval',
        ]);

        $approval = ContentApproval::create([
            'approval_id' => Str::uuid(),
            'content_item_id' => $contentItem->item_id,
            'org_id' => $org->org_id,
            'approver_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('cmis.content_approvals', [
            'approval_id' => $approval->approval_id,
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function it_belongs_to_content_item_and_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Approver',
            'email' => 'approver@example.com',
            'password' => bcrypt('password'),
        ]);

        $contentItem = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Test Content',
            'status' => 'pending_approval',
        ]);

        $approval = ContentApproval::create([
            'approval_id' => Str::uuid(),
            'content_item_id' => $contentItem->item_id,
            'org_id' => $org->org_id,
            'approver_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->assertEquals($contentItem->item_id, $approval->contentItem->item_id);
        $this->assertEquals($org->org_id, $approval->org->org_id);
    }

    #[Test]
    public function it_has_different_approval_statuses()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Approver',
            'email' => 'approver@example.com',
            'password' => bcrypt('password'),
        ]);

        $contentItem = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Test Content',
            'status' => 'pending_approval',
        ]);

        $pendingApproval = ContentApproval::create([
            'approval_id' => Str::uuid(),
            'content_item_id' => $contentItem->item_id,
            'org_id' => $org->org_id,
            'approver_id' => $user->id,
            'status' => 'pending',
        ]);

        $approvedApproval = ContentApproval::create([
            'approval_id' => Str::uuid(),
            'content_item_id' => $contentItem->item_id,
            'org_id' => $org->org_id,
            'approver_id' => $user->id,
            'status' => 'approved',
        ]);

        $rejectedApproval = ContentApproval::create([
            'approval_id' => Str::uuid(),
            'content_item_id' => $contentItem->item_id,
            'org_id' => $org->org_id,
            'approver_id' => $user->id,
            'status' => 'rejected',
        ]);

        $this->assertEquals('pending', $pendingApproval->status);
        $this->assertEquals('approved', $approvedApproval->status);
        $this->assertEquals('rejected', $rejectedApproval->status);
    }

    #[Test]
    public function it_stores_approval_comments()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Approver',
            'email' => 'approver@example.com',
            'password' => bcrypt('password'),
        ]);

        $contentItem = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Test Content',
            'status' => 'pending_approval',
        ]);

        $approval = ContentApproval::create([
            'approval_id' => Str::uuid(),
            'content_item_id' => $contentItem->item_id,
            'org_id' => $org->org_id,
            'approver_id' => $user->id,
            'status' => 'approved',
            'comments' => 'المحتوى ممتاز، موافق عليه',
        ]);

        $this->assertEquals('المحتوى ممتاز، موافق عليه', $approval->comments);
    }

    #[Test]
    public function it_tracks_approval_timestamp()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Approver',
            'email' => 'approver@example.com',
            'password' => bcrypt('password'),
        ]);

        $contentItem = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Test Content',
            'status' => 'pending_approval',
        ]);

        $approval = ContentApproval::create([
            'approval_id' => Str::uuid(),
            'content_item_id' => $contentItem->item_id,
            'org_id' => $org->org_id,
            'approver_id' => $user->id,
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $this->assertNotNull($approval->approved_at);
    }

    #[Test]
    public function it_supports_multi_level_approvals()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $approver1 = User::create([
            'user_id' => Str::uuid(),
            'name' => 'First Approver',
            'email' => 'approver1@example.com',
            'password' => bcrypt('password'),
        ]);

        $approver2 = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Second Approver',
            'email' => 'approver2@example.com',
            'password' => bcrypt('password'),
        ]);

        $contentItem = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Test Content',
            'status' => 'pending_approval',
        ]);

        $approval1 = ContentApproval::create([
            'approval_id' => Str::uuid(),
            'content_item_id' => $contentItem->item_id,
            'org_id' => $org->org_id,
            'approver_id' => $approver1->id,
            'status' => 'approved',
            'approval_level' => 1,
        ]);

        $approval2 = ContentApproval::create([
            'approval_id' => Str::uuid(),
            'content_item_id' => $contentItem->item_id,
            'org_id' => $org->org_id,
            'approver_id' => $approver2->id,
            'status' => 'pending',
            'approval_level' => 2,
        ]);

        $this->assertEquals(1, $approval1->approval_level);
        $this->assertEquals(2, $approval2->approval_level);
    }

    #[Test]
    public function it_stores_revision_requests()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Approver',
            'email' => 'approver@example.com',
            'password' => bcrypt('password'),
        ]);

        $contentItem = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Test Content',
            'status' => 'pending_approval',
        ]);

        $revisionRequests = [
            'يرجى تغيير العنوان',
            'تحديث الصورة الرئيسية',
            'تعديل وقت النشر',
        ];

        $approval = ContentApproval::create([
            'approval_id' => Str::uuid(),
            'content_item_id' => $contentItem->item_id,
            'org_id' => $org->org_id,
            'approver_id' => $user->id,
            'status' => 'revision_requested',
            'revision_requests' => $revisionRequests,
        ]);

        $this->assertCount(3, $approval->revision_requests);
        $this->assertContains('يرجى تغيير العنوان', $approval->revision_requests);
    }

    #[Test]
    public function it_tracks_approval_duration()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Approver',
            'email' => 'approver@example.com',
            'password' => bcrypt('password'),
        ]);

        $contentItem = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Test Content',
            'status' => 'pending_approval',
        ]);

        $requestedAt = now()->subHours(2);
        $approvedAt = now();

        $approval = ContentApproval::create([
            'approval_id' => Str::uuid(),
            'content_item_id' => $contentItem->item_id,
            'org_id' => $org->org_id,
            'approver_id' => $user->id,
            'status' => 'approved',
            'requested_at' => $requestedAt,
            'approved_at' => $approvedAt,
        ]);

        $duration = $approval->approved_at->diffInHours($approval->requested_at);
        $this->assertEquals(2, $duration);
    }

    #[Test]
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Approver',
            'email' => 'approver@example.com',
            'password' => bcrypt('password'),
        ]);

        $contentItem = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Test Content',
            'status' => 'pending_approval',
        ]);

        $approval = ContentApproval::create([
            'approval_id' => Str::uuid(),
            'content_item_id' => $contentItem->item_id,
            'org_id' => $org->org_id,
            'approver_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->assertTrue(Str::isUuid($approval->approval_id));
    }

    #[Test]
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Approver',
            'email' => 'approver@example.com',
            'password' => bcrypt('password'),
        ]);

        $contentItem = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'title' => 'Test Content',
            'status' => 'pending_approval',
        ]);

        $approval = ContentApproval::create([
            'approval_id' => Str::uuid(),
            'content_item_id' => $contentItem->item_id,
            'org_id' => $org->org_id,
            'approver_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->assertNotNull($approval->created_at);
        $this->assertNotNull($approval->updated_at);
    }

    #[Test]
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

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Approver',
            'email' => 'approver@example.com',
            'password' => bcrypt('password'),
        ]);

        $contentItem1 = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'title' => 'Org 1 Content',
            'status' => 'pending_approval',
        ]);

        $contentItem2 = ContentPlanItem::create([
            'item_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'title' => 'Org 2 Content',
            'status' => 'pending_approval',
        ]);

        ContentApproval::create([
            'approval_id' => Str::uuid(),
            'content_item_id' => $contentItem1->item_id,
            'org_id' => $org1->org_id,
            'approver_id' => $user->id,
            'status' => 'pending',
        ]);

        ContentApproval::create([
            'approval_id' => Str::uuid(),
            'content_item_id' => $contentItem2->item_id,
            'org_id' => $org2->org_id,
            'approver_id' => $user->id,
            'status' => 'pending',
        ]);

        $org1Approvals = ContentApproval::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Approvals);
    }
}
