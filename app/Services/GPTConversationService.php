<?php

namespace App\Services;

use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * GPT Conversation Service
 *
 * Manages conversational context and sessions for GPT integration.
 * Handles multi-turn conversations, context persistence, and message history.
 */
class GPTConversationService
{
    protected CacheService $cache;
    protected int $maxMessages = 20;
    protected int $sessionTTL = 3600; // 1 hour

    public function __construct(CacheService $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Create a new conversation session
     */
    public function createSession(string $userId, array $initialContext = []): string
    {
        $sessionId = Str::uuid()->toString();

        $session = [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'messages' => [],
            'context' => $initialContext,
            'metadata' => [
                'message_count' => 0,
                'total_tokens' => 0,
            ],
        ];

        $this->saveSession($sessionId, $session);

        Log::info('GPT conversation session created', [
            'session_id' => $sessionId,
            'user_id' => $userId,
        ]);

        return $sessionId;
    }

    /**
     * Get existing session or create new one
     */
    public function getOrCreateSession(string $userId, ?string $sessionId = null): array
    {
        if ($sessionId) {
            $session = $this->getSession($sessionId);
            if ($session && $session['user_id'] === $userId) {
                return $session;
            }
        }

        // Create new session
        $newSessionId = $this->createSession($userId);
        return $this->getSession($newSessionId);
    }

    /**
     * Get conversation session
     */
    public function getSession(string $sessionId): ?array
    {
        $cacheKey = "gpt_session:{$sessionId}";
        return Cache::get($cacheKey);
    }

    /**
     * Save conversation session
     */
    protected function saveSession(string $sessionId, array $session): void
    {
        $cacheKey = "gpt_session:{$sessionId}";
        $session['updated_at'] = now()->toISOString();
        Cache::put($cacheKey, $session, $this->sessionTTL);
    }

    /**
     * Add message to conversation
     */
    public function addMessage(
        string $sessionId,
        string $role,
        string $content,
        array $metadata = []
    ): array {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception("Session not found: {$sessionId}");
        }

        $message = [
            'message_id' => Str::uuid()->toString(),
            'role' => $role, // 'user' or 'assistant'
            'content' => $content,
            'timestamp' => now()->toISOString(),
            'metadata' => $metadata,
        ];

        $session['messages'][] = $message;
        $session['metadata']['message_count']++;

        if (isset($metadata['tokens'])) {
            $session['metadata']['total_tokens'] += $metadata['tokens'];
        }

        // Trim old messages if exceeding max
        if (count($session['messages']) > $this->maxMessages) {
            $session['messages'] = array_slice($session['messages'], -$this->maxMessages);
        }

        $this->saveSession($sessionId, $session);

        return $message;
    }

    /**
     * Get conversation history
     */
    public function getHistory(string $sessionId, int $limit = null): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            return [];
        }

        $messages = $session['messages'];

        if ($limit) {
            $messages = array_slice($messages, -$limit);
        }

        return $messages;
    }

    /**
     * Update conversation context
     */
    public function updateContext(string $sessionId, array $context): void
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception("Session not found: {$sessionId}");
        }

        $session['context'] = array_merge($session['context'], $context);
        $this->saveSession($sessionId, $session);
    }

    /**
     * Get conversation context
     */
    public function getContext(string $sessionId): array
    {
        $session = $this->getSession($sessionId);
        return $session['context'] ?? [];
    }

    /**
     * Build context for GPT from conversation history
     */
    public function buildGPTContext(string $sessionId, int $messageLimit = 10): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            return [];
        }

        $messages = array_slice($session['messages'], -$messageLimit);
        $context = $session['context'];

        return [
            'conversation_history' => array_map(function($msg) {
                return [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            }, $messages),
            'context' => $context,
            'session_metadata' => [
                'session_id' => $session['session_id'],
                'message_count' => $session['metadata']['message_count'],
                'session_age_minutes' => now()->diffInMinutes($session['created_at']),
            ],
        ];
    }

    /**
     * Clear conversation history
     */
    public function clearHistory(string $sessionId): void
    {
        $session = $this->getSession($sessionId);

        if ($session) {
            $session['messages'] = [];
            $session['metadata']['message_count'] = 0;
            $this->saveSession($sessionId, $session);

            Log::info('GPT conversation history cleared', [
                'session_id' => $sessionId,
            ]);
        }
    }

    /**
     * Delete conversation session
     */
    public function deleteSession(string $sessionId): void
    {
        $cacheKey = "gpt_session:{$sessionId}";
        Cache::forget($cacheKey);

        Log::info('GPT conversation session deleted', [
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Extend session TTL
     */
    public function extendSession(string $sessionId, int $additionalSeconds = null): void
    {
        $session = $this->getSession($sessionId);

        if ($session) {
            $ttl = $additionalSeconds ?? $this->sessionTTL;
            $this->saveSession($sessionId, $session);

            // Update TTL
            $cacheKey = "gpt_session:{$sessionId}";
            Cache::put($cacheKey, $session, $ttl);
        }
    }

    /**
     * Get session statistics
     */
    public function getSessionStats(string $sessionId): ?array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            return null;
        }

        $messages = $session['messages'];
        $userMessages = array_filter($messages, fn($m) => $m['role'] === 'user');
        $assistantMessages = array_filter($messages, fn($m) => $m['role'] === 'assistant');

        return [
            'session_id' => $sessionId,
            'created_at' => $session['created_at'],
            'updated_at' => $session['updated_at'],
            'duration_minutes' => now()->diffInMinutes($session['created_at']),
            'total_messages' => count($messages),
            'user_messages' => count($userMessages),
            'assistant_messages' => count($assistantMessages),
            'total_tokens' => $session['metadata']['total_tokens'],
            'context_keys' => array_keys($session['context']),
        ];
    }

    /**
     * List active sessions for a user
     */
    public function getUserSessions(string $userId): array
    {
        // Note: This requires Redis SCAN or storing session IDs separately
        // For now, return empty array - would need additional storage structure
        // to efficiently list all user sessions

        Log::warning('getUserSessions called but not fully implemented', [
            'user_id' => $userId,
        ]);

        return [];
    }

    /**
     * Summarize conversation for context compression
     */
    public function summarizeConversation(string $sessionId, AIService $aiService): ?string
    {
        $session = $this->getSession($sessionId);

        if (!$session || empty($session['messages'])) {
            return null;
        }

        // Build conversation text
        $conversationText = '';
        foreach ($session['messages'] as $message) {
            $role = ucfirst($message['role']);
            $conversationText .= "{$role}: {$message['content']}\n\n";
        }

        // Generate summary using AI
        $prompt = "Please summarize the following conversation concisely, capturing the key points and context:\n\n{$conversationText}\n\nSummary:";

        $result = $aiService->generate($prompt, 'summary', [
            'temperature' => 0.3,
            'max_tokens' => 200,
        ]);

        if ($result && isset($result['content'])) {
            // Store summary in context
            $this->updateContext($sessionId, [
                'conversation_summary' => $result['content'],
                'summarized_at' => now()->toISOString(),
            ]);

            return $result['content'];
        }

        return null;
    }
}
