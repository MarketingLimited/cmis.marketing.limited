<?php

namespace App\Http\Controllers\GPT;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\GPTConversationService;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * GPT Conversation Controller
 *
 * Handles conversation/chat operations for GPT/ChatGPT integration
 */
class GPTConversationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private GPTConversationService $conversationService,
        private AIService $aiService
    ) {}

    /**
     * Create or get conversation session
     */
    public function session(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->query('session_id');
            $session = $this->conversationService->getOrCreateSession(
                $sessionId,
                $request->user()->user_id,
                $request->user()->current_org_id
            );

            return $this->success($session, 'Conversation session ready');
        } catch (\Exception $e) {
            \Log::error('GPT conversation session error: ' . $e->getMessage());
            return $this->serverError('Failed to create/retrieve session');
        }
    }

    /**
     * Send message in conversation
     */
    public function message(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|uuid',
            'message' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $sessionId = $request->input('session_id');
            $userMessage = $request->input('message');

            // Add user message
            $this->conversationService->addMessage(
                $sessionId,
                'user',
                $userMessage
            );

            // Get conversation context for AI
            $context = $this->conversationService->buildGPTContext($sessionId);

            // Build enhanced prompt with conversation history and context
            $prompt = $this->buildConversationalPrompt($userMessage, $context);

            // Generate AI response
            $aiResult = $this->aiService->generate($prompt, 'chat_response', [
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

            $aiResponse = $aiResult['content'] ?? "I'm here to help with your marketing campaigns. What would you like to know?";

            // Add assistant message
            $this->conversationService->addMessage(
                $sessionId,
                'assistant',
                $aiResponse,
                [
                    'tokens_used' => $aiResult['tokens']['total'] ?? 0,
                    'model' => $aiResult['model'] ?? 'gpt-4',
                ]
            );

            return $this->success([
                'response' => $aiResponse,
                'session_id' => $sessionId,
                'tokens_used' => $aiResult['tokens']['total'] ?? 0,
            ], 'Message processed successfully');

        } catch (\Exception $e) {
            \Log::error('GPT conversation message error: ' . $e->getMessage(), [
                'session_id' => $request->input('session_id'),
                'user_id' => $request->user()->user_id,
            ]);

            // Return fallback response
            $fallbackResponse = "I apologize, but I'm having trouble processing your request right now. Please try again in a moment.";

            try {
                $this->conversationService->addMessage(
                    $request->input('session_id'),
                    'assistant',
                    $fallbackResponse,
                    ['error' => true]
                );
            } catch (\Exception $innerException) {
                \Log::error('Failed to save fallback message: ' . $innerException->getMessage());
            }

            return $this->error('Failed to process message', ['detail' => $e->getMessage()], 500);
        }
    }

    /**
     * Get conversation history
     */
    public function history(Request $request, string $sessionId): JsonResponse
    {
        try {
            $limit = $request->query('limit', 20);
            $history = $this->conversationService->getHistory($sessionId, $limit);

            return $this->success([
                'session_id' => $sessionId,
                'messages' => $history,
                'count' => count($history),
            ]);
        } catch (\Exception $e) {
            \Log::error('GPT conversation history error: ' . $e->getMessage());
            return $this->serverError('Failed to retrieve conversation history');
        }
    }

    /**
     * Clear conversation history
     */
    public function clear(Request $request, string $sessionId): JsonResponse
    {
        try {
            $this->conversationService->clearHistory($sessionId);

            return $this->success([
                'session_id' => $sessionId,
                'cleared' => true,
            ], 'Conversation history cleared');
        } catch (\Exception $e) {
            \Log::error('GPT conversation clear error: ' . $e->getMessage());
            return $this->serverError('Failed to clear conversation history');
        }
    }

    /**
     * Get conversation statistics
     */
    public function stats(Request $request, string $sessionId): JsonResponse
    {
        try {
            $stats = $this->conversationService->getSessionStats($sessionId);

            if (!$stats) {
                return $this->notFound('Session not found');
            }

            return $this->success($stats);
        } catch (\Exception $e) {
            \Log::error('GPT conversation stats error: ' . $e->getMessage());
            return $this->serverError('Failed to retrieve conversation statistics');
        }
    }

    /**
     * Build conversational prompt with context
     */
    private function buildConversationalPrompt(string $userMessage, array $context): string
    {
        $prompt = "You are an AI assistant for CMIS (Cognitive Marketing Intelligence System), helping users manage their marketing campaigns.\n\n";

        // Add conversation history
        if (!empty($context['conversation_history'])) {
            $prompt .= "Previous conversation:\n";
            $recentMessages = array_slice($context['conversation_history'], -5); // Last 5 messages
            foreach ($recentMessages as $msg) {
                $prompt .= "{$msg['role']}: {$msg['content']}\n";
            }
            $prompt .= "\n";
        }

        // Add user context
        if (!empty($context['context'])) {
            $orgId = $context['context']['org_id'] ?? null;
            if ($orgId) {
                $prompt .= "User's Organization ID: {$orgId}\n";
            }
        }

        $prompt .= "\nCurrent user message: {$userMessage}\n\n";
        $prompt .= "Please provide a helpful, concise response focused on marketing campaign management. ";
        $prompt .= "If the user asks about campaigns, content plans, analytics, or knowledge base, provide specific actionable guidance.";

        return $prompt;
    }
}
