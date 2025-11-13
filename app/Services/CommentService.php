<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * CommentService
 *
 * Handles comments and collaboration features
 * Implements Sprint 5.3: Comments & Collaboration
 *
 * Features:
 * - Threaded comments and replies
 * - @mentions with notifications
 * - Activity feed tracking
 * - Comment reactions
 * - Edit history
 */
class CommentService
{
    /**
     * Add a comment to an entity
     *
     * @param array $data
     * @return array
     */
    public function addComment(array $data): array
    {
        try {
            DB::beginTransaction();

            $commentId = (string) Str::uuid();

            // Insert comment
            DB::table('cmis.comments')->insert([
                'comment_id' => $commentId,
                'entity_type' => $data['entity_type'], // post, campaign, ad, content
                'entity_id' => $data['entity_id'],
                'user_id' => $data['user_id'],
                'comment_text' => $data['comment_text'],
                'parent_comment_id' => $data['parent_comment_id'] ?? null,
                'mentions' => json_encode($this->extractMentions($data['comment_text'])),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Process mentions and send notifications
            $mentions = $this->extractMentions($data['comment_text']);
            if (!empty($mentions)) {
                $this->processMentions($commentId, $mentions, $data['user_id']);
            }

            // Log activity
            $this->logActivity([
                'activity_type' => 'comment_added',
                'user_id' => $data['user_id'],
                'entity_type' => $data['entity_type'],
                'entity_id' => $data['entity_id'],
                'metadata' => ['comment_id' => $commentId]
            ]);

            DB::commit();

            // Clear cache
            $this->clearCommentCache($data['entity_type'], $data['entity_id']);

            $comment = DB::table('cmis.comments')->where('comment_id', $commentId)->first();

            return [
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => $this->formatComment($comment)
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to add comment',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reply to an existing comment
     *
     * @param string $parentCommentId
     * @param array $data
     * @return array
     */
    public function replyToComment(string $parentCommentId, array $data): array
    {
        try {
            // Get parent comment
            $parentComment = DB::table('cmis.comments')
                ->where('comment_id', $parentCommentId)
                ->first();

            if (!$parentComment) {
                return ['success' => false, 'message' => 'Parent comment not found'];
            }

            // Add reply as a child comment
            return $this->addComment([
                'entity_type' => $parentComment->entity_type,
                'entity_id' => $parentComment->entity_id,
                'user_id' => $data['user_id'],
                'comment_text' => $data['comment_text'],
                'parent_comment_id' => $parentCommentId
            ]);

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to add reply',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update a comment
     *
     * @param string $commentId
     * @param string $userId
     * @param string $newText
     * @return array
     */
    public function updateComment(string $commentId, string $userId, string $newText): array
    {
        try {
            DB::beginTransaction();

            $comment = DB::table('cmis.comments')->where('comment_id', $commentId)->first();

            if (!$comment) {
                return ['success' => false, 'message' => 'Comment not found'];
            }

            // Verify user owns the comment
            if ($comment->user_id !== $userId) {
                return ['success' => false, 'message' => 'You can only edit your own comments'];
            }

            // Store edit history
            DB::table('cmis.comment_history')->insert([
                'history_id' => (string) Str::uuid(),
                'comment_id' => $commentId,
                'previous_text' => $comment->comment_text,
                'edited_by' => $userId,
                'edited_at' => now()
            ]);

            // Update comment
            $mentions = $this->extractMentions($newText);

            DB::table('cmis.comments')
                ->where('comment_id', $commentId)
                ->update([
                    'comment_text' => $newText,
                    'mentions' => json_encode($mentions),
                    'is_edited' => true,
                    'updated_at' => now()
                ]);

            // Process new mentions
            if (!empty($mentions)) {
                $this->processMentions($commentId, $mentions, $userId);
            }

            DB::commit();

            $this->clearCommentCache($comment->entity_type, $comment->entity_id);

            return [
                'success' => true,
                'message' => 'Comment updated successfully',
                'data' => $this->formatComment(
                    DB::table('cmis.comments')->where('comment_id', $commentId)->first()
                )
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to update comment',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete a comment
     *
     * @param string $commentId
     * @param string $userId
     * @return array
     */
    public function deleteComment(string $commentId, string $userId): array
    {
        try {
            DB::beginTransaction();

            $comment = DB::table('cmis.comments')->where('comment_id', $commentId)->first();

            if (!$comment) {
                return ['success' => false, 'message' => 'Comment not found'];
            }

            // Verify user owns the comment
            if ($comment->user_id !== $userId) {
                return ['success' => false, 'message' => 'You can only delete your own comments'];
            }

            // Soft delete - mark as deleted instead of removing
            DB::table('cmis.comments')
                ->where('comment_id', $commentId)
                ->update([
                    'is_deleted' => true,
                    'deleted_at' => now()
                ]);

            DB::commit();

            $this->clearCommentCache($comment->entity_type, $comment->entity_id);

            return [
                'success' => true,
                'message' => 'Comment deleted successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to delete comment',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get comments for an entity
     *
     * @param string $entityType
     * @param string $entityId
     * @param array $options
     * @return array
     */
    public function getComments(string $entityType, string $entityId, array $options = []): array
    {
        try {
            $cacheKey = "comments:{$entityType}:{$entityId}:" . md5(json_encode($options));

            return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($entityType, $entityId, $options) {
                $query = DB::table('cmis.comments')
                    ->join('cmis.users', 'cmis.comments.user_id', '=', 'cmis.users.user_id')
                    ->where('cmis.comments.entity_type', $entityType)
                    ->where('cmis.comments.entity_id', $entityId)
                    ->where('cmis.comments.is_deleted', false)
                    ->select(
                        'cmis.comments.*',
                        'cmis.users.email',
                        'cmis.users.first_name',
                        'cmis.users.last_name'
                    );

                // Filter top-level comments only (no replies)
                if ($options['top_level_only'] ?? false) {
                    $query->whereNull('cmis.comments.parent_comment_id');
                }

                // Sorting
                $sortBy = $options['sort_by'] ?? 'created_at';
                $sortOrder = $options['sort_order'] ?? 'asc';
                $query->orderBy("cmis.comments.{$sortBy}", $sortOrder);

                $comments = $query->get();

                // Build threaded structure
                $formattedComments = [];
                $commentMap = [];

                // First pass: create all comments
                foreach ($comments as $comment) {
                    $formatted = $this->formatComment($comment);
                    $formatted['replies'] = [];
                    $commentMap[$comment->comment_id] = $formatted;
                }

                // Second pass: build hierarchy
                foreach ($commentMap as $commentId => $comment) {
                    if ($comment['parent_comment_id']) {
                        // This is a reply
                        if (isset($commentMap[$comment['parent_comment_id']])) {
                            $commentMap[$comment['parent_comment_id']]['replies'][] = $comment;
                        }
                    } else {
                        // This is a top-level comment
                        $formattedComments[] = &$commentMap[$commentId];
                    }
                }

                return [
                    'success' => true,
                    'data' => array_values($formattedComments),
                    'total' => count($formattedComments)
                ];
            });

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get comments',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Add reaction to comment
     *
     * @param string $commentId
     * @param string $userId
     * @param string $reactionType
     * @return array
     */
    public function addReaction(string $commentId, string $userId, string $reactionType): array
    {
        try {
            $comment = DB::table('cmis.comments')->where('comment_id', $commentId)->first();

            if (!$comment) {
                return ['success' => false, 'message' => 'Comment not found'];
            }

            // Check if user already reacted
            $existing = DB::table('cmis.comment_reactions')
                ->where('comment_id', $commentId)
                ->where('user_id', $userId)
                ->first();

            if ($existing) {
                // Update reaction type
                DB::table('cmis.comment_reactions')
                    ->where('comment_id', $commentId)
                    ->where('user_id', $userId)
                    ->update([
                        'reaction_type' => $reactionType,
                        'updated_at' => now()
                    ]);
            } else {
                // Add new reaction
                DB::table('cmis.comment_reactions')->insert([
                    'reaction_id' => (string) Str::uuid(),
                    'comment_id' => $commentId,
                    'user_id' => $userId,
                    'reaction_type' => $reactionType,
                    'created_at' => now()
                ]);
            }

            $this->clearCommentCache($comment->entity_type, $comment->entity_id);

            return [
                'success' => true,
                'message' => 'Reaction added successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to add reaction',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Remove reaction from comment
     *
     * @param string $commentId
     * @param string $userId
     * @return array
     */
    public function removeReaction(string $commentId, string $userId): array
    {
        try {
            $comment = DB::table('cmis.comments')->where('comment_id', $commentId)->first();

            if (!$comment) {
                return ['success' => false, 'message' => 'Comment not found'];
            }

            DB::table('cmis.comment_reactions')
                ->where('comment_id', $commentId)
                ->where('user_id', $userId)
                ->delete();

            $this->clearCommentCache($comment->entity_type, $comment->entity_id);

            return [
                'success' => true,
                'message' => 'Reaction removed successfully'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to remove reaction',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get activity feed for organization
     *
     * @param string $orgId
     * @param array $filters
     * @return array
     */
    public function getActivityFeed(string $orgId, array $filters = []): array
    {
        try {
            $query = DB::table('cmis.collaboration_activity')
                ->join('cmis.users', 'cmis.collaboration_activity.user_id', '=', 'cmis.users.user_id')
                ->where('cmis.collaboration_activity.org_id', $orgId)
                ->select(
                    'cmis.collaboration_activity.*',
                    'cmis.users.email',
                    'cmis.users.first_name',
                    'cmis.users.last_name'
                );

            // Apply filters
            if (!empty($filters['activity_type'])) {
                $query->where('cmis.collaboration_activity.activity_type', $filters['activity_type']);
            }

            if (!empty($filters['entity_type'])) {
                $query->where('cmis.collaboration_activity.entity_type', $filters['entity_type']);
            }

            if (!empty($filters['user_id'])) {
                $query->where('cmis.collaboration_activity.user_id', $filters['user_id']);
            }

            if (!empty($filters['start_date'])) {
                $query->where('cmis.collaboration_activity.created_at', '>=', $filters['start_date']);
            }

            if (!empty($filters['end_date'])) {
                $query->where('cmis.collaboration_activity.created_at', '<=', $filters['end_date']);
            }

            // Sorting
            $query->orderBy('cmis.collaboration_activity.created_at', 'desc');

            // Pagination
            $perPage = $filters['per_page'] ?? 50;
            $page = $filters['page'] ?? 1;
            $offset = ($page - 1) * $perPage;

            $total = $query->count();
            $activities = $query->offset($offset)->limit($perPage)->get();

            $formattedActivities = $activities->map(function ($activity) {
                return [
                    'activity_id' => $activity->activity_id,
                    'activity_type' => $activity->activity_type,
                    'entity_type' => $activity->entity_type,
                    'entity_id' => $activity->entity_id,
                    'user' => [
                        'user_id' => $activity->user_id,
                        'name' => trim(($activity->first_name ?? '') . ' ' . ($activity->last_name ?? '')),
                        'email' => $activity->email
                    ],
                    'metadata' => json_decode($activity->metadata ?? '{}', true),
                    'created_at' => $activity->created_at
                ];
            });

            return [
                'success' => true,
                'data' => $formattedActivities,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => ceil($total / $perPage)
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get activity feed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract @mentions from comment text
     *
     * @param string $text
     * @return array
     */
    protected function extractMentions(string $text): array
    {
        preg_match_all('/@(\w+)/', $text, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Process mentions and send notifications
     *
     * @param string $commentId
     * @param array $mentions
     * @param string $mentioningUserId
     * @return void
     */
    protected function processMentions(string $commentId, array $mentions, string $mentioningUserId): void
    {
        foreach ($mentions as $mention) {
            // Find user by username or email
            $user = DB::table('cmis.users')
                ->where('email', 'ILIKE', "%{$mention}%")
                ->first();

            if ($user && $user->user_id !== $mentioningUserId) {
                // Send notification
                DB::table('cmis.notifications')->insert([
                    'notification_id' => (string) Str::uuid(),
                    'user_id' => $user->user_id,
                    'type' => 'mention',
                    'title' => 'You were mentioned in a comment',
                    'message' => "You were mentioned by {$mentioningUserId}",
                    'data' => json_encode(['comment_id' => $commentId]),
                    'is_read' => false,
                    'created_at' => now()
                ]);
            }
        }
    }

    /**
     * Log collaboration activity
     *
     * @param array $data
     * @return void
     */
    protected function logActivity(array $data): void
    {
        try {
            // Get org_id from entity
            $orgId = $this->getOrgIdFromEntity($data['entity_type'], $data['entity_id']);

            if ($orgId) {
                DB::table('cmis.collaboration_activity')->insert([
                    'activity_id' => (string) Str::uuid(),
                    'org_id' => $orgId,
                    'user_id' => $data['user_id'],
                    'activity_type' => $data['activity_type'],
                    'entity_type' => $data['entity_type'],
                    'entity_id' => $data['entity_id'],
                    'metadata' => json_encode($data['metadata'] ?? []),
                    'created_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the main operation
            \Log::error('Failed to log activity', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get org_id from entity
     *
     * @param string $entityType
     * @param string $entityId
     * @return string|null
     */
    protected function getOrgIdFromEntity(string $entityType, string $entityId): ?string
    {
        try {
            switch ($entityType) {
                case 'post':
                    $result = DB::table('cmis.social_posts')
                        ->join('cmis.social_accounts', 'cmis.social_posts.social_account_id', '=', 'cmis.social_accounts.social_account_id')
                        ->where('cmis.social_posts.post_id', $entityId)
                        ->value('cmis.social_accounts.org_id');
                    return $result;

                case 'campaign':
                case 'ad':
                    $result = DB::table('cmis_ads.ad_campaigns')
                        ->join('cmis_ads.ad_accounts', 'cmis_ads.ad_campaigns.ad_account_id', '=', 'cmis_ads.ad_accounts.ad_account_id')
                        ->where('cmis_ads.ad_campaigns.ad_campaign_id', $entityId)
                        ->value('cmis_ads.ad_accounts.org_id');
                    return $result;

                default:
                    return null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format comment for response
     *
     * @param object $comment
     * @return array
     */
    protected function formatComment($comment): array
    {
        // Get reaction counts
        $reactions = DB::table('cmis.comment_reactions')
            ->where('comment_id', $comment->comment_id)
            ->select('reaction_type', DB::raw('COUNT(*) as count'))
            ->groupBy('reaction_type')
            ->get();

        $reactionCounts = [];
        foreach ($reactions as $reaction) {
            $reactionCounts[$reaction->reaction_type] = $reaction->count;
        }

        return [
            'comment_id' => $comment->comment_id,
            'entity_type' => $comment->entity_type,
            'entity_id' => $comment->entity_id,
            'parent_comment_id' => $comment->parent_comment_id ?? null,
            'user' => [
                'user_id' => $comment->user_id,
                'name' => trim(($comment->first_name ?? '') . ' ' . ($comment->last_name ?? '')),
                'email' => $comment->email ?? ''
            ],
            'comment_text' => $comment->comment_text,
            'mentions' => json_decode($comment->mentions ?? '[]', true),
            'is_edited' => $comment->is_edited ?? false,
            'reactions' => $reactionCounts,
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at
        ];
    }

    /**
     * Clear comment cache
     *
     * @param string $entityType
     * @param string $entityId
     * @return void
     */
    protected function clearCommentCache(string $entityType, string $entityId): void
    {
        Cache::forget("comments:{$entityType}:{$entityId}");
        // Also clear any cached variations with options
        Cache::tags(["comments_{$entityType}_{$entityId}"])->flush();
    }
}
