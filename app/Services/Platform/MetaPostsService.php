<?php

namespace App\Services\Platform;

use App\Models\Platform\MetaAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class MetaPostsService
{
    private string $apiVersion;
    private string $baseUrl = 'https://graph.facebook.com';

    public function __construct()
    {
        $this->apiVersion = config('services.meta.api_version', 'v19.0');
    }

    /**
     * Get Page Access Token from User Access Token
     */
    public function getPageAccessToken(string $pageId, string $userAccessToken): ?string
    {
        try {
            $cacheKey = "meta_page_token_{$pageId}";

            return Cache::remember($cacheKey, 3600, function () use ($pageId, $userAccessToken) {
                $url = "{$this->baseUrl}/{$this->apiVersion}/me/accounts";

                $response = Http::timeout(30)->get($url, [
                    'access_token' => $userAccessToken,
                    'fields' => 'id,name,access_token',
                ]);

                if (!$response->successful()) {
                    Log::error('Failed to get page accounts', ['error' => $response->body()]);
                    return null;
                }

                $pages = $response->json()['data'] ?? [];

                foreach ($pages as $page) {
                    if ($page['id'] === $pageId) {
                        return $page['access_token'];
                    }
                }

                return null;
            });
        } catch (Exception $e) {
            Log::error('Failed to get page access token', [
                'page_id' => $pageId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Fetch organic posts from Facebook Page
     */
    public function fetchFacebookPosts(
        string $pageId,
        string $accessToken,
        int $limit = 25,
        ?string $after = null
    ): array {
        try {
            // First, get the Page Access Token from User Access Token
            $pageAccessToken = $this->getPageAccessToken($pageId, $accessToken);

            if (!$pageAccessToken) {
                Log::warning('Could not get page access token, trying with user token', [
                    'page_id' => $pageId
                ]);
                $pageAccessToken = $accessToken; // Fallback to user token
            }

            $cacheKey = "meta_fb_posts_{$pageId}_" . md5($after ?? 'first');

            return Cache::remember($cacheKey, 300, function () use ($pageId, $pageAccessToken, $limit, $after) {
                $url = "{$this->baseUrl}/{$this->apiVersion}/{$pageId}/posts";

                $params = [
                    'access_token' => $pageAccessToken,
                    'limit' => $limit,
                    'fields' => implode(',', [
                        'id',
                        'message',
                        'full_picture',
                        'created_time',
                        'updated_time',
                        'permalink_url',
                        'is_published',
                        'shares',
                        'likes.summary(true)',
                        'comments.summary(true)',
                        'reactions.summary(true)'
                    ])
                ];

                if ($after) {
                    $params['after'] = $after;
                }

                $response = Http::timeout(30)->get($url, $params);

                if (!$response->successful()) {
                    throw new Exception("Facebook API error: {$response->body()}");
                }

                $data = $response->json();

                return [
                    'posts' => $this->transformFacebookPosts($data['data'] ?? []),
                    'paging' => $data['paging'] ?? null,
                    'count' => count($data['data'] ?? [])
                ];
            });

        } catch (Exception $e) {
            Log::error('Failed to fetch Facebook posts', [
                'page_id' => $pageId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Fetch organic posts from Instagram Business Account
     */
    public function fetchInstagramPosts(
        string $instagramAccountId,
        string $accessToken,
        int $limit = 25,
        ?string $after = null
    ): array {
        try {
            $cacheKey = "meta_ig_posts_{$instagramAccountId}_" . md5($after ?? 'first');

            return Cache::remember($cacheKey, 300, function () use ($instagramAccountId, $accessToken, $limit, $after) {
                $url = "{$this->baseUrl}/{$this->apiVersion}/{$instagramAccountId}/media";

                $params = [
                    'access_token' => $accessToken,
                    'limit' => $limit,
                    'fields' => implode(',', [
                        'id',
                        'caption',
                        'media_type',
                        'media_url',
                        'thumbnail_url',
                        'permalink',
                        'timestamp',
                        'username',
                        'like_count',
                        'comments_count',
                        'is_comment_enabled',
                        'children{id,media_type,media_url,thumbnail_url}'
                    ])
                ];

                if ($after) {
                    $params['after'] = $after;
                }

                $response = Http::timeout(30)->get($url, $params);

                if (!$response->successful()) {
                    throw new Exception("Instagram API error: {$response->body()}");
                }

                $data = $response->json();

                return [
                    'posts' => $this->transformInstagramPosts($data['data'] ?? []),
                    'paging' => $data['paging'] ?? null,
                    'count' => count($data['data'] ?? [])
                ];
            });

        } catch (Exception $e) {
            Log::error('Failed to fetch Instagram posts', [
                'account_id' => $instagramAccountId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Fetch all posts from all connected Meta accounts for an organization
     */
    public function fetchAllOrganizationPosts(
        string $orgId,
        string $platform = 'all', // 'all', 'facebook', 'instagram'
        int $limit = 50
    ): array {
        $metaAccounts = MetaAccount::where('org_id', $orgId)
            ->where('status', 'active')
            ->get();

        $allPosts = [
            'facebook' => [],
            'instagram' => [],
            'total_count' => 0
        ];

        foreach ($metaAccounts as $account) {
            // Fetch Facebook posts
            if (($platform === 'all' || $platform === 'facebook') && $account->page_id) {
                try {
                    $fbPosts = $this->fetchFacebookPosts(
                        $account->page_id,
                        $account->access_token,
                        $limit
                    );

                    foreach ($fbPosts['posts'] as &$post) {
                        $post['account_name'] = $account->page_name;
                        $post['account_id'] = $account->id;
                    }

                    $allPosts['facebook'] = array_merge($allPosts['facebook'], $fbPosts['posts']);
                } catch (Exception $e) {
                    Log::warning("Skipping Facebook account {$account->page_id}: {$e->getMessage()}");
                }
            }

            // Fetch Instagram posts
            if (($platform === 'all' || $platform === 'instagram') && $account->instagram_account_id) {
                try {
                    $igPosts = $this->fetchInstagramPosts(
                        $account->instagram_account_id,
                        $account->access_token,
                        $limit
                    );

                    foreach ($igPosts['posts'] as &$post) {
                        $post['account_name'] = $account->instagram_username ?? 'Instagram Account';
                        $post['account_id'] = $account->id;
                    }

                    $allPosts['instagram'] = array_merge($allPosts['instagram'], $igPosts['posts']);
                } catch (Exception $e) {
                    Log::warning("Skipping Instagram account {$account->instagram_account_id}: {$e->getMessage()}");
                }
            }
        }

        // Sort all posts by date (most recent first)
        $allPosts['facebook'] = collect($allPosts['facebook'])
            ->sortByDesc('created_time')
            ->values()
            ->toArray();

        $allPosts['instagram'] = collect($allPosts['instagram'])
            ->sortByDesc('created_time')
            ->values()
            ->toArray();

        $allPosts['total_count'] = count($allPosts['facebook']) + count($allPosts['instagram']);

        return $allPosts;
    }

    /**
     * Get post details by ID and platform
     */
    public function getPostDetails(
        string $postId,
        string $platform,
        string $accessToken
    ): array {
        try {
            $url = "{$this->baseUrl}/{$this->apiVersion}/{$postId}";

            $fields = $platform === 'facebook'
                ? $this->getFacebookPostFields()
                : $this->getInstagramPostFields();

            $response = Http::timeout(30)->get($url, [
                'access_token' => $accessToken,
                'fields' => implode(',', $fields)
            ]);

            if (!$response->successful()) {
                throw new Exception("Meta API error: {$response->body()}");
            }

            $data = $response->json();

            return $platform === 'facebook'
                ? $this->transformFacebookPost($data)
                : $this->transformInstagramPost($data);

        } catch (Exception $e) {
            Log::error('Failed to fetch post details', [
                'post_id' => $postId,
                'platform' => $platform,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Transform Facebook posts to standardized format
     */
    private function transformFacebookPosts(array $posts): array
    {
        return array_map(fn($post) => $this->transformFacebookPost($post), $posts);
    }

    /**
     * Transform single Facebook post
     */
    private function transformFacebookPost(array $post): array
    {
        $attachments = $post['attachments']['data'][0] ?? null;
        $mediaUrl = $post['full_picture'] ?? $attachments['media']['image']['src'] ?? null;

        return [
            'id' => $post['id'],
            'platform' => 'facebook',
            'message' => $post['message'] ?? '',
            'media_url' => $mediaUrl,
            'media_type' => $post['type'] ?? 'status',
            'permalink' => $post['permalink_url'] ?? null,
            'created_time' => $post['created_time'] ?? null,
            'updated_time' => $post['updated_time'] ?? null,
            'is_published' => $post['is_published'] ?? true,
            'engagement' => [
                'likes' => $post['likes']['summary']['total_count'] ?? 0,
                'comments' => $post['comments']['summary']['total_count'] ?? 0,
                'shares' => $post['shares']['count'] ?? 0,
                'reactions' => $post['reactions']['summary']['total_count'] ?? 0,
            ],
            'insights' => $this->extractInsights($post['insights']['data'] ?? []),
            'raw_data' => $post
        ];
    }

    /**
     * Transform Instagram posts to standardized format
     */
    private function transformInstagramPosts(array $posts): array
    {
        return array_map(fn($post) => $this->transformInstagramPost($post), $posts);
    }

    /**
     * Transform single Instagram post
     */
    private function transformInstagramPost(array $post): array
    {
        return [
            'id' => $post['id'],
            'platform' => 'instagram',
            'message' => $post['caption'] ?? '',
            'media_url' => $post['media_url'] ?? $post['thumbnail_url'] ?? null,
            'media_type' => strtolower($post['media_type'] ?? 'IMAGE'),
            'permalink' => $post['permalink'] ?? null,
            'created_time' => $post['timestamp'] ?? null,
            'username' => $post['username'] ?? null,
            'is_published' => true,
            'engagement' => [
                'likes' => $post['like_count'] ?? 0,
                'comments' => $post['comments_count'] ?? 0,
                'shares' => 0, // Instagram doesn't provide share count
                'reactions' => $post['like_count'] ?? 0,
            ],
            'insights' => $this->extractInsights($post['insights']['data'] ?? []),
            'children' => $post['children']['data'] ?? [],
            'raw_data' => $post
        ];
    }

    /**
     * Extract insights into simple key-value format
     */
    private function extractInsights(array $insights): array
    {
        $result = [];

        foreach ($insights as $insight) {
            $name = $insight['name'] ?? null;
            $value = $insight['values'][0]['value'] ?? 0;

            if ($name) {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    /**
     * Get Facebook post fields
     */
    private function getFacebookPostFields(): array
    {
        return [
            'id', 'message', 'full_picture', 'created_time', 'updated_time',
            'permalink_url', 'status_type', 'type', 'is_published',
            'shares', 'likes.summary(true)', 'comments.summary(true)',
            'reactions.summary(true)',
            'attachments{media,media_type,type,url,title,description}',
            'insights.metric(post_impressions,post_engaged_users,post_reactions_by_type_total)'
        ];
    }

    /**
     * Get Instagram post fields
     */
    private function getInstagramPostFields(): array
    {
        return [
            'id', 'caption', 'media_type', 'media_url', 'thumbnail_url',
            'permalink', 'timestamp', 'username',
            'like_count', 'comments_count', 'is_comment_enabled',
            'children{id,media_type,media_url,thumbnail_url}',
            'insights.metric(impressions,reach,engagement,saves,video_views)'
        ];
    }

    /**
     * Clear cache for organization posts
     */
    public function clearCache(string $identifier): void
    {
        Cache::forget("meta_fb_posts_{$identifier}_" . md5('first'));
        Cache::forget("meta_ig_posts_{$identifier}_" . md5('first'));
    }
}
