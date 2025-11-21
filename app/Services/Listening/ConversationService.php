<?php

namespace App\Services\Listening;

use App\Models\Listening\SocialConversation;
use App\Models\Listening\SocialMention;
use App\Models\Listening\ResponseTemplate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConversationService
{
    public function __construct(
        protected SentimentAnalysisService $sentimentService
    ) {}

    /**
     * Create conversation from mention
     */
    public function createConversation(SocialMention $mention, array $data = []): SocialConversation
    {
        $conversation = SocialConversation::create([
            'org_id' => $mention->org_id,
            'root_mention_id' => $mention->mention_id,
            'platform' => $mention->platform,
            'conversation_type' => $data['conversation_type'] ?? 'thread',
            'priority' => $data['priority'] ?? $this->calculatePriority($mention),
            'participants' => [$mention->author_username],
            'message_count' => 1,
            'overall_sentiment' => $mention->sentiment,
            'last_activity_at' => $mention->published_at,
        ]);

        Log::info('Conversation created', [
            'conversation_id' => $conversation->conversation_id,
            'mention_id' => $mention->mention_id,
        ]);

        return $conversation;
    }

    /**
     * Calculate conversation priority
     */
    protected function calculatePriority(SocialMention $mention): string
    {
        // Negative sentiment = urgent
        if ($mention->sentiment === 'negative' && $mention->sentiment_score < -0.5) {
            return 'urgent';
        }

        // Influencer = high priority
        if ($mention->isInfluencer()) {
            return 'high';
        }

        // High engagement = high priority
        if ($mention->hasHighEngagement()) {
            return 'high';
        }

        return 'normal';
    }

    /**
     * Add message to conversation
     */
    public function addMessage(
        SocialConversation $conversation,
        string $content,
        string $author,
        bool $isResponse = false
    ): void {
        $conversation->incrementMessageCount();
        $conversation->addParticipant($author);

        if ($isResponse) {
            $conversation->recordResponse();
        }

        $conversation->updateActivity();

        Log::info('Message added to conversation', [
            'conversation_id' => $conversation->conversation_id,
            'author' => $author,
            'is_response' => $isResponse,
        ]);
    }

    /**
     * Assign conversation to user
     */
    public function assignConversation(SocialConversation $conversation, string $userId): void
    {
        $conversation->assignTo($userId);
        $conversation->startProgress();

        Log::info('Conversation assigned', [
            'conversation_id' => $conversation->conversation_id,
            'assigned_to' => $userId,
        ]);
    }

    /**
     * Respond to conversation
     */
    public function respond(
        SocialConversation $conversation,
        string $responseContent,
        ?string $templateId = null
    ): array {
        try {
            // Use template if provided
            if ($templateId) {
                $template = ResponseTemplate::findOrFail($templateId);
                $responseContent = $template->render();
                $template->incrementUsage();
            }

            // In production, this would post to the actual platform
            // For now, log the response
            Log::info('Responding to conversation', [
                'conversation_id' => $conversation->conversation_id,
                'platform' => $conversation->platform,
                'content_length' => strlen($responseContent),
            ]);

            // Mark conversation as responded
            $conversation->recordResponse();
            $conversation->addMessage($responseContent, 'system', true);

            return [
                'success' => true,
                'response_content' => $responseContent,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to respond to conversation', [
                'conversation_id' => $conversation->conversation_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Suggest response templates
     */
    public function suggestTemplates(SocialConversation $conversation, int $limit = 5): Collection
    {
        $mention = $conversation->rootMention;

        $templates = ResponseTemplate::where('org_id', $conversation->org_id)
            ->active()
            ->forPlatform($conversation->platform)
            ->get();

        // Score templates based on relevance
        $scoredTemplates = $templates->map(function($template) use ($mention) {
            $score = $template->getMatchScore($mention->content);

            // Boost score based on sentiment match
            if ($mention->sentiment === 'negative' && $template->category === 'complaint') {
                $score += 20;
            } elseif ($mention->sentiment === 'positive' && $template->category === 'feedback') {
                $score += 10;
            }

            return [
                'template' => $template,
                'score' => $score,
            ];
        })->sortByDesc('score');

        return $scoredTemplates->take($limit)->pluck('template');
    }

    /**
     * Escalate conversation
     */
    public function escalate(SocialConversation $conversation, string $reason): void
    {
        $conversation->escalate();
        $conversation->addNote("Escalated: {$reason}");

        Log::warning('Conversation escalated', [
            'conversation_id' => $conversation->conversation_id,
            'reason' => $reason,
        ]);

        // In production, send notifications to managers
    }

    /**
     * Resolve conversation
     */
    public function resolve(SocialConversation $conversation, string $resolution): void
    {
        $conversation->resolve();
        $conversation->addNote("Resolved: {$resolution}");

        Log::info('Conversation resolved', [
            'conversation_id' => $conversation->conversation_id,
            'resolution_time' => $conversation->resolution_time_minutes,
        ]);
    }

    /**
     * Get conversation statistics
     */
    public function getStatistics(string $orgId, int $days = 30): array
    {
        $query = SocialConversation::where('org_id', $orgId)
            ->where('created_at', '>=', now()->subDays($days));

        $total = $query->count();
        $open = (clone $query)->open()->count();
        $inProgress = (clone $query)->inProgress()->count();
        $resolved = (clone $query)->resolved()->count();

        $avgResponseTime = (clone $query)
            ->whereNotNull('response_time_minutes')
            ->avg('response_time_minutes');

        $avgResolutionTime = (clone $query)
            ->whereNotNull('resolution_time_minutes')
            ->avg('resolution_time_minutes');

        $requiresEscalation = (clone $query)->requireingEscalation()->count();

        return [
            'total_conversations' => $total,
            'status_breakdown' => [
                'open' => $open,
                'in_progress' => $inProgress,
                'resolved' => $resolved,
            ],
            'avg_response_time_minutes' => round($avgResponseTime ?? 0, 2),
            'avg_resolution_time_minutes' => round($avgResolutionTime ?? 0, 2),
            'requiring_escalation' => $requiresEscalation,
            'period_days' => $days,
        ];
    }

    /**
     * Get user workload
     */
    public function getUserWorkload(string $userId): array
    {
        $assigned = SocialConversation::assignedTo($userId)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        $urgent = SocialConversation::assignedTo($userId)
            ->urgent()
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        $unread = SocialConversation::assignedTo($userId)
            ->withUnread()
            ->count();

        return [
            'assigned_conversations' => $assigned,
            'urgent_conversations' => $urgent,
            'unread_messages' => $unread,
        ];
    }

    /**
     * Get conversation inbox
     */
    public function getInbox(string $orgId, array $filters = []): Collection
    {
        $query = SocialConversation::where('org_id', $orgId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['assigned_to'])) {
            $query->assignedTo($filters['assigned_to']);
        }

        if (isset($filters['unassigned']) && $filters['unassigned']) {
            $query->unassigned();
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['platform'])) {
            $query->onPlatform($filters['platform']);
        }

        if (isset($filters['escalated']) && $filters['escalated']) {
            $query->requireingEscalation();
        }

        return $query->recentActivity()->get();
    }

    /**
     * Auto-assign conversations
     */
    public function autoAssign(string $orgId): array
    {
        $unassignedConversations = SocialConversation::where('org_id', $orgId)
            ->unassigned()
            ->whereIn('status', ['open'])
            ->get();

        $assigned = 0;

        foreach ($unassignedConversations as $conversation) {
            // Simple round-robin assignment
            // In production, use more sophisticated assignment logic
            $userId = $this->getNextAvailableUser($orgId);

            if ($userId) {
                $this->assignConversation($conversation, $userId);
                $assigned++;
            }
        }

        return [
            'total_unassigned' => $unassignedConversations->count(),
            'assigned' => $assigned,
        ];
    }

    /**
     * Get next available user for assignment
     */
    protected function getNextAvailableUser(string $orgId): ?string
    {
        // Placeholder - would query actual users with workload balancing
        return null;
    }

    /**
     * Identify stale conversations
     */
    public function identifyStaleConversations(string $orgId, int $hoursThreshold = 48): Collection
    {
        return SocialConversation::where('org_id', $orgId)
            ->whereIn('status', ['open', 'in_progress'])
            ->stale($hoursThreshold)
            ->get();
    }

    /**
     * Bulk update conversations
     */
    public function bulkUpdate(array $conversationIds, array $updates): array
    {
        $updated = 0;

        foreach ($conversationIds as $conversationId) {
            try {
                $conversation = SocialConversation::findOrFail($conversationId);

                if (isset($updates['status'])) {
                    match($updates['status']) {
                        'resolved' => $conversation->resolve(),
                        'closed' => $conversation->close(),
                        default => $conversation->update(['status' => $updates['status']]),
                    };
                }

                if (isset($updates['assigned_to'])) {
                    $conversation->assignTo($updates['assigned_to']);
                }

                if (isset($updates['priority'])) {
                    $conversation->setPriority($updates['priority']);
                }

                $updated++;

            } catch (\Exception $e) {
                Log::error('Failed to update conversation', [
                    'conversation_id' => $conversationId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'total' => count($conversationIds),
            'updated' => $updated,
        ];
    }
}
