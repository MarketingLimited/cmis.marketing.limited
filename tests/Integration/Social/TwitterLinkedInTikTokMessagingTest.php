<?php

namespace Tests\Integration\Social;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Social\SocialMessage;
use App\Models\Social\MessageThread;
use App\Jobs\SendSocialMessageJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Twitter, LinkedIn & TikTok Messaging Integration Test
 *
 * اختبارات الرسائل المباشرة على Twitter و LinkedIn و TikTok
 */
class TwitterLinkedInTikTokMessagingTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_receives_twitter_direct_message()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $thread = MessageThread::create([
            'thread_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'twitter',
            'external_thread_id' => 'twitter_dm_123',
            'participant_id' => 'twitter_user_456',
            'participant_name' => '@username',
            'status' => 'open',
        ]);

        $message = SocialMessage::create([
            'message_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'thread_id' => $thread->thread_id,
            'platform' => 'twitter',
            'external_message_id' => 'dm_789',
            'direction' => 'incoming',
            'sender_id' => 'twitter_user_456',
            'content' => 'متى يبدأ التخفيض؟',
            'received_at' => now(),
            'status' => 'received',
        ]);

        $this->assertDatabaseHas('cmis.social_messages', [
            'platform' => 'twitter',
            'direction' => 'incoming',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'social_messaging',
            'platform' => 'twitter',
            'action' => 'receive_dm',
        ]);
    }

    /** @test */
    public function it_sends_twitter_direct_message_reply()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'twitter');

        $thread = MessageThread::create([
            'thread_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'twitter',
            'external_thread_id' => 'twitter_dm_123',
            'participant_id' => 'twitter_user_456',
            'status' => 'open',
        ]);

        $replyMessage = SocialMessage::create([
            'message_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'thread_id' => $thread->thread_id,
            'platform' => 'twitter',
            'direction' => 'outgoing',
            'sender_id' => $user->user_id,
            'recipient_id' => 'twitter_user_456',
            'content' => 'يبدأ التخفيض غداً الساعة 12 ظهراً!',
            'status' => 'pending',
        ]);

        $this->mockTwitterAPI('success', [
            'event' => [
                'id' => 'dm_reply_123',
            ],
        ]);

        SendSocialMessageJob::dispatch($replyMessage);
        Queue::assertPushed(SendSocialMessageJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_messaging',
            'platform' => 'twitter',
            'action' => 'send_reply',
        ]);
    }

    /** @test */
    public function it_receives_linkedin_message()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $thread = MessageThread::create([
            'thread_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'linkedin',
            'external_thread_id' => 'li_thread_123',
            'participant_id' => 'li_user_456',
            'participant_name' => 'Professional Name',
            'status' => 'open',
        ]);

        $message = SocialMessage::create([
            'message_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'thread_id' => $thread->thread_id,
            'platform' => 'linkedin',
            'external_message_id' => 'li_msg_789',
            'direction' => 'incoming',
            'sender_id' => 'li_user_456',
            'content' => 'أود الاستفسار عن خدماتكم الاستشارية',
            'received_at' => now(),
            'status' => 'received',
        ]);

        $this->assertDatabaseHas('cmis.social_messages', [
            'platform' => 'linkedin',
            'direction' => 'incoming',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'social_messaging',
            'platform' => 'linkedin',
            'action' => 'receive_message',
        ]);
    }

    /** @test */
    public function it_sends_linkedin_message_reply()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'linkedin');

        $thread = MessageThread::create([
            'thread_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'linkedin',
            'external_thread_id' => 'li_thread_123',
            'participant_id' => 'li_user_456',
            'status' => 'open',
        ]);

        $replyMessage = SocialMessage::create([
            'message_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'thread_id' => $thread->thread_id,
            'platform' => 'linkedin',
            'direction' => 'outgoing',
            'sender_id' => $user->user_id,
            'recipient_id' => 'li_user_456',
            'content' => 'نقدم خدمات استشارية شاملة في التسويق الرقمي. هل تريد جدولة اجتماع؟',
            'status' => 'pending',
        ]);

        $this->mockLinkedInAPI('success', [
            'id' => 'li_msg_reply_123',
        ]);

        SendSocialMessageJob::dispatch($replyMessage);
        Queue::assertPushed(SendSocialMessageJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_messaging',
            'platform' => 'linkedin',
            'action' => 'send_reply',
        ]);
    }

    /** @test */
    public function it_receives_tiktok_message()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'tiktok');

        $thread = MessageThread::create([
            'thread_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'tiktok',
            'external_thread_id' => 'tiktok_thread_123',
            'participant_id' => 'tiktok_user_456',
            'participant_name' => '@tiktokuser',
            'status' => 'open',
        ]);

        $message = SocialMessage::create([
            'message_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'thread_id' => $thread->thread_id,
            'platform' => 'tiktok',
            'external_message_id' => 'tiktok_msg_789',
            'direction' => 'incoming',
            'sender_id' => 'tiktok_user_456',
            'content' => 'أين يمكنني شراء هذا المنتج؟',
            'received_at' => now(),
            'status' => 'received',
        ]);

        $this->assertDatabaseHas('cmis.social_messages', [
            'platform' => 'tiktok',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'social_messaging',
            'platform' => 'tiktok',
            'action' => 'receive_message',
        ]);
    }
}
