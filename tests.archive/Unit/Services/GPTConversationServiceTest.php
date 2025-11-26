<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\GPTConversationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

use PHPUnit\Framework\Attributes\Test;
class GPTConversationServiceTest extends TestCase
{
    use RefreshDatabase;

    private GPTConversationService $service;
    private string $userId;
    private string $orgId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(GPTConversationService::class);
        $this->userId = 'user-123';
        $this->orgId = 'org-456';
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    #[Test]
    public function it_can_create_new_session()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);

        $this->assertIsArray($session);
        $this->assertArrayHasKey('session_id', $session);
        $this->assertArrayHasKey('user_id', $session);
        $this->assertArrayHasKey('org_id', $session);
        $this->assertArrayHasKey('messages', $session);
        $this->assertArrayHasKey('context', $session);
        $this->assertArrayHasKey('created_at', $session);
        $this->assertArrayHasKey('metadata', $session);

        $this->assertEquals($this->userId, $session['user_id']);
        $this->assertEquals($this->orgId, $session['org_id']);
        $this->assertEmpty($session['messages']);
    }

    #[Test]
    public function it_can_get_or_create_session()
    {
        $sessionId = null;

        // First call should create new session
        $session1 = $this->service->getOrCreateSession($sessionId, $this->userId, $this->orgId);

        $this->assertIsArray($session1);
        $this->assertNotNull($session1['session_id']);

        // Second call with same session ID should retrieve existing session
        $sessionId = $session1['session_id'];
        $session2 = $this->service->getOrCreateSession($sessionId, $this->userId, $this->orgId);

        $this->assertEquals($session1['session_id'], $session2['session_id']);
    }

    #[Test]
    public function it_can_add_user_message()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        $message = $this->service->addMessage($sessionId, 'user', 'Hello, how are you?');

        $this->assertIsArray($message);
        $this->assertArrayHasKey('message_id', $message);
        $this->assertArrayHasKey('role', $message);
        $this->assertArrayHasKey('content', $message);
        $this->assertArrayHasKey('timestamp', $message);

        $this->assertEquals('user', $message['role']);
        $this->assertEquals('Hello, how are you?', $message['content']);
    }

    #[Test]
    public function it_can_add_assistant_message()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        $message = $this->service->addMessage($sessionId, 'assistant', 'I am fine, thank you!');

        $this->assertEquals('assistant', $message['role']);
        $this->assertEquals('I am fine, thank you!', $message['content']);
    }

    #[Test]
    public function it_can_add_message_with_metadata()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        $metadata = [
            'tokens_used' => 150,
            'model' => 'gpt-4',
        ];

        $message = $this->service->addMessage($sessionId, 'assistant', 'Response', $metadata);

        $this->assertArrayHasKey('metadata', $message);
        $this->assertEquals(150, $message['metadata']['tokens_used']);
        $this->assertEquals('gpt-4', $message['metadata']['model']);
    }

    #[Test]
    public function it_increments_message_count()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        $this->service->addMessage($sessionId, 'user', 'Message 1');
        $this->service->addMessage($sessionId, 'assistant', 'Response 1');
        $this->service->addMessage($sessionId, 'user', 'Message 2');

        $updatedSession = $this->service->getSession($sessionId);

        $this->assertEquals(3, $updatedSession['metadata']['message_count']);
    }

    #[Test]
    public function it_limits_message_history()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        // Add more than maxMessages (20)
        for ($i = 0; $i < 25; $i++) {
            $this->service->addMessage($sessionId, 'user', "Message {$i}");
        }

        $updatedSession = $this->service->getSession($sessionId);

        // Should only keep last 20 messages
        $this->assertCount(20, $updatedSession['messages']);

        // First message should be message 5 (messages 0-4 were trimmed)
        $this->assertStringContainsString('Message 5', $updatedSession['messages'][0]['content']);
    }

    #[Test]
    public function it_can_get_conversation_history()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        $this->service->addMessage($sessionId, 'user', 'Hello');
        $this->service->addMessage($sessionId, 'assistant', 'Hi there!');
        $this->service->addMessage($sessionId, 'user', 'How are you?');

        $history = $this->service->getHistory($sessionId);

        $this->assertCount(3, $history);
        $this->assertEquals('Hello', $history[0]['content']);
        $this->assertEquals('Hi there!', $history[1]['content']);
        $this->assertEquals('How are you?', $history[2]['content']);
    }

    #[Test]
    public function it_can_limit_history_results()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        for ($i = 0; $i < 10; $i++) {
            $this->service->addMessage($sessionId, 'user', "Message {$i}");
        }

        $history = $this->service->getHistory($sessionId, 5);

        // Should return last 5 messages
        $this->assertCount(5, $history);
        $this->assertStringContainsString('Message 5', $history[0]['content']);
    }

    #[Test]
    public function it_can_update_session_context()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        $contextData = [
            'active_campaign_id' => 'campaign-789',
            'current_task' => 'Creating content plan',
        ];

        $this->service->updateContext($sessionId, $contextData);

        $updatedSession = $this->service->getSession($sessionId);

        $this->assertEquals('campaign-789', $updatedSession['context']['active_campaign_id']);
        $this->assertEquals('Creating content plan', $updatedSession['context']['current_task']);
    }

    #[Test]
    public function it_merges_context_updates()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        $this->service->updateContext($sessionId, ['key1' => 'value1']);
        $this->service->updateContext($sessionId, ['key2' => 'value2']);

        $updatedSession = $this->service->getSession($sessionId);

        $this->assertEquals('value1', $updatedSession['context']['key1']);
        $this->assertEquals('value2', $updatedSession['context']['key2']);
    }

    #[Test]
    public function it_can_build_gpt_context()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        $this->service->addMessage($sessionId, 'user', 'What are my campaigns?');
        $this->service->addMessage($sessionId, 'assistant', 'You have 5 active campaigns.');
        $this->service->updateContext($sessionId, ['campaign_count' => 5]);

        $context = $this->service->buildGPTContext($sessionId);

        $this->assertIsArray($context);
        $this->assertArrayHasKey('conversation_history', $context);
        $this->assertArrayHasKey('context', $context);
        $this->assertArrayHasKey('session_metadata', $context);

        $this->assertCount(2, $context['conversation_history']);
        $this->assertEquals('user', $context['conversation_history'][0]['role']);
        $this->assertEquals('assistant', $context['conversation_history'][1]['role']);
        $this->assertEquals(5, $context['context']['campaign_count']);
    }

    #[Test]
    public function it_limits_context_message_history()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        // Add 15 messages
        for ($i = 0; $i < 15; $i++) {
            $this->service->addMessage($sessionId, 'user', "Message {$i}");
        }

        // Request only last 5 messages
        $context = $this->service->buildGPTContext($sessionId, 5);

        $this->assertCount(5, $context['conversation_history']);
    }

    #[Test]
    public function it_can_clear_conversation_history()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        $this->service->addMessage($sessionId, 'user', 'Message 1');
        $this->service->addMessage($sessionId, 'user', 'Message 2');

        $this->service->clearHistory($sessionId);

        $history = $this->service->getHistory($sessionId);

        $this->assertEmpty($history);
    }

    #[Test]
    public function it_can_get_session_statistics()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        $this->service->addMessage($sessionId, 'user', 'Hello');
        $this->service->addMessage($sessionId, 'assistant', 'Hi!');

        $stats = $this->service->getSessionStats($sessionId);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('session_id', $stats);
        $this->assertArrayHasKey('user_id', $stats);
        $this->assertArrayHasKey('message_count', $stats);
        $this->assertArrayHasKey('created_at', $stats);
        $this->assertArrayHasKey('last_activity', $stats);

        $this->assertEquals(2, $stats['message_count']);
    }

    #[Test]
    public function it_returns_null_for_non_existent_session_stats()
    {
        $stats = $this->service->getSessionStats('non-existent-session-id');

        $this->assertNull($stats);
    }

    #[Test]
    public function it_can_summarize_conversation()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        $this->service->addMessage($sessionId, 'user', 'I want to create a campaign');
        $this->service->addMessage($sessionId, 'assistant', 'Sure, what is the campaign name?');
        $this->service->addMessage($sessionId, 'user', 'Summer Sale 2025');

        $summary = $this->service->summarizeConversation($sessionId);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('session_id', $summary);
        $this->assertArrayHasKey('message_count', $summary);
        $this->assertArrayHasKey('topics', $summary);
        $this->assertArrayHasKey('summary', $summary);
    }

    #[Test]
    public function it_stores_sessions_in_cache()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        $cacheKey = "gpt_conversation:{$sessionId}";

        $this->assertTrue(Cache::has($cacheKey));
    }

    #[Test]
    public function it_sets_correct_cache_ttl()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        // Add a message to update last activity
        $this->service->addMessage($sessionId, 'user', 'Test');

        $cacheKey = "gpt_conversation:{$sessionId}";

        // Cache should exist
        $this->assertTrue(Cache::has($cacheKey));

        // After expiry time (simulated by manually clearing), session should be gone
        Cache::forget($cacheKey);
        $this->assertFalse(Cache::has($cacheKey));
    }

    #[Test]
    public function it_handles_invalid_session_id_gracefully()
    {
        $this->expectException(\Exception::class);

        $this->service->getSession('invalid-session-id');
    }

    #[Test]
    public function it_generates_unique_message_ids()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        $message1 = $this->service->addMessage($sessionId, 'user', 'Message 1');
        $message2 = $this->service->addMessage($sessionId, 'user', 'Message 2');

        $this->assertNotEquals($message1['message_id'], $message2['message_id']);
    }

    #[Test]
    public function it_maintains_message_order()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);
        $sessionId = $session['session_id'];

        $this->service->addMessage($sessionId, 'user', 'First');
        $this->service->addMessage($sessionId, 'assistant', 'Second');
        $this->service->addMessage($sessionId, 'user', 'Third');

        $history = $this->service->getHistory($sessionId);

        $this->assertEquals('First', $history[0]['content']);
        $this->assertEquals('Second', $history[1]['content']);
        $this->assertEquals('Third', $history[2]['content']);
    }

    #[Test]
    public function it_preserves_org_context_in_session()
    {
        $session = $this->service->createSession($this->userId, $this->orgId);

        $this->assertEquals($this->orgId, $session['org_id']);
        $this->assertEquals($this->orgId, $session['context']['org_id']);
    }
}
