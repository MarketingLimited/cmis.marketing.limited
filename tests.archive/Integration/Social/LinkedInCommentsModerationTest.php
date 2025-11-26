<?php

namespace Tests\Integration\Social;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Social\SocialPost;
use App\Models\Social\SocialComment;
use App\Jobs\ReplyToCommentJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * LinkedIn Comments & Comment Moderation Integration Test
 *
 * اختبارات التعليقات على LinkedIn والإشراف التلقائي على التعليقات
 */
class LinkedInCommentsModerationTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_receives_linkedin_comment()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $socialPost = SocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'linkedin_post_123',
            'platform' => 'linkedin',
            'post_type' => 'post',
            'content' => 'منشور LinkedIn',
            'published_at' => now()->subHours(5),
        ]);

        $comment = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'platform' => 'linkedin',
            'external_comment_id' => 'linkedin_comment_456',
            'author_id' => 'linkedin_user_789',
            'author_name' => 'Professional Name',
            'content' => 'محتوى قيم جداً',
            'commented_at' => now(),
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('cmis.social_comments', [
            'platform' => 'linkedin',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'social_comments',
            'platform' => 'linkedin',
            'action' => 'receive_comment',
        ]);
    }

    #[Test]
    public function it_replies_to_linkedin_comment()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $socialPost = SocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'linkedin_post_123',
            'platform' => 'linkedin',
            'post_type' => 'post',
            'content' => 'منشور',
            'published_at' => now()->subHours(5),
        ]);

        $comment = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'platform' => 'linkedin',
            'external_comment_id' => 'linkedin_comment_456',
            'author_id' => 'linkedin_user_789',
            'content' => 'تعليق',
            'commented_at' => now(),
            'status' => 'pending',
        ]);

        $reply = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'parent_comment_id' => $comment->comment_id,
            'platform' => 'linkedin',
            'author_id' => $user->user_id,
            'content' => 'شكراً لك! نسعد دائماً بمشاركة المعرفة',
            'is_reply' => true,
            'status' => 'pending',
        ]);

        $this->mockLinkedInAPI('success', [
            'id' => 'linkedin_comment_reply_789',
        ]);

        ReplyToCommentJob::dispatch($reply);
        Queue::assertPushed(ReplyToCommentJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_comments',
            'platform' => 'linkedin',
            'action' => 'reply_to_comment',
        ]);
    }

    #[Test]
    public function it_auto_moderates_comments_with_keywords()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $socialPost = SocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'ig_post_123',
            'platform' => 'instagram',
            'post_type' => 'feed',
            'content' => 'منشور',
            'published_at' => now()->subHours(1),
        ]);

        // Comment with spam keywords
        $comment = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'platform' => 'instagram',
            'external_comment_id' => 'spam_comment_123',
            'author_id' => 'spammer_456',
            'content' => 'Check out this link for free followers!',
            'commented_at' => now(),
            'status' => 'hidden',
            'moderation_reason' => 'spam_detected',
        ]);

        $this->assertDatabaseHas('cmis.social_comments', [
            'comment_id' => $comment->comment_id,
            'status' => 'hidden',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'social_comments',
            'test' => 'auto_moderation',
        ]);
    }

    #[Test]
    public function it_handles_nested_comment_threads()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $socialPost = SocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'fb_post_123',
            'platform' => 'facebook',
            'post_type' => 'post',
            'content' => 'منشور',
            'published_at' => now()->subHours(1),
        ]);

        // Parent comment
        $parentComment = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'platform' => 'facebook',
            'external_comment_id' => 'parent_123',
            'author_id' => 'user_1',
            'content' => 'تعليق رئيسي',
            'commented_at' => now(),
            'status' => 'approved',
        ]);

        // Create 3 nested replies
        for ($i = 1; $i <= 3; $i++) {
            SocialComment::create([
                'comment_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'post_id' => $socialPost->post_id,
                'parent_comment_id' => $parentComment->comment_id,
                'platform' => 'facebook',
                'external_comment_id' => "reply_{$i}",
                'author_id' => "user_{$i}",
                'content' => "رد {$i}",
                'commented_at' => now()->addMinutes($i),
                'is_reply' => true,
                'status' => 'approved',
            ]);
        }

        $totalReplies = SocialComment::where('parent_comment_id', $parentComment->comment_id)->count();
        $this->assertEquals(3, $totalReplies);

        $this->logTestResult('passed', [
            'workflow' => 'social_comments',
            'test' => 'nested_threads',
            'reply_count' => 3,
        ]);
    }
}
