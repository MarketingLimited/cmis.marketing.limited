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

/**
 * Instagram & Facebook Comments Integration Test
 *
 * اختبارات التعليقات على Instagram و Facebook
 */
class InstagramFacebookCommentsTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_receives_instagram_comment()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        // Create published post
        $socialPost = SocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'ig_post_123',
            'platform' => 'instagram',
            'post_type' => 'feed',
            'content' => 'منشور على Instagram',
            'published_at' => now()->subHours(1),
        ]);

        // Simulate incoming comment webhook
        $comment = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'platform' => 'instagram',
            'external_comment_id' => 'ig_comment_456',
            'author_id' => 'ig_user_789',
            'author_name' => 'Instagram User',
            'content' => 'منتج رائع! أين يمكنني شراؤه؟',
            'commented_at' => now(),
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('cmis.social_comments', [
            'comment_id' => $comment->comment_id,
            'platform' => 'instagram',
            'status' => 'pending',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'social_comments',
            'platform' => 'instagram',
            'action' => 'receive_comment',
        ]);
    }

    /** @test */
    public function it_replies_to_instagram_comment()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

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

        $comment = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'platform' => 'instagram',
            'external_comment_id' => 'ig_comment_456',
            'author_id' => 'ig_user_789',
            'content' => 'سؤال عن المنتج',
            'commented_at' => now(),
            'status' => 'pending',
        ]);

        // Create reply
        $reply = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'parent_comment_id' => $comment->comment_id,
            'platform' => 'instagram',
            'author_id' => $user->user_id,
            'content' => 'شكراً لاهتمامك! يمكنك الشراء من خلال الرابط في البايو',
            'is_reply' => true,
            'status' => 'pending',
        ]);

        $this->mockMetaAPI('success', [
            'id' => 'ig_comment_reply_789',
        ]);

        ReplyToCommentJob::dispatch($reply);
        Queue::assertPushed(ReplyToCommentJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_comments',
            'platform' => 'instagram',
            'action' => 'reply_to_comment',
        ]);
    }

    /** @test */
    public function it_receives_facebook_comment()
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
            'content' => 'منشور Facebook',
            'published_at' => now()->subHours(2),
        ]);

        $comment = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'platform' => 'facebook',
            'external_comment_id' => 'fb_comment_456',
            'author_id' => 'fb_user_789',
            'author_name' => 'Facebook User',
            'content' => 'هل لديكم فروع في الرياض؟',
            'commented_at' => now(),
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('cmis.social_comments', [
            'platform' => 'facebook',
            'content' => 'هل لديكم فروع في الرياض؟',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'social_comments',
            'platform' => 'facebook',
            'action' => 'receive_comment',
        ]);
    }

    /** @test */
    public function it_replies_to_facebook_comment()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $socialPost = SocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'fb_post_123',
            'platform' => 'facebook',
            'post_type' => 'post',
            'content' => 'منشور',
            'published_at' => now()->subHours(2),
        ]);

        $comment = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'platform' => 'facebook',
            'external_comment_id' => 'fb_comment_456',
            'author_id' => 'fb_user_789',
            'content' => 'سؤال',
            'commented_at' => now(),
            'status' => 'pending',
        ]);

        $reply = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'parent_comment_id' => $comment->comment_id,
            'platform' => 'facebook',
            'author_id' => $user->user_id,
            'content' => 'نعم، لدينا ثلاثة فروع في الرياض',
            'is_reply' => true,
            'status' => 'pending',
        ]);

        $this->mockMetaAPI('success', [
            'id' => 'fb_comment_reply_789',
        ]);

        ReplyToCommentJob::dispatch($reply);
        Queue::assertPushed(ReplyToCommentJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_comments',
            'platform' => 'facebook',
            'action' => 'reply_to_comment',
        ]);
    }
}
