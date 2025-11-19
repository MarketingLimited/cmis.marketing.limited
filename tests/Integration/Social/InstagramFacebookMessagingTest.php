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
 * Instagram & Facebook Messaging Integration Test
 *
 * اختبارات الرسائل المباشرة على Instagram و Facebook Messenger
 */
class InstagramFacebookMessagingTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_receives_instagram_direct_message()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        // Simulate incoming Instagram DM webhook
        $webhookData = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_account_123',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_456'],
                            'recipient' => ['id' => 'ig_account_123'],
                            'timestamp' => time() * 1000,
                            'message' => [
                                'mid' => 'msg_789',
                                'text' => 'مرحباً، أريد الاستفسار عن منتجاتكم',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Create message thread
        $thread = MessageThread::create([
            'thread_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'instagram',
            'external_thread_id' => 'user_456',
            'participant_id' => 'user_456',
            'participant_name' => 'Customer Name',
            'status' => 'open',
        ]);

        // Store incoming message
        $message = SocialMessage::create([
            'message_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'thread_id' => $thread->thread_id,
            'platform' => 'instagram',
            'external_message_id' => 'msg_789',
            'direction' => 'incoming',
            'sender_id' => 'user_456',
            'content' => 'مرحباً، أريد الاستفسار عن منتجاتكم',
            'received_at' => now(),
            'status' => 'received',
        ]);

        $this->assertDatabaseHas('cmis.social_messages', [
            'message_id' => $message->message_id,
            'platform' => 'instagram',
            'direction' => 'incoming',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'social_messaging',
            'platform' => 'instagram',
            'action' => 'receive_dm',
        ]);
    }

    /** @test */
    public function it_sends_instagram_direct_message_reply()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $thread = MessageThread::create([
            'thread_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'instagram',
            'external_thread_id' => 'user_456',
            'participant_id' => 'user_456',
            'status' => 'open',
        ]);

        // Create outgoing message (reply)
        $replyMessage = SocialMessage::create([
            'message_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'thread_id' => $thread->thread_id,
            'platform' => 'instagram',
            'direction' => 'outgoing',
            'sender_id' => $user->user_id,
            'recipient_id' => 'user_456',
            'content' => 'شكراً لتواصلك معنا! يمكنك الاطلاع على منتجاتنا من خلال الرابط...',
            'status' => 'pending',
        ]);

        $this->mockMetaAPI('success', [
            'message_id' => 'msg_reply_123',
        ]);

        SendSocialMessageJob::dispatch($replyMessage);
        Queue::assertPushed(SendSocialMessageJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_messaging',
            'platform' => 'instagram',
            'action' => 'send_reply',
        ]);
    }

    /** @test */
    public function it_receives_facebook_messenger_message()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $thread = MessageThread::create([
            'thread_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'facebook',
            'external_thread_id' => 'fb_thread_123',
            'participant_id' => 'fb_user_456',
            'participant_name' => 'Facebook User',
            'status' => 'open',
        ]);

        $message = SocialMessage::create([
            'message_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'thread_id' => $thread->thread_id,
            'platform' => 'facebook',
            'external_message_id' => 'fb_msg_789',
            'direction' => 'incoming',
            'sender_id' => 'fb_user_456',
            'content' => 'هل لديكم خدمة توصيل؟',
            'received_at' => now(),
            'status' => 'received',
        ]);

        $this->assertDatabaseHas('cmis.social_messages', [
            'message_id' => $message->message_id,
            'platform' => 'facebook',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'social_messaging',
            'platform' => 'facebook',
            'action' => 'receive_message',
        ]);
    }

    /** @test */
    public function it_sends_facebook_messenger_reply()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $thread = MessageThread::create([
            'thread_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'facebook',
            'external_thread_id' => 'fb_thread_123',
            'participant_id' => 'fb_user_456',
            'status' => 'open',
        ]);

        $replyMessage = SocialMessage::create([
            'message_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'thread_id' => $thread->thread_id,
            'platform' => 'facebook',
            'direction' => 'outgoing',
            'sender_id' => $user->user_id,
            'recipient_id' => 'fb_user_456',
            'content' => 'نعم، لدينا خدمة توصيل مجانية لجميع الطلبات!',
            'status' => 'pending',
        ]);

        $this->mockMetaAPI('success', [
            'message_id' => 'fb_msg_reply_123',
        ]);

        SendSocialMessageJob::dispatch($replyMessage);
        Queue::assertPushed(SendSocialMessageJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_messaging',
            'platform' => 'facebook',
            'action' => 'send_reply',
        ]);
    }
}
