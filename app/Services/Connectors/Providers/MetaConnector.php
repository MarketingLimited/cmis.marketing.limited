<?php

namespace App\Services\Connectors\Providers;

use App\Services\Connectors\AbstractConnector;
use App\Models\Core\Integration;
use App\Models\Creative\ContentItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Connector for Meta platforms (Facebook, Instagram).
 * Handles all interactions with Meta Graph API.
 */
class MetaConnector extends AbstractConnector
{
    protected string $platform = 'meta';
    protected string $baseUrl = 'https://graph.facebook.com';
    protected string $apiVersion = 'v19.0';

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
            'client_id' => config('services.meta.client_id'),
            'redirect_uri' => config('services.meta.redirect_uri'),
            'scope' => implode(',', [
                // Insights & Analytics
                'read_insights',
                'attribution_read',

                // Publishing
                'publish_video',

                // Catalog & Commerce
                'catalog_management',
                'commerce_account_read_settings',
                'commerce_account_manage_orders',
                'commerce_account_read_orders',
                'commerce_account_read_reports',

                // Advanced Features
                'private_computation_access',

                // Threads
                'threads_business_basic',

                // Pages - Content & Management
                'pages_show_list',
                'pages_read_engagement',
                'pages_read_user_content',
                'pages_manage_cta',
                'pages_manage_instant_articles',
                'pages_manage_metadata',
                'pages_manage_ads',
                'pages_manage_posts',
                'pages_manage_engagement',
                'pages_messaging',
                'pages_utility_messaging',
                'read_page_mailboxes',
                'page_events',
                'paid_marketing_messages',

                // Instagram
                'instagram_basic',
                'instagram_manage_comments',
                'instagram_manage_insights',
                'instagram_content_publish',
                'instagram_manage_messages',
                'instagram_shopping_tag_products',
                'instagram_branded_content_brand',
                'instagram_branded_content_ads_brand',

                // Ads
                'ads_management',
                'ads_read',

                // Business
                'business_management',
                'manage_app_solution',

                // Leads
                'leads_retrieval',

                // WhatsApp
                'whatsapp_business_management',
                'whatsapp_business_messaging',
                'whatsapp_business_manage_events',

                // Profile
                'public_profile',
            ]),
            'response_type' => 'code',
            'state' => $options['state'] ?? bin2hex(random_bytes(16)),
        ];

        return 'https://www.facebook.com/' . $this->apiVersion . '/dialog/oauth?' . http_build_query($params);
    }

    public function connect(string $authCode, array $options = []): Integration
    {
        // Exchange code for access token
        $response = $this->makeRequest(
            new Integration(['access_token' => '']), // temp integration
            'GET',
            '/oauth/access_token',
            [
                'client_id' => config('services.meta.client_id'),
                'client_secret' => config('services.meta.client_secret'),
                'redirect_uri' => config('services.meta.redirect_uri'),
                'code' => $authCode,
            ]
        );

        $accessToken = $response['access_token'];

        // Get long-lived token
        $longLivedResponse = $this->makeRequest(
            new Integration(['access_token' => $accessToken]),
            'GET',
            '/oauth/access_token',
            [
                'grant_type' => 'fb_exchange_token',
                'client_id' => config('services.meta.client_id'),
                'client_secret' => config('services.meta.client_secret'),
                'fb_exchange_token' => $accessToken,
            ]
        );

        $longLivedToken = $longLivedResponse['access_token'];
        $expiresIn = $longLivedResponse['expires_in'] ?? 5184000; // 60 days default

        // Get user/page info
        $userInfo = $this->makeRequest(
            new Integration(['access_token' => $longLivedToken]),
            'GET',
            '/me',
            ['fields' => 'id,name,email']
        );

        // Create or update integration
        $integration = Integration::updateOrCreate(
            [
                'org_id' => $options['org_id'],
                'platform' => 'meta',
                'external_account_id' => $userInfo['id'],
            ],
            [
                'access_token' => encrypt($longLivedToken),
                'refresh_token' => null, // Meta doesn't use refresh tokens
                'token_expires_at' => now()->addSeconds($expiresIn),
                'is_active' => true,
                'settings' => [
                    'account_name' => $userInfo['name'],
                    'account_email' => $userInfo['email'] ?? null,
                ],
            ]
        );

        return $integration;
    }

    public function disconnect(Integration $integration): bool
    {
        try {
            // Revoke permissions
            $this->makeRequest($integration, 'DELETE', '/me/permissions');
        } catch (\Exception $e) {
            // Continue even if revoke fails
        }

        $integration->update([
            'is_active' => false,
            'access_token' => null,
            'token_expires_at' => null,
        ]);

        return true;
    }

    public function refreshToken(Integration $integration): Integration
    {
        // Meta long-lived tokens auto-refresh on use, manual refresh not needed
        // But we can exchange for a new long-lived token if needed
        return $integration;
    }

    // ========================================
    // Sync Operations
    // ========================================

    public function syncCampaigns(Integration $integration, array $options = []): Collection
    {
        $adAccountId = $options['ad_account_id'] ?? $integration->settings['ad_account_id'] ?? null;

        if (!$adAccountId) {
            throw new \Exception('Ad Account ID is required for syncing campaigns');
        }

        $campaigns = collect();
        $endpoint = "/act_{$adAccountId}/campaigns";

        $response = $this->makeRequest($integration, 'GET', $endpoint, [
            'fields' => 'id,name,objective,status,daily_budget,lifetime_budget,created_time,updated_time,insights{spend,impressions,clicks,ctr,cpc,cpm,reach}',
            'limit' => 100,
        ]);

        foreach ($response['data'] ?? [] as $campaign) {
            $campaigns->push($this->storeCampaign($integration, $campaign));
        }

        $this->logSync($integration, 'campaigns', $campaigns->count());

        return $campaigns;
    }

    public function syncPosts(Integration $integration, array $options = []): Collection
    {
        $pageId = $options['page_id'] ?? $integration->settings['page_id'] ?? null;

        if (!$pageId) {
            throw new \Exception('Page ID is required for syncing posts');
        }

        $posts = collect();
        $since = $options['since'] ?? now()->subDays(30);

        $response = $this->makeRequest($integration, 'GET', "/{$pageId}/posts", [
            'fields' => 'id,message,created_time,permalink_url,attachments,reactions.summary(true),comments.summary(true),shares',
            'since' => $since->timestamp,
            'limit' => 100,
        ]);

        foreach ($response['data'] ?? [] as $post) {
            $posts->push($this->storePost($integration, $post));
        }

        $this->logSync($integration, 'posts', $posts->count());

        return $posts;
    }

    public function syncComments(Integration $integration, array $options = []): Collection
    {
        $postIds = $options['post_ids'] ?? [];
        $comments = collect();

        if (empty($postIds)) {
            // Get recent posts first
            $recentPosts = DB::table('cmis_social.social_posts')
                ->where('integration_id', $integration->integration_id)
                ->where('created_at', '>=', now()->subDays(7))
                ->pluck('platform_post_id');

            $postIds = $recentPosts->toArray();
        }

        foreach ($postIds as $postId) {
            $response = $this->makeRequest($integration, 'GET', "/{$postId}/comments", [
                'fields' => 'id,message,from,created_time,like_count,is_hidden',
                'limit' => 100,
            ]);

            foreach ($response['data'] ?? [] as $comment) {
                $comments->push($this->storeComment($integration, $postId, $comment));
            }
        }

        $this->logSync($integration, 'comments', $comments->count());

        return $comments;
    }

    public function syncMessages(Integration $integration, array $options = []): Collection
    {
        $pageId = $options['page_id'] ?? $integration->settings['page_id'] ?? null;

        if (!$pageId) {
            throw new \Exception('Page ID is required for syncing messages');
        }

        $messages = collect();

        $response = $this->makeRequest($integration, 'GET', "/{$pageId}/conversations", [
            'fields' => 'id,messages{id,message,from,created_time}',
            'limit' => 50,
        ]);

        foreach ($response['data'] ?? [] as $conversation) {
            foreach ($conversation['messages']['data'] ?? [] as $message) {
                $messages->push($this->storeMessage($integration, $conversation['id'], $message));
            }
        }

        $this->logSync($integration, 'messages', $messages->count());

        return $messages;
    }

    public function getAccountMetrics(Integration $integration): Collection
    {
        $pageId = $integration->settings['page_id'] ?? null;

        if (!$pageId) {
            return collect();
        }

        $response = $this->makeRequest($integration, 'GET', "/{$pageId}", [
            'fields' => 'followers_count,fan_count,engagement',
        ]);

        return collect($response);
    }

    // ========================================
    // Publishing & Scheduling
    // ========================================

    public function publishPost(Integration $integration, ContentItem $item): string
    {
        $pageId = $integration->settings['page_id'] ?? null;

        if (!$pageId) {
            throw new \Exception('Page ID is required for publishing');
        }

        // Check if we have media to upload
        $mediaUrls = is_array($item->media_urls) ? $item->media_urls : [];

        if (!empty($mediaUrls)) {
            // Determine media type from first URL
            $firstUrl = $mediaUrls[0];
            $mediaType = $this->detectMediaType($firstUrl);

            if ($mediaType === 'image') {
                return $this->publishImage($integration, $pageId, $item->content, $mediaUrls);
            } elseif ($mediaType === 'video') {
                return $this->publishVideo($integration, $pageId, $item->content, $firstUrl);
            } else {
                // Fallback to link post if media type is unknown
                $data = [
                    'message' => $item->content,
                    'link' => $firstUrl,
                ];
            }
        } else {
            // Text-only post
            $data = [
                'message' => $item->content,
            ];
        }

        if (isset($data)) {
            $response = $this->makeRequest($integration, 'POST', "/{$pageId}/feed", $data);
            return $response['id'];
        }

        throw new \Exception('Unable to publish post');
    }

    public function schedulePost(Integration $integration, ContentItem $item, Carbon $scheduledTime): string
    {
        $pageId = $integration->settings['page_id'] ?? null;

        if (!$pageId) {
            throw new \Exception('Page ID is required for scheduling');
        }

        $data = [
            'message' => $item->content,
            'published' => false,
            'scheduled_publish_time' => $scheduledTime->timestamp,
        ];

        $response = $this->makeRequest($integration, 'POST', "/{$pageId}/feed", $data);

        return $response['id'];
    }

    /**
     * Publish an image or multiple images to Facebook/Instagram
     *
     * @param Integration $integration
     * @param string $pageId
     * @param string $message
     * @param array $imageUrls
     * @return string The post ID
     */
    protected function publishImage(Integration $integration, string $pageId, string $message, array $imageUrls): string
    {
        if (count($imageUrls) === 1) {
            // Single image post
            $data = [
                'message' => $message,
                'url' => $imageUrls[0], // Meta will fetch and upload the image from this URL
            ];

            $response = $this->makeRequest($integration, 'POST', "/{$pageId}/photos", $data);
            return $response['post_id'] ?? $response['id'];
        } else {
            // Multiple images (carousel/album)
            // First, upload images without publishing
            $photoIds = [];
            foreach ($imageUrls as $imageUrl) {
                $photoResponse = $this->makeRequest($integration, 'POST', "/{$pageId}/photos", [
                    'url' => $imageUrl,
                    'published' => false, // Don't publish yet
                ]);
                $photoIds[] = ['media_fbid' => $photoResponse['id']];
            }

            // Then create the multi-photo post
            $response = $this->makeRequest($integration, 'POST', "/{$pageId}/feed", [
                'message' => $message,
                'attached_media' => json_encode($photoIds),
            ]);

            return $response['id'];
        }
    }

    /**
     * Publish a video to Facebook/Instagram
     *
     * @param Integration $integration
     * @param string $pageId
     * @param string $message
     * @param string $videoUrl
     * @return string The post ID
     */
    protected function publishVideo(Integration $integration, string $pageId, string $message, string $videoUrl): string
    {
        // For videos, Meta Graph API supports uploading from URL
        $data = [
            'description' => $message,
            'file_url' => $videoUrl, // Meta will fetch and upload the video from this URL
        ];

        $response = $this->makeRequest($integration, 'POST', "/{$pageId}/videos", $data);
        return $response['id'];
    }

    /**
     * Detect media type from URL
     *
     * @param string $url
     * @return string 'image', 'video', or 'unknown'
     */
    protected function detectMediaType(string $url): string
    {
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        $videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mkv', '3gp', 'm4v'];

        if (in_array($extension, $imageExtensions)) {
            return 'image';
        } elseif (in_array($extension, $videoExtensions)) {
            return 'video';
        }

        // Try to detect from URL patterns or content-type (fallback)
        if (preg_match('/\.(jpe?g|png|gif|webp|bmp)/i', $url)) {
            return 'image';
        } elseif (preg_match('/\.(mp4|mov|avi|webm)/i', $url)) {
            return 'video';
        }

        return 'unknown';
    }

    // ========================================
    // Messaging & Engagement
    // ========================================

    public function sendMessage(Integration $integration, string $conversationId, string $messageText, array $options = []): array
    {
        $data = [
            'recipient' => ['id' => $conversationId],
            'message' => ['text' => $messageText],
        ];

        $response = $this->makeRequest($integration, 'POST', '/me/messages', $data);

        return [
            'success' => true,
            'message_id' => $response['message_id'] ?? null,
        ];
    }

    public function replyToComment(Integration $integration, string $commentId, string $replyText): array
    {
        $data = ['message' => $replyText];

        $response = $this->makeRequest($integration, 'POST', "/{$commentId}/comments", $data);

        return [
            'success' => true,
            'comment_id' => $response['id'] ?? null,
        ];
    }

    public function hideComment(Integration $integration, string $commentId, bool $hide = true): bool
    {
        $data = ['is_hidden' => $hide];

        $this->makeRequest($integration, 'POST', "/{$commentId}", $data);

        return true;
    }

    public function deleteComment(Integration $integration, string $commentId): bool
    {
        $this->makeRequest($integration, 'DELETE', "/{$commentId}");

        return true;
    }

    public function likeComment(Integration $integration, string $commentId): bool
    {
        $this->makeRequest($integration, 'POST', "/{$commentId}/likes");

        return true;
    }

    // ========================================
    // Ad Campaign Management
    // ========================================

    public function createAdCampaign(Integration $integration, array $campaignData): array
    {
        $adAccountId = $integration->settings['ad_account_id'] ?? null;

        if (!$adAccountId) {
            throw new \Exception('Ad Account ID is required');
        }

        // Create Campaign
        $campaignResponse = $this->makeRequest($integration, 'POST', "/act_{$adAccountId}/campaigns", [
            'name' => $campaignData['campaign_name'],
            'objective' => $campaignData['objective'],
            'status' => $campaignData['status'] ?? 'PAUSED',
            'special_ad_categories' => $campaignData['special_ad_categories'] ?? [],
        ]);

        $campaignId = $campaignResponse['id'];

        // Create Ad Set if provided
        if (isset($campaignData['adset'])) {
            $adSetResponse = $this->makeRequest($integration, 'POST', "/act_{$adAccountId}/adsets", [
                'name' => $campaignData['adset']['name'],
                'campaign_id' => $campaignId,
                'daily_budget' => $campaignData['adset']['daily_budget'] ?? null,
                'lifetime_budget' => $campaignData['adset']['lifetime_budget'] ?? null,
                'billing_event' => $campaignData['adset']['billing_event'] ?? 'IMPRESSIONS',
                'optimization_goal' => $campaignData['adset']['optimization_goal'] ?? 'LINK_CLICKS',
                'targeting' => json_encode($campaignData['adset']['targeting'] ?? []),
                'status' => $campaignData['status'] ?? 'PAUSED',
            ]);
        }

        return [
            'success' => true,
            'campaign_id' => $campaignId,
            'adset_id' => $adSetResponse['id'] ?? null,
        ];
    }

    public function updateAdCampaign(Integration $integration, string $campaignId, array $updates): array
    {
        $this->makeRequest($integration, 'POST', "/{$campaignId}", $updates);

        return ['success' => true];
    }

    public function getAdCampaignMetrics(Integration $integration, string $campaignId, array $options = []): Collection
    {
        $response = $this->makeRequest($integration, 'GET', "/{$campaignId}/insights", [
            'fields' => 'spend,impressions,clicks,ctr,cpc,cpm,reach,conversions',
            'date_preset' => $options['date_preset'] ?? 'last_30d',
        ]);

        return collect($response['data'] ?? []);
    }

    /**
     * Create an ad creative for Meta ads.
     *
     * @param Integration $integration
     * @param array $creativeData Creative data including:
     *   - name: Creative name
     *   - object_story_spec: Story specification (page_id, link_data, etc.)
     *   - asset_feed_spec: For dynamic creative ads
     *   - degrees_of_freedom_spec: For Advantage+ creative
     * @return array Result with creative_id
     */
    public function createAdCreative($integration, array $creativeData): array
    {
        $adAccountId = $integration->settings['ad_account_id'] ?? null;

        if (!$adAccountId) {
            throw new \Exception('Ad Account ID is required for creating ad creative');
        }

        $payload = [
            'name' => $creativeData['name'],
        ];

        // Handle different creative types
        if (!empty($creativeData['object_story_spec'])) {
            $payload['object_story_spec'] = json_encode($creativeData['object_story_spec']);
        }

        if (!empty($creativeData['asset_feed_spec'])) {
            $payload['asset_feed_spec'] = json_encode($creativeData['asset_feed_spec']);
        }

        if (!empty($creativeData['degrees_of_freedom_spec'])) {
            $payload['degrees_of_freedom_spec'] = json_encode($creativeData['degrees_of_freedom_spec']);
        }

        // Handle image/video creative
        if (!empty($creativeData['image_hash'])) {
            $payload['image_hash'] = $creativeData['image_hash'];
        }

        if (!empty($creativeData['video_id'])) {
            $payload['video_id'] = $creativeData['video_id'];
        }

        // Link specification for link ads
        if (!empty($creativeData['link_url'])) {
            $linkData = [
                'link' => $creativeData['link_url'],
                'message' => $creativeData['message'] ?? '',
            ];

            if (!empty($creativeData['image_hash'])) {
                $linkData['image_hash'] = $creativeData['image_hash'];
            }

            if (!empty($creativeData['call_to_action'])) {
                $linkData['call_to_action'] = $creativeData['call_to_action'];
            }

            $payload['object_story_spec'] = json_encode([
                'page_id' => $creativeData['page_id'] ?? $integration->settings['page_id'],
                'link_data' => $linkData,
            ]);
        }

        $response = $this->makeRequest($integration, 'POST', "/act_{$adAccountId}/adcreatives", $payload);

        return [
            'success' => true,
            'creative_id' => $response['id'],
        ];
    }

    /**
     * Create a lookalike audience on Meta.
     *
     * @param Integration $integration
     * @param array $audienceData Audience data including:
     *   - name: Audience name
     *   - source_audience_id: Custom audience ID to base lookalike on
     *   - country: Target country code (e.g., 'US')
     *   - ratio: Lookalike ratio 0.01 to 0.20 (1% to 20%)
     *   - optimization_goal: NONE, REACH, or SIMILARITY
     * @return array Result with audience_id
     */
    public function createLookalikeAudience($integration, array $audienceData): array
    {
        $adAccountId = $integration->settings['ad_account_id'] ?? null;

        if (!$adAccountId) {
            throw new \Exception('Ad Account ID is required for creating lookalike audience');
        }

        if (empty($audienceData['source_audience_id'])) {
            throw new \Exception('Source audience ID is required for lookalike audience');
        }

        $payload = [
            'name' => $audienceData['name'],
            'subtype' => 'LOOKALIKE',
            'origin_audience_id' => $audienceData['source_audience_id'],
            'lookalike_spec' => json_encode([
                'type' => 'similarity',
                'country' => $audienceData['country'] ?? 'US',
                'ratio' => $audienceData['ratio'] ?? 0.01, // 1% default
            ]),
        ];

        // Optional optimization goal
        if (!empty($audienceData['optimization_goal'])) {
            $lookalikeSpec = json_decode($payload['lookalike_spec'], true);
            $lookalikeSpec['optimization_goal'] = $audienceData['optimization_goal'];
            $payload['lookalike_spec'] = json_encode($lookalikeSpec);
        }

        $response = $this->makeRequest($integration, 'POST', "/act_{$adAccountId}/customaudiences", $payload);

        return [
            'success' => true,
            'audience_id' => $response['id'],
        ];
    }

    /**
     * Create a custom audience on Meta.
     *
     * @param Integration $integration
     * @param array $audienceData Audience data including:
     *   - name: Audience name
     *   - description: Audience description
     *   - subtype: CUSTOM, WEBSITE, APP, OFFLINE_CONVERSION, etc.
     *   - customer_file_source: USER_PROVIDED_ONLY, BOTH_USER_AND_PARTNER_PROVIDED
     * @return array Result with audience_id
     */
    public function createCustomAudience($integration, array $audienceData): array
    {
        $adAccountId = $integration->settings['ad_account_id'] ?? null;

        if (!$adAccountId) {
            throw new \Exception('Ad Account ID is required for creating custom audience');
        }

        $payload = [
            'name' => $audienceData['name'],
            'subtype' => $audienceData['subtype'] ?? 'CUSTOM',
            'description' => $audienceData['description'] ?? '',
            'customer_file_source' => $audienceData['customer_file_source'] ?? 'USER_PROVIDED_ONLY',
        ];

        // For website custom audiences
        if (($audienceData['subtype'] ?? '') === 'WEBSITE') {
            $payload['rule'] = json_encode($audienceData['rule'] ?? [
                'inclusions' => [
                    'operator' => 'or',
                    'rules' => [
                        [
                            'event_sources' => [['id' => $audienceData['pixel_id']]],
                            'retention_seconds' => $audienceData['retention_days'] ?? 30 * 86400,
                        ],
                    ],
                ],
            ]);
        }

        $response = $this->makeRequest($integration, 'POST', "/act_{$adAccountId}/customaudiences", $payload);

        return [
            'success' => true,
            'audience_id' => $response['id'],
        ];
    }

    /**
     * Create an ad set on Meta.
     *
     * @param Integration $integration
     * @param array $adSetData Ad set data including:
     *   - name: Ad set name
     *   - campaign_id: Parent campaign ID
     *   - daily_budget or lifetime_budget: Budget in cents
     *   - billing_event: IMPRESSIONS, LINK_CLICKS, etc.
     *   - optimization_goal: LINK_CLICKS, REACH, IMPRESSIONS, CONVERSIONS, etc.
     *   - targeting: Targeting specification
     *   - start_time, end_time: Schedule (optional)
     *   - bid_amount: Bid cap in cents (optional)
     *   - bid_strategy: LOWEST_COST_WITHOUT_CAP, LOWEST_COST_WITH_BID_CAP, COST_CAP
     * @return array Result with adset_id
     */
    public function createAdSet($integration, array $adSetData): array
    {
        $adAccountId = $integration->settings['ad_account_id'] ?? null;

        if (!$adAccountId) {
            throw new \Exception('Ad Account ID is required for creating ad set');
        }

        if (empty($adSetData['campaign_id'])) {
            throw new \Exception('Campaign ID is required for creating ad set');
        }

        $payload = [
            'name' => $adSetData['name'],
            'campaign_id' => $adSetData['campaign_id'],
            'status' => $adSetData['status'] ?? 'PAUSED',
            'billing_event' => $adSetData['billing_event'] ?? 'IMPRESSIONS',
            'optimization_goal' => $adSetData['optimization_goal'] ?? 'LINK_CLICKS',
        ];

        // Budget - either daily or lifetime
        if (!empty($adSetData['daily_budget'])) {
            $payload['daily_budget'] = $adSetData['daily_budget'];
        } elseif (!empty($adSetData['lifetime_budget'])) {
            $payload['lifetime_budget'] = $adSetData['lifetime_budget'];
        }

        // Targeting (required)
        if (!empty($adSetData['targeting'])) {
            $payload['targeting'] = is_string($adSetData['targeting'])
                ? $adSetData['targeting']
                : json_encode($adSetData['targeting']);
        } else {
            // Default targeting - reach everyone in US
            $payload['targeting'] = json_encode([
                'geo_locations' => [
                    'countries' => ['US'],
                ],
            ]);
        }

        // Optional: Schedule
        if (!empty($adSetData['start_time'])) {
            $payload['start_time'] = $adSetData['start_time'];
        }
        if (!empty($adSetData['end_time'])) {
            $payload['end_time'] = $adSetData['end_time'];
        }

        // Optional: Bid settings
        if (!empty($adSetData['bid_amount'])) {
            $payload['bid_amount'] = $adSetData['bid_amount'];
        }
        if (!empty($adSetData['bid_strategy'])) {
            $payload['bid_strategy'] = $adSetData['bid_strategy'];
        }

        // Optional: Promoted object (for conversion campaigns)
        if (!empty($adSetData['promoted_object'])) {
            $payload['promoted_object'] = is_string($adSetData['promoted_object'])
                ? $adSetData['promoted_object']
                : json_encode($adSetData['promoted_object']);
        }

        // Optional: Attribution spec
        if (!empty($adSetData['attribution_spec'])) {
            $payload['attribution_spec'] = is_string($adSetData['attribution_spec'])
                ? $adSetData['attribution_spec']
                : json_encode($adSetData['attribution_spec']);
        }

        $response = $this->makeRequest($integration, 'POST', "/act_{$adAccountId}/adsets", $payload);

        return [
            'success' => true,
            'adset_id' => $response['id'],
        ];
    }

    /**
     * Create an ad (links creative to ad set).
     *
     * @param Integration $integration
     * @param array $adData Ad data including:
     *   - name: Ad name
     *   - adset_id: Ad set ID
     *   - creative_id: Creative ID
     *   - status: PAUSED, ACTIVE
     * @return array Result with ad_id
     */
    public function createAd($integration, array $adData): array
    {
        $adAccountId = $integration->settings['ad_account_id'] ?? null;

        if (!$adAccountId) {
            throw new \Exception('Ad Account ID is required for creating ad');
        }

        if (empty($adData['adset_id']) || empty($adData['creative_id'])) {
            throw new \Exception('Ad set ID and Creative ID are required for creating ad');
        }

        $payload = [
            'name' => $adData['name'],
            'adset_id' => $adData['adset_id'],
            'creative' => json_encode(['creative_id' => $adData['creative_id']]),
            'status' => $adData['status'] ?? 'PAUSED',
        ];

        // Optional: Tracking specs
        if (!empty($adData['tracking_specs'])) {
            $payload['tracking_specs'] = is_string($adData['tracking_specs'])
                ? $adData['tracking_specs']
                : json_encode($adData['tracking_specs']);
        }

        $response = $this->makeRequest($integration, 'POST', "/act_{$adAccountId}/ads", $payload);

        return [
            'success' => true,
            'ad_id' => $response['id'],
        ];
    }

    /**
     * Upload an image to the ad account for use in creatives.
     *
     * @param Integration $integration
     * @param string $imagePath Path to local image file or URL
     * @return array Result with image_hash
     */
    public function uploadAdImage($integration, string $imagePath): array
    {
        $adAccountId = $integration->settings['ad_account_id'] ?? null;

        if (!$adAccountId) {
            throw new \Exception('Ad Account ID is required for uploading ad image');
        }

        // If it's a URL, download it first
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            $tempPath = sys_get_temp_dir() . '/meta_ad_image_' . uniqid() . '.jpg';
            file_put_contents($tempPath, file_get_contents($imagePath));
            $imagePath = $tempPath;
            $cleanup = true;
        } else {
            $cleanup = false;
        }

        try {
            $payload = [
                'filename' => base64_encode(file_get_contents($imagePath)),
            ];

            $response = $this->makeRequest($integration, 'POST', "/act_{$adAccountId}/adimages", $payload);

            // Response contains images keyed by filename
            $images = $response['images'] ?? [];
            $imageData = reset($images);

            return [
                'success' => true,
                'image_hash' => $imageData['hash'] ?? null,
                'url' => $imageData['url'] ?? null,
            ];
        } finally {
            if ($cleanup && file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }
    }

    // ========================================
    // Helper Methods
    // ========================================

    private function storeCampaign(Integration $integration, array $campaign): int
    {
        return $this->storeData('cmis_ads.ad_campaigns', [
            'org_id' => $integration->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'meta',
            'platform_campaign_id' => $campaign['id'],
            'campaign_name' => $campaign['name'],
            'objective' => $campaign['objective'] ?? null,
            'status' => $campaign['status'],
            'daily_budget' => $campaign['daily_budget'] ?? null,
            'lifetime_budget' => $campaign['lifetime_budget'] ?? null,
            'metrics' => json_encode($campaign['insights']['data'][0] ?? []),
            'created_at' => now(),
            'updated_at' => now(),
        ], ['platform_campaign_id' => $campaign['id']]);
    }

    private function storePost(Integration $integration, array $post): int
    {
        return $this->storeData('cmis_social.social_posts', [
            'org_id' => $integration->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'meta',
            'platform_post_id' => $post['id'],
            'post_type' => 'facebook_post',
            'content' => $post['message'] ?? null,
            'permalink' => $post['permalink_url'] ?? null,
            'published_at' => isset($post['created_time']) ? Carbon::parse($post['created_time']) : now(),
            'metrics' => json_encode([
                'reactions' => $post['reactions']['summary']['total_count'] ?? 0,
                'comments' => $post['comments']['summary']['total_count'] ?? 0,
                'shares' => $post['shares']['count'] ?? 0,
            ]),
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ], ['platform_post_id' => $post['id']]);
    }

    private function storeComment(Integration $integration, string $postId, array $comment): int
    {
        return $this->storeData('cmis_social.social_comments', [
            'org_id' => $integration->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'meta',
            'platform_comment_id' => $comment['id'],
            'post_id' => DB::table('cmis_social.social_posts')
                ->where('platform_post_id', $postId)
                ->value('post_id'),
            'comment_text' => $comment['message'],
            'commenter_name' => $comment['from']['name'] ?? null,
            'commenter_id' => $comment['from']['id'] ?? null,
            'is_hidden' => $comment['is_hidden'] ?? false,
            'likes_count' => $comment['like_count'] ?? 0,
            'created_at' => isset($comment['created_time']) ? Carbon::parse($comment['created_time']) : now(),
        ], ['platform_comment_id' => $comment['id']]);
    }

    private function storeMessage(Integration $integration, string $conversationId, array $message): int
    {
        return $this->storeData('cmis_social.social_messages', [
            'org_id' => $integration->org_id,
            'integration_id' => $integration->integration_id,
            'platform' => 'meta',
            'platform_message_id' => $message['id'],
            'conversation_id' => $conversationId,
            'message_text' => $message['message'],
            'sender_id' => $message['from']['id'] ?? null,
            'sender_name' => $message['from']['name'] ?? null,
            'is_from_page' => ($message['from']['id'] ?? null) === ($integration->settings['page_id'] ?? null),
            'status' => 'received',
            'created_at' => isset($message['created_time']) ? Carbon::parse($message['created_time']) : now(),
        ], ['platform_message_id' => $message['id']]);
    }
}
