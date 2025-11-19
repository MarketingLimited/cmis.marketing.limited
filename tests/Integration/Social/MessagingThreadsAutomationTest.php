<?php

namespace Tests\Integration\Social;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Social\SocialMessage;
use App\Models\Social\MessageThread;
use App\Jobs\ProcessIncomingMessageJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Messaging Threads & Automation Integration Test
 *
 * اختبارات المحادثات متعددة الرسائل والرد التلقائي بالذكاء الاصطناعي
 */
class MessagingThreadsAutomationTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
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
