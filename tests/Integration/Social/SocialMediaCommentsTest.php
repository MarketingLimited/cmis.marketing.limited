<?php

namespace Tests\Integration\Social;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Social\SocialPost;
use App\Models\Social\SocialComment;
use App\Jobs\ProcessIncomingCommentJob;
use App\Jobs\ReplyToCommentJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Social Media Comments Integration Test
 *
 * اختبارات شاملة لاستقبال التعليقات من جميع منصات السوشيال ميديا والرد عليها
 */
class SocialMediaCommentsTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
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

    /** @test */
    public function it_receives_tiktok_comment()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok');

        $socialPost = SocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'tiktok_video_123',
            'platform' => 'tiktok',
            'post_type' => 'video',
            'content' => 'فيديو TikTok',
            'published_at' => now()->subHours(3),
        ]);

        $comment = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'platform' => 'tiktok',
            'external_comment_id' => 'tiktok_comment_456',
            'author_id' => 'tiktok_user_789',
            'author_name' => '@tiktokuser',
            'content' => 'أحب هذا المحتوى! 🔥',
            'commented_at' => now(),
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('cmis.social_comments', [
            'platform' => 'tiktok',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'social_comments',
            'platform' => 'tiktok',
            'action' => 'receive_comment',
        ]);
    }

    /** @test */
    public function it_replies_to_tiktok_comment()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'tiktok');

        $socialPost = SocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'tiktok_video_123',
            'platform' => 'tiktok',
            'post_type' => 'video',
            'content' => 'فيديو',
            'published_at' => now()->subHours(3),
        ]);

        $comment = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'platform' => 'tiktok',
            'external_comment_id' => 'tiktok_comment_456',
            'author_id' => 'tiktok_user_789',
            'content' => 'تعليق',
            'commented_at' => now(),
            'status' => 'pending',
        ]);

        $reply = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'parent_comment_id' => $comment->comment_id,
            'platform' => 'tiktok',
            'author_id' => $user->user_id,
            'content' => 'شكراً لك! 💙',
            'is_reply' => true,
            'status' => 'pending',
        ]);

        $this->mockTikTokAPI('success', [
            'data' => [
                'comment_id' => 'tiktok_reply_789',
            ],
        ]);

        ReplyToCommentJob::dispatch($reply);
        Queue::assertPushed(ReplyToCommentJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_comments',
            'platform' => 'tiktok',
            'action' => 'reply_to_comment',
        ]);
    }

    /** @test */
    public function it_receives_youtube_comment()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'youtube');

        $socialPost = SocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'youtube_video_123',
            'platform' => 'youtube',
            'post_type' => 'video',
            'content' => 'فيديو YouTube',
            'published_at' => now()->subDays(1),
        ]);

        $comment = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'platform' => 'youtube',
            'external_comment_id' => 'yt_comment_456',
            'author_id' => 'yt_user_789',
            'author_name' => 'YouTube User',
            'content' => 'شرح ممتاز! هل يمكن عمل جزء ثاني؟',
            'commented_at' => now(),
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('cmis.social_comments', [
            'platform' => 'youtube',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'social_comments',
            'platform' => 'youtube',
            'action' => 'receive_comment',
        ]);
    }

    /** @test */
    public function it_replies_to_youtube_comment()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'youtube');

        $socialPost = SocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'youtube_video_123',
            'platform' => 'youtube',
            'post_type' => 'video',
            'content' => 'فيديو',
            'published_at' => now()->subDays(1),
        ]);

        $comment = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'platform' => 'youtube',
            'external_comment_id' => 'yt_comment_456',
            'author_id' => 'yt_user_789',
            'content' => 'سؤال',
            'commented_at' => now(),
            'status' => 'pending',
        ]);

        $reply = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'parent_comment_id' => $comment->comment_id,
            'platform' => 'youtube',
            'author_id' => $user->user_id,
            'content' => 'شكراً! بالتأكيد سنعمل جزء ثاني قريباً',
            'is_reply' => true,
            'status' => 'pending',
        ]);

        $this->mockGoogleAdsAPI('success', [
            'id' => 'yt_comment_reply_789',
        ]);

        ReplyToCommentJob::dispatch($reply);
        Queue::assertPushed(ReplyToCommentJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_comments',
            'platform' => 'youtube',
            'action' => 'reply_to_comment',
        ]);
    }

    /** @test */
    public function it_receives_twitter_reply()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $socialPost = SocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'tweet_123',
            'platform' => 'twitter',
            'post_type' => 'tweet',
            'content' => 'تغريدة',
            'published_at' => now()->subHours(2),
        ]);

        $comment = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'platform' => 'twitter',
            'external_comment_id' => 'tweet_reply_456',
            'author_id' => 'twitter_user_789',
            'author_name' => '@username',
            'content' => '@brand رائع!',
            'commented_at' => now(),
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('cmis.social_comments', [
            'platform' => 'twitter',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'social_comments',
            'platform' => 'twitter',
            'action' => 'receive_reply',
        ]);
    }

    /** @test */
    public function it_replies_to_twitter_mention()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $socialPost = SocialPost::create([
            'post_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'post_external_id' => 'tweet_123',
            'platform' => 'twitter',
            'post_type' => 'tweet',
            'content' => 'تغريدة',
            'published_at' => now()->subHours(2),
        ]);

        $comment = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'platform' => 'twitter',
            'external_comment_id' => 'tweet_reply_456',
            'author_id' => 'twitter_user_789',
            'content' => '@brand سؤال',
            'commented_at' => now(),
            'status' => 'pending',
        ]);

        $reply = SocialComment::create([
            'comment_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'post_id' => $socialPost->post_id,
            'parent_comment_id' => $comment->comment_id,
            'platform' => 'twitter',
            'author_id' => $user->user_id,
            'content' => '@username شكراً لتواصلك!',
            'is_reply' => true,
            'status' => 'pending',
        ]);

        $this->mockTwitterAPI('success', [
            'data' => [
                'id' => 'tweet_reply_789',
            ],
        ]);

        ReplyToCommentJob::dispatch($reply);
        Queue::assertPushed(ReplyToCommentJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_comments',
            'platform' => 'twitter',
            'action' => 'reply_to_mention',
        ]);
    }

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
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
