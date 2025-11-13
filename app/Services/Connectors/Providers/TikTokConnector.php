<?php

namespace App\Services\Connectors\Providers;

use App\Services\Connectors\AbstractConnector;
use App\Models\Core\Integration;
use App\Models\Creative\ContentItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Connector for TikTok for Business.
 * Handles posts, messages, comments, and ads.
 */
class TikTokConnector extends AbstractConnector
{
    protected string $platform = 'tiktok';
    protected string $baseUrl = 'https://business-api.tiktok.com';
    protected string $apiVersion = 'v1.3';

    public function __construct()
    {
        $this->baseUrl = $this->baseUrl . '/' . $this->apiVersion;
    }

    // ========================================
    // Authentication & Connection
    // ========================================

    public function getAuthUrl(array $options = []): string
    {
        $params = [
            'client_key' => config('services.tiktok.client_key'),
            'response_type' => 'code',
            'scope' => 'user.info.basic,video.list,video.publish',
            'redirect_uri' => config('services.tiktok.redirect_uri'),
            'state' => $options['state'] ?? bin2hex(random_bytes(16)),
        ];

        return 'https://www.tiktok.com/auth/authorize/?' . http_build_query($params);
    }

    public function connect(string $authCode, array $options = []): Integration
    {
        $response = \Http::post('https://open-api.tiktok.com/oauth/access_token/', [
            'client_key' => config('services.tiktok.client_key'),
            'client_secret' => config('services.tiktok.client_secret'),
            'code' => $authCode,
            'grant_type' => 'authorization_code',
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to get TikTok access token: ' . $response->body());
        }

        $data = $response->json()['data'];
        $accessToken = $data['access_token'];
        $openId = $data['open_id'];
        $expiresIn = $data['expires_in'];

        // Get user info
        $userInfo = \Http::post('https://open-api.tiktok.com/user/info/', [
            'access_token' => $accessToken,
            'open_id' => $openId,
        ])->json()['data']['user'] ?? [];

        $integration = Integration::updateOrCreate(
            [
                'org_id' => $options['org_id'],
                'platform' => 'tiktok',
                'external_account_id' => $openId,
            ],
            [
                'access_token' => encrypt($accessToken),
                'token_expires_at' => now()->addSeconds($expiresIn),
                'is_active' => true,
                'settings' => [
                    'open_id' => $openId,
                    'display_name' => $userInfo['display_name'] ?? null,
                    'avatar_url' => $userInfo['avatar_url'] ?? null,
                ],
            ]
        );

        return $integration;
    }

    public function disconnect(Integration $integration): bool
    {
        $integration->update([
            'is_active' => false,
            'access_token' => null,
            'token_expires_at' => null,
        ]);

        return true;
    }

    public function refreshToken(Integration $integration): Integration
    {
        // TikTok tokens are long-lived and don't have refresh tokens
        return $integration;
    }

    // ========================================
    // Sync Operations
    // ========================================

    public function syncCampaigns(Integration $integration, array $options = []): Collection
    {
        $advertiserId = $options['advertiser_id'] ?? $integration->settings['advertiser_id'] ?? null;

        if (!$advertiserId) {
            return collect();
        }

        $campaigns = collect();

        $response = $this->makeRequest($integration, 'GET', '/campaign/get/', [
            'advertiser_id' => $advertiserId,
            'page_size' => 100,
        ]);

        foreach ($response['data']['list'] ?? [] as $campaign) {
            $campaigns->push($this->storeCampaign($integration, $campaign));
        }

        $this->logSync($integration, 'campaigns', $campaigns->count());

        return $campaigns;
    }

    public function syncPosts(Integration $integration, array $options = []): Collection
    {
        $openId = $integration->settings['open_id'] ?? null;

        if (!$openId) {
            return collect();
        }

        $posts = collect();

        $response = $this->makeRequest($integration, 'POST', '/video/list/', [
            'open_id' => $openId,
            'cursor' => 0,
            'max_count' => 20,
        ]);

        foreach ($response['data']['videos'] ?? [] as $video) {
            $posts->push($this->storePost($integration, $video));
        }

        $this->logSync($integration, 'posts', $posts->count());

        return $posts;
    }

    public function syncComments(Integration $integration, array $options = []): Collection
    {
        $comments = collect();

        $postIds = $options['post_ids'] ?? [];

        if (empty($postIds)) {
            $recentPosts = DB::table('cmis_social.social_posts')
                ->where('integration_id', $integration->integration_id)
                ->where('created_at', '>=', now()->subDays(7))
                ->pluck('platform_post_id');

            $postIds = $recentPosts->toArray();
        }

        foreach ($postIds as $postId) {
            $response = $this->makeRequest($integration, 'POST', '/comment/list/', [
                'video_id' => $postId,
                'count' => 50,
            ]);

            foreach ($response['data']['comments'] ?? [] as $comment) {
                $comments->push($this->storeComment($integration, $postId, $comment));
            }
        }

        $this->logSync($integration, 'comments', $comments->count());

        return $comments;
    }

    public function syncMessages(Integration $integration, array $options = []): Collection
    {
        // TikTok doesn't have a public messages API yet
        return collect();
    }

    public function getAccountMetrics(Integration $integration): Collection
    {
        $openId = $integration->settings['open_id'] ?? null;

        if (!$openId) {
            return collect();
        }

        $response = $this->makeRequest($integration, 'POST', '/user/info/', [
            'open_id' => $openId,
        ]);

        return collect($response['data']['user'] ?? []);
    }

    // ========================================
    // Publishing & Scheduling
    // ========================================

    public function publishPost(Integration $integration, ContentItem $item): string
    {
        $openId = $integration->settings['open_id'] ?? null;

        if (!$openId) {
            throw new \Exception('TikTok open_id not configured');
        }

        // Step 1: Init upload
        $initResponse = $this->makeRequest($integration, 'POST', '/share/video/upload/', [
            'open_id' => $openId,
        ]);

        $uploadUrl = $initResponse['data']['upload_url'];

        // Step 2: Upload video
        // Note: Requires actual video file upload

        // Step 3: Publish
        $response = $this->makeRequest($integration, 'POST', '/video/publish/', [
            'open_id' => $openId,
            'video_id' => $initResponse['data']['video_id'],
            'text' => $item->content,
        ]);

        return $response['data']['video_id'];
    }

    public function schedulePost(Integration $integration, ContentItem $item, Carbon $scheduledTime): string
    {
        // TikTok doesn't support native scheduling via API
        throw new \Exception('TikTok does not support scheduling via API');
    }

    // ========================================
    // Messaging & Engagement
    // ========================================

    public function sendMessage(Integration $integration, string $conversationId, string $messageText, array $options = []): array
    {
        return ['success' => false, 'error' => 'TikTok messages API not available'];
    }

    public function replyToComment(Integration $integration, string $commentId, string $replyText): array
    {
        $response = $this->makeRequest($integration, 'POST', '/comment/reply/', [
            'comment_id' => $commentId,
            'text' => $replyText,
        ]);

        return [
            'success' => true,
            'comment_id' => $response['data']['comment_id'] ?? null,
        ];
    }

    public function hideComment(Integration $integration, string $commentId, bool $hide = true): bool
    {
        // TikTok doesn't have hide comment API
        return false;
    }

    public function deleteComment(Integration $integration, string $commentId): bool
    {
        $this->makeRequest($integration, 'POST', '/comment/delete/', [
            'comment_id' => $commentId,
        ]);

        return true;
    }

    public function likeComment(Integration $integration, string $commentId): bool
    {
        return false;
    }

    // ========================================
    // Ad Campaign Management
    // ========================================

    public function createAdCampaign(Integration $integration, array $campaignData): array
    {
        $advertiserId = $integration->settings['advertiser_id'] ?? null;

        if (!$advertiserId) {
            throw new \Exception('TikTok advertiser_id not configured');
        }

        $response = $this->makeRequest($integration, 'POST', '/campaign/create/', [
            'advertiser_id' => $advertiserId,
            'campaign_name' => $campaignData['campaign_name'],
            'objective_type' => $campaignData['objective'] ?? 'TRAFFIC',
            'budget_mode' => 'BUDGET_MODE_DAY',
            'budget' => $campaignData['daily_budget'] ?? 5000,
        ]);

        return [
            'success' => true,
            'campaign_id' => $response['data']['campaign_id'],
        ];
    }

    public function updateAdCampaign(Integration $integration, string $campaignId, array $updates): array
    {
        $advertiserId = $integration->settings['advertiser_id'] ?? null;

        $this->makeRequest($integration, 'POST', '/campaign/update/', array_merge([
            'advertiser_id' => $advertiserId,
            'campaign_id' => $campaignId,
        ], $updates));

        return ['success' => true];
    }

    public function getAdCampaignMetrics(Integration $integration, string $campaignId, array $options = []): Collection
    {
        $advertiserId = $integration->settings['advertiser_id'] ?? null;

        $response = $this->makeRequest($integration, 'GET', '/reports/integrated/get/', [
            'advertiser_id' => $advertiserId,
            'report_type' => 'BASIC',
            'data_level' => 'AUCTION_CAMPAIGN',
            'dimensions' => ['campaign_id'],
            'filters' => [['field' => 'campaign_id', 'operator' => 'IN', 'value' => [$campaignId]]],
            'metrics' => ['spend', 'impressions', 'clicks', 'ctr', 'cpc'],
        ]);

        return collect($response['data']['list'] ?? []);
    }

    // ========================================
    // Helper Methods
    // ========================================

    private function storeCampaign(Integration $integration, array $campaign): int
    {
        return $this->storeData('cmis_ads.ad_campaigns', [
            'org_id' => $integration->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'tiktok',
            'platform_campaign_id' => $campaign['campaign_id'],
            'campaign_name' => $campaign['campaign_name'],
            'objective' => $campaign['objective_type'] ?? null,
            'status' => $campaign['operation_status'] ?? 'ENABLE',
            'daily_budget' => $campaign['budget'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ], ['platform_campaign_id' => $campaign['campaign_id']]);
    }

    private function storePost(Integration $integration, array $video): int
    {
        return $this->storeData('cmis_social.social_posts', [
            'org_id' => $integration->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'tiktok',
            'platform_post_id' => $video['id'],
            'post_type' => 'video',
            'content' => $video['title'] ?? null,
            'media_urls' => json_encode([$video['cover_image_url'] ?? null]),
            'permalink' => $video['share_url'] ?? null,
            'published_at' => isset($video['create_time']) ? Carbon::createFromTimestamp($video['create_time']) : now(),
            'metrics' => json_encode([
                'views' => $video['view_count'] ?? 0,
                'likes' => $video['like_count'] ?? 0,
                'comments' => $video['comment_count'] ?? 0,
                'shares' => $video['share_count'] ?? 0,
            ]),
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ], ['platform_post_id' => $video['id']]);
    }

    private function storeComment(Integration $integration, string $postId, array $comment): int
    {
        return $this->storeData('cmis_social.social_comments', [
            'org_id' => $integration->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'tiktok',
            'platform_comment_id' => $comment['id'],
            'post_id' => DB::table('cmis_social.social_posts')
                ->where('platform_post_id', $postId)
                ->value('post_id'),
            'comment_text' => $comment['text'],
            'commenter_name' => $comment['user']['display_name'] ?? null,
            'commenter_id' => $comment['user']['open_id'] ?? null,
            'likes_count' => $comment['like_count'] ?? 0,
            'created_at' => isset($comment['create_time']) ? Carbon::createFromTimestamp($comment['create_time']) : now(),
        ], ['platform_comment_id' => $comment['id']]);
    }
}
