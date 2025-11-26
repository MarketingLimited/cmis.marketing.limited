<?php

namespace Tests\Unit\Models\Comment;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\User;
use App\Models\Core\Campaign;
use App\Models\Comment\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Comment Model Unit Tests
 */
class CommentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_create_comment()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $comment = Comment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'body' => 'تعليق تجريبي على الحملة',
            'commentable_type' => 'Campaign',
            'commentable_id' => Str::uuid(),
        ]);

        $this->assertDatabaseHas('cmis.comments', [
            'comment_id' => $comment->comment_id,
            'body' => 'تعليق تجريبي على الحملة',
        ]);
    }

    #[Test]
    public function it_belongs_to_org()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $comment = Comment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'body' => 'Test Comment',
            'commentable_type' => 'Campaign',
            'commentable_id' => Str::uuid(),
        ]);

        $this->assertEquals($org->org_id, $comment->org->org_id);
    }

    #[Test]
    public function it_belongs_to_user()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'password' => bcrypt('password'),
        ]);

        $comment = Comment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'body' => 'تعليق من أحمد',
            'commentable_type' => 'Campaign',
            'commentable_id' => Str::uuid(),
        ]);

        $this->assertEquals($user->user_id, $comment->user->user_id);
    }

    #[Test]
    public function it_is_polymorphic()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $campaign = Campaign::create([
            'campaign_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'name' => 'Test Campaign',
            'status' => 'active',
        ]);

        $comment = Comment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'body' => 'Comment on campaign',
            'commentable_type' => 'Campaign',
            'commentable_id' => $campaign->campaign_id,
        ]);

        $this->assertEquals('Campaign', $comment->commentable_type);
        $this->assertEquals($campaign->campaign_id, $comment->commentable_id);
    }

    #[Test]
    public function it_can_have_parent_comment()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $parentComment = Comment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'body' => 'Parent comment',
            'commentable_type' => 'Campaign',
            'commentable_id' => Str::uuid(),
        ]);

        $replyComment = Comment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'body' => 'Reply to parent',
            'commentable_type' => 'Campaign',
            'commentable_id' => Str::uuid(),
            'parent_id' => $parentComment->comment_id,
        ]);

        $this->assertEquals($parentComment->comment_id, $replyComment->parent_id);
    }

    #[Test]
    public function it_can_be_edited()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $comment = Comment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'body' => 'Original comment',
            'commentable_type' => 'Campaign',
            'commentable_id' => Str::uuid(),
        ]);

        $comment->update([
            'body' => 'Edited comment',
            'is_edited' => true,
        ]);

        $this->assertEquals('Edited comment', $comment->fresh()->body);
        $this->assertTrue($comment->fresh()->is_edited);
    }

    #[Test]
    public function it_tracks_mentions()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $mentions = [
            Str::uuid(),
            Str::uuid(),
        ];

        $comment = Comment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'body' => 'Comment with mentions @user1 @user2',
            'commentable_type' => 'Campaign',
            'commentable_id' => Str::uuid(),
            'mentions' => $mentions,
        ]);

        $this->assertCount(2, $comment->mentions);
    }

    #[Test]
    public function it_can_be_marked_as_resolved()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $comment = Comment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'body' => 'Issue to resolve',
            'commentable_type' => 'Campaign',
            'commentable_id' => Str::uuid(),
            'is_resolved' => false,
        ]);

        $comment->update(['is_resolved' => true]);

        $this->assertTrue($comment->fresh()->is_resolved);
    }

    #[Test]
    public function it_supports_soft_deletes()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $comment = Comment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'body' => 'Comment to delete',
            'commentable_type' => 'Campaign',
            'commentable_id' => Str::uuid(),
        ]);

        $comment->delete();

        $this->assertSoftDeleted('cmis.comments', [
            'comment_id' => $comment->comment_id,
        ]);
    }

    #[Test]
    public function it_stores_attachments()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $user = User::create([
            'user_id' => Str::uuid(),
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $attachments = [
            'file1.pdf',
            'image.jpg',
        ];

        $comment = Comment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'body' => 'Comment with attachments',
            'commentable_type' => 'Campaign',
            'commentable_id' => Str::uuid(),
            'attachments' => $attachments,
        ]);

        $this->assertCount(2, $comment->attachments);
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
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $comment = Comment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'body' => 'UUID Comment',
            'commentable_type' => 'Campaign',
            'commentable_id' => Str::uuid(),
        ]);

        $this->assertTrue(Str::isUuid($comment->comment_id));
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
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $comment = Comment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'user_id' => $user->user_id,
            'body' => 'Timestamped Comment',
            'commentable_type' => 'Campaign',
            'commentable_id' => Str::uuid(),
        ]);

        $this->assertNotNull($comment->created_at);
        $this->assertNotNull($comment->updated_at);
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
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        Comment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'user_id' => $user->user_id,
            'body' => 'Org 1 Comment',
            'commentable_type' => 'Campaign',
            'commentable_id' => Str::uuid(),
        ]);

        Comment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'user_id' => $user->user_id,
            'body' => 'Org 2 Comment',
            'commentable_type' => 'Campaign',
            'commentable_id' => Str::uuid(),
        ]);

        $org1Comments = Comment::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Comments);
        $this->assertEquals('Org 1 Comment', $org1Comments->first()->body);
    }
}
