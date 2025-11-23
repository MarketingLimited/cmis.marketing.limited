<?php

namespace App\Http\Controllers\Api\Listening;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Listening\SocialConversation;
use App\Services\Listening\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Social Conversation Controller
 *
 * Manages social media conversations and responses
 */
class SocialConversationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ConversationService $conversationService
    ) {}

    /**
     * Get conversation inbox
     *
     * GET /api/listening/conversations
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $filters = $request->only([
            'status',
            'assigned_to',
            'unassigned',
            'priority',
            'platform',
            'escalated',
        ]);

        $conversations = $this->conversationService->getInbox($orgId, $filters);

        return $this->success($conversations, 'Conversations retrieved successfully');
    }

    /**
     * Get conversation details
     *
     * GET /api/listening/conversations/{id}
     */
    public function show(string $id): JsonResponse
    {
        $conversation = SocialConversation::with(['rootMention'])
            ->findOrFail($id);

        $suggestedTemplates = $this->conversationService->suggestTemplates($conversation);

        return $this->success([
            'conversation' => $conversation,
            'suggested_templates' => $suggestedTemplates,
        ], 'Conversation retrieved successfully');
    }

    /**
     * Respond to conversation
     *
     * POST /api/listening/conversations/{id}/respond
     */
    public function respond(Request $request, string $id): JsonResponse
    {
        $conversation = SocialConversation::findOrFail($id);

        $validated = $request->validate([
            'response_content' => 'required|string',
            'template_id' => 'uuid|nullable',
        ]);

        $result = $this->conversationService->respond(
            $conversation,
            $validated['response_content'],
            $validated['template_id'] ?? null
        );

        if ($result['success']) {
            return $this->success(
                $result,
                'Response sent successfully'
            );
        }

        return $this->error(
            'Failed to send response',
            400,
            ['error' => $result['error'] ?? null]
        );
    }

    /**
     * Assign conversation
     *
     * POST /api/listening/conversations/{id}/assign
     */
    public function assign(Request $request, string $id): JsonResponse
    {
        $conversation = SocialConversation::findOrFail($id);

        $validated = $request->validate([
            'assigned_to' => 'required|uuid',
        ]);

        $this->conversationService->assignConversation($conversation, $validated['assigned_to']);

        return $this->success(
            $conversation->fresh(),
            'Conversation assigned successfully'
        );
    }

    /**
     * Get conversation statistics
     *
     * GET /api/listening/conversations/statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;
        $days = $request->days ?? 30;

        $stats = $this->conversationService->getStatistics($orgId, $days);

        return $this->success($stats, 'Conversation statistics retrieved successfully');
    }
}
