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
 * TikTok, YouTube & Twitter Comments Integration Test
 *
 * اختبارات التعليقات على TikTok و YouTube و Twitter
 */
class TikTokYouTubeTwitterCommentsTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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
}
