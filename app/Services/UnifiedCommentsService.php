<?php

namespace App\Services;

use App\Services\Connectors\ConnectorFactory;
use App\Services\Connectors\Providers\TikTokConnector;
use App\Services\Connectors\Providers\TwitterConnector;
use App\Services\Connectors\Providers\LinkedInConnector;
use App\Services\Connectors\Providers\YouTubeConnector;
use App\Models\Core\Integration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UnifiedCommentsService
{
    protected $orgId;

    public function __construct($orgId)
    {
        $this->orgId = $orgId;
    }

    /**
     * Get all comments for organization (unified view)
     */
    public function getComments(array $filters = []): array
    {
        $query = DB::table('cmis_social.social_comments as c')
            ->leftJoin('cmis_social.social_posts as p', 'c.post_id', '=', 'p.post_id')
            ->where('c.org_id', $this->orgId)
            ->select('c.*', 'p.content as post_content', 'p.platform_post_id')
            ->orderBy('c.created_at', 'desc');

        // Apply filters
        if (isset($filters['platform'])) {
            $query->where('c.platform', $filters['platform']);
        }

        if (isset($filters['sentiment'])) {
            $query->where('c.sentiment', $filters['sentiment']);
        }

        if (isset($filters['is_replied'])) {
            if ($filters['is_replied']) {
                $query->whereNotNull('c.replied_at');
            } else {
                $query->whereNull('c.replied_at');
            }
        }

        if (isset($filters['is_hidden'])) {
            $query->where('c.is_hidden', $filters['is_hidden']);
        }

        if (isset($filters['post_id'])) {
            $query->where('c.post_id', $filters['post_id']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('c.comment_text', 'ILIKE', '%' . $filters['search'] . '%')
                  ->orWhere('c.author_name', 'ILIKE', '%' . $filters['search'] . '%');
            });
        }

        $perPage = $filters['per_page'] ?? 50;
        $page = $filters['page'] ?? 1;

        $total = $query->count();
        $comments = $query->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return [
            'data' => $comments,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
        ];
    }

    /**
     * Reply to comment
     */
    public function replyToComment(int $commentId, string $replyText, int $userId): array
    {
        // Get comment
        $comment = DB::table('cmis_social.social_comments')
            ->where('comment_id', $commentId)
            ->where('org_id', $this->orgId)
            ->first();

        if (!$comment) {
            throw new \Exception('Comment not found');
        }

        // Get post
        $post = DB::table('cmis_social.social_posts')
            ->where('post_id', $comment->post_id)
            ->first();

        if (!$post) {
            throw new \Exception('Post not found');
        }

        // Get integration
        $integration = DB::table('cmis.integrations')
            ->where('integration_id', $post->integration_id)
            ->first();

        if (!$integration) {
            throw new \Exception('Integration not found');
        }

        // Send reply based on platform
        $platformReply = $this->sendPlatformCommentReply($comment, $replyText, $integration, $post);

        if (!$platformReply['success']) {
            throw new \Exception($platformReply['error'] ?? 'Failed to send reply');
        }

        // Store reply in database
        $replyId = DB::table('cmis_social.social_comments')->insertGetId([
            'org_id' => $this->orgId,
            'post_id' => $comment->post_id,
            'platform' => $comment->platform,
            'platform_comment_id' => $platformReply['platform_comment_id'] ?? null,
            'author_name' => 'Page Admin',
            'author_id' => null,
            'comment_text' => $replyText,
            'parent_comment_id' => $comment->comment_id,
            'is_from_page' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'comment_id');

        // Mark original comment as replied
        DB::table('cmis_social.social_comments')
            ->where('comment_id', $commentId)
            ->update([
                'replied_at' => now(),
                'replied_by' => $userId,
                'updated_at' => now(),
            ]);

        return [
            'success' => true,
            'reply_id' => $replyId,
            'platform_comment_id' => $platformReply['platform_comment_id'] ?? null,
        ];
    }

    /**
     * Send reply to platform-specific API
     */
    protected function sendPlatformCommentReply($comment, string $replyText, $integration, $post): array
    {
        switch ($comment->platform) {
            case 'facebook':
            case 'meta':
                return $this->replyToFacebookComment($comment, $replyText, $integration);

            case 'instagram':
                return $this->replyToInstagramComment($comment, $replyText, $integration);

            case 'tiktok':
                return $this->replyToTikTokComment($comment, $replyText, $integration);

            case 'twitter':
            case 'x':
                return $this->replyToTwitterComment($comment, $replyText, $integration);

            case 'linkedin':
                return $this->replyToLinkedInComment($comment, $replyText, $integration);

            case 'youtube':
                return $this->replyToYouTubeComment($comment, $replyText, $integration);

            default:
                return ['success' => false, 'error' => 'Platform not supported'];
        }
    }

    /**
     * Reply to Facebook comment
     */
    protected function replyToFacebookComment($comment, string $replyText, $integration): array
    {
        try {
            $response = Http::post("https://graph.facebook.com/v19.0/{$comment->platform_comment_id}/comments", [
                'message' => $replyText,
                'access_token' => $integration->access_token,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'platform_comment_id' => $response->json()['id'] ?? null,
                ];
            }

            return ['success' => false, 'error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('Failed to reply to Facebook comment: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reply to Instagram comment
     */
    protected function replyToInstagramComment($comment, string $replyText, $integration): array
    {
        try {
            $response = Http::post("https://graph.facebook.com/v19.0/{$comment->platform_comment_id}/replies", [
                'message' => $replyText,
                'access_token' => $integration->access_token,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'platform_comment_id' => $response->json()['id'] ?? null,
                ];
            }

            return ['success' => false, 'error' => $response->body()];
        } catch (\Exception $e) {
            Log::error('Failed to reply to Instagram comment: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reply to TikTok comment
     */
    protected function replyToTikTokComment($comment, string $replyText, $integration): array
    {
        try {
            $integrationModel = Integration::find($integration->integration_id);
            if (!$integrationModel) {
                return ['success' => false, 'error' => 'Integration not found'];
            }

            $connector = app(TikTokConnector::class);
            $result = $connector->replyToComment(
                $integrationModel,
                $comment->platform_comment_id,
                $replyText
            );

            return [
                'success' => $result['success'] ?? true,
                'platform_comment_id' => $result['comment_id'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to reply to TikTok comment: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reply to Twitter/X comment
     */
    protected function replyToTwitterComment($comment, string $replyText, $integration): array
    {
        try {
            $integrationModel = Integration::find($integration->integration_id);
            if (!$integrationModel) {
                return ['success' => false, 'error' => 'Integration not found'];
            }

            $connector = app(TwitterConnector::class);
            $result = $connector->replyToComment(
                $integrationModel,
                $comment->platform_comment_id,
                $replyText
            );

            return [
                'success' => $result['success'] ?? true,
                'platform_comment_id' => $result['comment_id'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to reply to Twitter comment: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reply to LinkedIn comment
     */
    protected function replyToLinkedInComment($comment, string $replyText, $integration): array
    {
        try {
            $integrationModel = Integration::find($integration->integration_id);
            if (!$integrationModel) {
                return ['success' => false, 'error' => 'Integration not found'];
            }

            $connector = app(LinkedInConnector::class);
            $result = $connector->replyToComment(
                $integrationModel,
                $comment->platform_comment_id,
                $replyText
            );

            return [
                'success' => $result['success'] ?? true,
                'platform_comment_id' => $result['comment_id'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to reply to LinkedIn comment: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reply to YouTube comment
     */
    protected function replyToYouTubeComment($comment, string $replyText, $integration): array
    {
        try {
            $integrationModel = Integration::find($integration->integration_id);
            if (!$integrationModel) {
                return ['success' => false, 'error' => 'Integration not found'];
            }

            $connector = app(YouTubeConnector::class);
            $result = $connector->replyToComment(
                $integrationModel,
                $comment->platform_comment_id,
                $replyText
            );

            return [
                'success' => $result['success'] ?? true,
                'platform_comment_id' => $result['comment_id'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to reply to YouTube comment: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Hide comment
     */
    public function hideComment(int $commentId): array
    {
        $comment = DB::table('cmis_social.social_comments')
            ->where('comment_id', $commentId)
            ->where('org_id', $this->orgId)
            ->first();

        if (!$comment) {
            return ['success' => false, 'error' => 'Comment not found'];
        }

        // Get integration
        $post = DB::table('cmis_social.social_posts')->where('post_id', $comment->post_id)->first();
        $integration = DB::table('cmis.integrations')
            ->where('integration_id', $post->integration_id)
            ->first();

        // Hide on platform
        $result = $this->hidePlatformComment($comment, $integration);

        if ($result['success']) {
            // Update in database
            DB::table('cmis_social.social_comments')
                ->where('comment_id', $commentId)
                ->update([
                    'is_hidden' => true,
                    'hidden_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return $result;
    }

    /**
     * Hide comment on platform
     */
    protected function hidePlatformComment($comment, $integration): array
    {
        try {
            switch ($comment->platform) {
                case 'facebook':
                case 'meta':
                    $response = Http::post("https://graph.facebook.com/v19.0/{$comment->platform_comment_id}", [
                        'is_hidden' => true,
                        'access_token' => $integration->access_token,
                    ]);
                    return ['success' => $response->successful()];

                case 'instagram':
                    $response = Http::delete("https://graph.facebook.com/v19.0/{$comment->platform_comment_id}", [
                        'access_token' => $integration->access_token,
                    ]);
                    return ['success' => $response->successful()];

                default:
                    return ['success' => false, 'error' => 'Platform not supported'];
            }
        } catch (\Exception $e) {
            Log::error('Failed to hide comment: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete comment
     */
    public function deleteComment(int $commentId): array
    {
        $comment = DB::table('cmis_social.social_comments')
            ->where('comment_id', $commentId)
            ->where('org_id', $this->orgId)
            ->first();

        if (!$comment) {
            return ['success' => false, 'error' => 'Comment not found'];
        }

        // Get integration
        $post = DB::table('cmis_social.social_posts')->where('post_id', $comment->post_id)->first();
        $integration = DB::table('cmis.integrations')
            ->where('integration_id', $post->integration_id)
            ->first();

        // Delete on platform
        $result = $this->deletePlatformComment($comment, $integration);

        if ($result['success']) {
            // Delete from database
            DB::table('cmis_social.social_comments')
                ->where('comment_id', $commentId)
                ->delete();
        }

        return $result;
    }

    /**
     * Delete comment on platform
     */
    protected function deletePlatformComment($comment, $integration): array
    {
        try {
            $response = Http::delete("https://graph.facebook.com/v19.0/{$comment->platform_comment_id}", [
                'access_token' => $integration->access_token,
            ]);

            return ['success' => $response->successful()];
        } catch (\Exception $e) {
            Log::error('Failed to delete comment: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Like comment
     */
    public function likeComment(int $commentId): array
    {
        $comment = DB::table('cmis_social.social_comments')
            ->where('comment_id', $commentId)
            ->where('org_id', $this->orgId)
            ->first();

        if (!$comment) {
            return ['success' => false, 'error' => 'Comment not found'];
        }

        // Get integration
        $post = DB::table('cmis_social.social_posts')->where('post_id', $comment->post_id)->first();
        $integration = DB::table('cmis.integrations')
            ->where('integration_id', $post->integration_id)
            ->first();

        try {
            $response = Http::post("https://graph.facebook.com/v19.0/{$comment->platform_comment_id}/likes", [
                'access_token' => $integration->access_token,
            ]);

            return ['success' => $response->successful()];
        } catch (\Exception $e) {
            Log::error('Failed to like comment: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get comments statistics
     */
    public function getStatistics(): array
    {
        $total = DB::table('cmis_social.social_comments')
            ->where('org_id', $this->orgId)
            ->count();

        $unreplied = DB::table('cmis_social.social_comments')
            ->where('org_id', $this->orgId)
            ->whereNull('replied_at')
            ->where('is_from_page', false)
            ->count();

        $replied = DB::table('cmis_social.social_comments')
            ->where('org_id', $this->orgId)
            ->whereNotNull('replied_at')
            ->count();

        $hidden = DB::table('cmis_social.social_comments')
            ->where('org_id', $this->orgId)
            ->where('is_hidden', true)
            ->count();

        $bySentiment = DB::table('cmis_social.social_comments')
            ->where('org_id', $this->orgId)
            ->select('sentiment', DB::raw('COUNT(*) as count'))
            ->groupBy('sentiment')
            ->get();

        $byPlatform = DB::table('cmis_social.social_comments')
            ->where('org_id', $this->orgId)
            ->select('platform', DB::raw('COUNT(*) as count'))
            ->groupBy('platform')
            ->get();

        $avgResponseTime = DB::table('cmis_social.social_comments')
            ->where('org_id', $this->orgId)
            ->whereNotNull('replied_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (replied_at - created_at))) as avg_seconds')
            ->first();

        return [
            'total' => $total,
            'unreplied' => $unreplied,
            'replied' => $replied,
            'hidden' => $hidden,
            'avg_response_time_minutes' => round(($avgResponseTime->avg_seconds ?? 0) / 60, 2),
            'by_sentiment' => $bySentiment,
            'by_platform' => $byPlatform,
        ];
    }

    /**
     * Bulk actions
     */
    public function bulkAction(string $action, array $commentIds): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($commentIds as $commentId) {
            try {
                switch ($action) {
                    case 'hide':
                        $result = $this->hideComment($commentId);
                        break;
                    case 'delete':
                        $result = $this->deleteComment($commentId);
                        break;
                    case 'like':
                        $result = $this->likeComment($commentId);
                        break;
                    default:
                        $result = ['success' => false, 'error' => 'Invalid action'];
                }

                if ($result['success']) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Comment {$commentId}: " . ($result['error'] ?? 'Unknown error');
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Comment {$commentId}: " . $e->getMessage();
            }
        }

        return $results;
    }
}
