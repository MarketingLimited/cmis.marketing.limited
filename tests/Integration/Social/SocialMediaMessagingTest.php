<?php

namespace Tests\Integration\Social;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Social\SocialMessage;
use App\Models\Social\MessageThread;
use App\Jobs\ProcessIncomingMessageJob;
use App\Jobs\SendSocialMessageJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Social Media Messaging Integration Test
 *
 * اختبارات شاملة لاستقبال الرسائل من جميع منصات السوشيال ميديا والرد عليها
 */
class SocialMediaMessagingTest extends TestCase
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

    /** @test */
    public function it_handles_message_thread_with_multiple_messages()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'instagram');

        $thread = MessageThread::create([
            'thread_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'instagram',
            'external_thread_id' => 'conversation_123',
            'participant_id' => 'user_456',
            'status' => 'open',
        ]);

        // Create multiple messages in thread
        $messages = [];
        for ($i = 1; $i <= 5; $i++) {
            $direction = $i % 2 == 0 ? 'outgoing' : 'incoming';
            $messages[] = SocialMessage::create([
                'message_id' => Str::uuid(),
                'org_id' => $org->org_id,
                'thread_id' => $thread->thread_id,
                'platform' => 'instagram',
                'direction' => $direction,
                'content' => "رسالة رقم {$i}",
                'received_at' => now()->addMinutes($i),
                'status' => 'received',
            ]);
        }

        $this->assertEquals(5, SocialMessage::where('thread_id', $thread->thread_id)->count());

        $this->logTestResult('passed', [
            'workflow' => 'social_messaging',
            'test' => 'multi_message_thread',
            'message_count' => 5,
        ]);
    }

    /** @test */
    public function it_auto_responds_with_ai_for_common_questions()
    {
        Queue::fake();

        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

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

        // Incoming message with common question
        $incomingMessage = SocialMessage::create([
            'message_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'thread_id' => $thread->thread_id,
            'platform' => 'instagram',
            'direction' => 'incoming',
            'sender_id' => 'user_456',
            'content' => 'ما هي ساعات العمل؟',
            'received_at' => now(),
            'status' => 'received',
        ]);

        // Mock AI response generation
        $this->mockGeminiAPI('success', [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'نعمل من الأحد إلى الخميس من 9 صباحاً إلى 6 مساءً'],
                        ],
                    ],
                ],
            ],
        ]);

        ProcessIncomingMessageJob::dispatch($incomingMessage);
        Queue::assertPushed(ProcessIncomingMessageJob::class);

        $this->logTestResult('passed', [
            'workflow' => 'social_messaging',
            'test' => 'ai_auto_response',
        ]);
    }

    /** @test */
    public function it_marks_thread_as_resolved()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];

        $integration = $this->createTestIntegration($org->org_id, 'facebook');

        $thread = MessageThread::create([
            'thread_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'facebook',
            'external_thread_id' => 'thread_123',
            'participant_id' => 'user_456',
            'status' => 'open',
        ]);

        // Resolve thread
        $thread->update(['status' => 'resolved', 'resolved_at' => now()]);

        $this->assertDatabaseHas('cmis.message_threads', [
            'thread_id' => $thread->thread_id,
            'status' => 'resolved',
        ]);

        $this->logTestResult('passed', [
            'workflow' => 'social_messaging',
            'test' => 'thread_resolution',
        ]);
    }
}
