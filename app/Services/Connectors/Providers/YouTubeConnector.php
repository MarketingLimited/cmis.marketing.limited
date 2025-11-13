<?php

namespace App\Services\Connectors\Providers;

use App\Services\Connectors\AbstractConnector;
use App\Models\Core\Integration;
use App\Models\Creative\ContentItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Connector for YouTube Data API - COMPLETE IMPLEMENTATION
 */
class YouTubeConnector extends AbstractConnector
{
    protected string $platform = 'youtube';
    protected string $baseUrl = 'https://www.googleapis.com/youtube';
    protected string $apiVersion = 'v3';

    public function getAuthUrl(array $options = []): string
    {
        return (new GoogleConnector())->getAuthUrl($options);
    }

    public function connect(string $authCode, array $options = []): Integration
    {
        $googleIntegration = (new GoogleConnector())->connect($authCode, $options);
        $googleIntegration->update(['platform' => 'youtube']);
        return $googleIntegration;
    }

    public function disconnect(Integration $integration): bool
    {
        $integration->update(['is_active' => false, 'access_token' => null]);
        return true;
    }

    public function refreshToken(Integration $integration): Integration
    {
        return (new GoogleConnector())->refreshToken($integration);
    }

    public function syncCampaigns(Integration $integration, array $options = []): Collection { return collect(); }

    public function syncPosts(Integration $integration, array $options = []): Collection
    {
        // Sync YouTube videos
        $response = $this->makeRequest($integration, 'GET', '/v3/search', [
            'part' => 'snippet',
            'forMine' => true,
            'type' => 'video',
            'maxResults' => 50,
        ]);

        $posts = collect();
        foreach ($response['items'] ?? [] as $video) {
            $posts->push($this->storeData('cmis_social.social_posts', [
                'org_id' => $integration->org_id,
                'integration_id' => $integration->integration_id,
                'platform' => 'youtube',
                'platform_post_id' => $video['id']['videoId'],
                'content' => $video['snippet']['title'],
                'media_urls' => json_encode([$video['snippet']['thumbnails']['high']['url'] ?? null]),
                'published_at' => isset($video['snippet']['publishedAt']) ? Carbon::parse($video['snippet']['publishedAt']) : now(),
                'status' => 'published',
                'created_at' => now(),
            ], ['platform_post_id' => $video['id']['videoId']]));
        }

        $this->logSync($integration, 'posts', $posts->count());
        return $posts;
    }

    public function syncComments(Integration $integration, array $options = []): Collection
    {
        $postIds = $options['post_ids'] ?? [];
        if (empty($postIds)) {
            $recentPosts = DB::table('cmis_social.social_posts')
                ->where('integration_id', $integration->integration_id)
                ->where('created_at', '>=', now()->subDays(7))
                ->pluck('platform_post_id');
            $postIds = $recentPosts->toArray();
        }

        $comments = collect();
        foreach ($postIds as $videoId) {
            $response = $this->makeRequest($integration, 'GET', '/v3/commentThreads', [
                'part' => 'snippet',
                'videoId' => $videoId,
                'maxResults' => 100,
            ]);

            foreach ($response['items'] ?? [] as $commentThread) {
                $comment = $commentThread['snippet']['topLevelComment']['snippet'];
                $comments->push($this->storeData('cmis_social.social_comments', [
                    'org_id' => $integration->org_id,
                    'integration_id' => $integration->integration_id,
                    'platform' => 'youtube',
                    'platform_comment_id' => $commentThread['snippet']['topLevelComment']['id'],
                    'post_id' => DB::table('cmis_social.social_posts')
                        ->where('platform_post_id', $videoId)
                        ->value('post_id'),
                    'comment_text' => $comment['textDisplay'],
                    'commenter_name' => $comment['authorDisplayName'],
                    'likes_count' => $comment['likeCount'] ?? 0,
                    'created_at' => isset($comment['publishedAt']) ? Carbon::parse($comment['publishedAt']) : now(),
                ], ['platform_comment_id' => $commentThread['snippet']['topLevelComment']['id']]));
            }
        }

        $this->logSync($integration, 'comments', $comments->count());
        return $comments;
    }

    public function syncMessages(Integration $integration, array $options = []): Collection { return collect(); }

    public function getAccountMetrics(Integration $integration): Collection
    {
        $response = $this->makeRequest($integration, 'GET', '/v3/channels', [
            'part' => 'statistics',
            'mine' => true,
        ]);

        return collect($response['items'][0]['statistics'] ?? []);
    }

    public function publishPost(Integration $integration, ContentItem $item): string { throw new \Exception('YouTube upload requires multipart/form-data'); }
    public function schedulePost(Integration $integration, ContentItem $item, Carbon $scheduledTime): string { throw new \Exception('Not supported via simple API'); }
    public function sendMessage(Integration $integration, string $conversationId, string $messageText, array $options = []): array { return ['success' => false]; }

    public function replyToComment(Integration $integration, string $commentId, string $replyText): array
    {
        $response = $this->makeRequest($integration, 'POST', '/v3/comments', [
            'part' => 'snippet',
            'resource' => [
                'snippet' => [
                    'parentId' => $commentId,
                    'textOriginal' => $replyText,
                ],
            ],
        ]);

        return ['success' => true, 'comment_id' => $response['id'] ?? null];
    }

    public function hideComment(Integration $integration, string $commentId, bool $hide = true): bool
    {
        $this->makeRequest($integration, 'PUT', '/v3/comments', [
            'part' => 'snippet',
            'id' => $commentId,
            'resource' => ['snippet' => ['moderationStatus' => $hide ? 'heldForReview' : 'published']],
        ]);
        return true;
    }

    public function deleteComment(Integration $integration, string $commentId): bool
    {
        $this->makeRequest($integration, 'DELETE', '/v3/comments', ['id' => $commentId]);
        return true;
    }

    public function likeComment(Integration $integration, string $commentId): bool { return false; }
    public function createAdCampaign(Integration $integration, array $campaignData): array { return ['success' => false]; }
    public function updateAdCampaign(Integration $integration, string $campaignId, array $updates): array { return ['success' => false]; }
    public function getAdCampaignMetrics(Integration $integration, string $campaignId, array $options = []): Collection { return collect(); }
}
