<?php

namespace App\Services\Social;

use App\Models\Platform\PlatformConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Social Post Publishing Service
 *
 * Handles publishing posts to social media platforms.
 * Currently supports Meta platforms (Facebook, Instagram) with
 * full support for all Graph API features.
 */
class SocialPostPublishService
{
    /**
     * Publish a social post to its target platform
     *
     * @param string $orgId Organization UUID
     * @param string $postId Post UUID
     * @return array Publishing result
     */
    public function publishPost(string $orgId, string $postId): array
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

            $socialPost = DB::table('cmis.social_posts')
                ->where('org_id', $orgId)
                ->where('id', $postId)
                ->whereNull('deleted_at')
                ->first();

            if (!$socialPost) {
                return ['success' => false, 'message' => 'Post not found'];
            }

            // Validate post status
            if (!in_array($socialPost->status, ['draft', 'scheduled'])) {
                return ['success' => false, 'message' => 'This post cannot be published'];
            }

            // Get platform connection
            $connection = PlatformConnection::where('org_id', $orgId)
                ->where('platform', 'meta')
                ->where('status', 'active')
                ->first();

            if (!$connection) {
                return ['success' => false, 'message' => 'No active Meta connection found'];
            }

            // Update status to publishing
            DB::table('cmis.social_posts')
                ->where('id', $postId)
                ->update(['status' => 'publishing', 'updated_at' => now()]);

            // Parse media URLs
            $mediaUrls = json_decode($socialPost->media ?? '[]', true);

            // Publish to platform
            $result = $this->publishToMeta(
                $postId,
                $socialPost->platform,
                $socialPost->account_id,
                $socialPost->content,
                $mediaUrls,
                $connection
            );

            // Update post with result
            if ($result['success']) {
                DB::table('cmis.social_posts')
                    ->where('id', $postId)
                    ->update([
                        'status' => 'published',
                        'published_at' => now(),
                        'post_external_id' => $result['post_id'] ?? null,
                        'permalink' => $result['permalink'] ?? null,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('cmis.social_posts')
                    ->where('id', $postId)
                    ->update([
                        'status' => 'failed',
                        'failed_at' => now(),
                        'error_message' => $result['message'] ?? 'Unknown error',
                        'retry_count' => DB::raw('COALESCE(retry_count, 0) + 1'),
                        'updated_at' => now(),
                    ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Post publishing error', [
                'org_id' => $orgId,
                'post_id' => $postId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Publish content to Meta (Facebook/Instagram)
     *
     * @param string $postId Post UUID
     * @param string $platform Target platform (facebook|instagram)
     * @param string|null $accountId Specific account ID
     * @param string $content Post content/caption
     * @param array $mediaUrls Media attachments
     * @param PlatformConnection $connection Meta platform connection
     * @return array Publishing result
     */
    public function publishToMeta(
        string $postId,
        string $platform,
        ?string $accountId,
        string $content,
        array $mediaUrls,
        PlatformConnection $connection
    ): array {
        try {
            $accessToken = $connection->access_token;
            $metadata = $connection->account_metadata ?? [];
            $selectedAssets = $metadata['selected_assets'] ?? [];

            // Get selected asset IDs (handle both singular and plural keys)
            $selectedPageIds = $selectedAssets['pages'] ?? $selectedAssets['page'] ?? [];
            $selectedInstagramIds = $selectedAssets['instagram_accounts'] ?? $selectedAssets['instagram_account'] ?? [];

            // Determine target account
            [$pageId, $instagramAccountId] = $this->resolveTargetAccounts(
                $platform,
                $accountId,
                $selectedPageIds,
                $selectedInstagramIds
            );

            Log::info('Publishing to Meta', [
                'post_id' => $postId,
                'platform' => $platform,
                'page_id' => $pageId,
                'instagram_id' => $instagramAccountId,
            ]);

            // Get post options from metadata
            $postMetadata = DB::table('cmis.social_posts')
                ->where('id', $postId)
                ->value('metadata');
            $postOptions = $postMetadata ? json_decode($postMetadata, true) : [];

            // Route to platform-specific publisher
            if ($platform === 'facebook' && $pageId) {
                return $this->publishToFacebook($content, $mediaUrls, $pageId, $accessToken, $postOptions);
            }

            if ($platform === 'instagram' && $instagramAccountId) {
                return $this->publishToInstagram($content, $mediaUrls, $instagramAccountId, $pageId, $accessToken, $postOptions);
            }

            return [
                'success' => false,
                'message' => "No {$platform} account selected. Please configure your Meta connection.",
            ];

        } catch (\Exception $e) {
            Log::error('Meta publishing error', [
                'post_id' => $postId,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Resolve target accounts for publishing
     *
     * @param string $platform
     * @param string|null $accountId
     * @param array $selectedPageIds
     * @param array $selectedInstagramIds
     * @return array [pageId, instagramAccountId]
     */
    protected function resolveTargetAccounts(
        string $platform,
        ?string $accountId,
        array $selectedPageIds,
        array $selectedInstagramIds
    ): array {
        $pageId = null;
        $instagramAccountId = null;

        if ($platform === 'facebook') {
            if ($accountId && in_array($accountId, $selectedPageIds)) {
                $pageId = $accountId;
            } elseif (!empty($selectedPageIds)) {
                $pageId = $selectedPageIds[0];
            }
        } elseif ($platform === 'instagram') {
            if ($accountId && in_array($accountId, $selectedInstagramIds)) {
                $instagramAccountId = $accountId;
            } elseif (!empty($selectedInstagramIds)) {
                $instagramAccountId = $selectedInstagramIds[0];
            }

            // Instagram requires a connected page for API access
            if ($instagramAccountId && !empty($selectedPageIds)) {
                $pageId = $selectedPageIds[0];
            }
        }

        return [$pageId, $instagramAccountId];
    }

    /**
     * Publish to Facebook Page
     *
     * Supports: text posts, photos, videos, albums
     * Options: location, user_tags, etc.
     *
     * @param string $content
     * @param array $mediaUrls
     * @param string $pageId
     * @param string $accessToken
     * @param array $postOptions
     * @return array
     */
    public function publishToFacebook(
        string $content,
        array $mediaUrls,
        string $pageId,
        string $accessToken,
        array $postOptions = []
    ): array {
        try {
            Log::info('Publishing to Facebook', [
                'page_id' => $pageId,
                'has_media' => !empty($mediaUrls),
                'content_length' => strlen($content),
            ]);

            // Get page access token
            $pageToken = $this->getPageToken($pageId, $accessToken);
            if (!$pageToken) {
                return ['success' => false, 'message' => 'Failed to get page access token'];
            }

            // Publish based on content type
            if (empty($mediaUrls)) {
                $response = $this->publishFacebookTextPost($pageId, $content, $pageToken);
            } else {
                $firstMedia = $mediaUrls[0];

                if ($firstMedia['type'] === 'video') {
                    $response = $this->publishFacebookVideo($pageId, $firstMedia, $content, $pageToken);
                } elseif (count($mediaUrls) === 1) {
                    $response = $this->publishFacebookPhoto($pageId, $firstMedia, $content, $pageToken);
                } else {
                    $response = $this->publishFacebookAlbum($pageId, $mediaUrls, $content, $pageToken);
                }
            }

            return $this->handleFacebookResponse($response);

        } catch (\Exception $e) {
            Log::error('Facebook publish exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get page access token
     */
    protected function getPageToken(string $pageId, string $userToken): ?string
    {
        $response = Http::timeout(30)->get("https://graph.facebook.com/v21.0/{$pageId}", [
            'access_token' => $userToken,
            'fields' => 'access_token,name',
        ]);

        if ($response->successful()) {
            return $response->json('access_token');
        }

        Log::error('Failed to get page token', [
            'page_id' => $pageId,
            'response' => $response->json(),
        ]);

        return null;
    }

    /**
     * Publish text-only post to Facebook
     */
    protected function publishFacebookTextPost(string $pageId, string $content, string $pageToken)
    {
        return Http::timeout(30)
            ->asForm()
            ->post("https://graph.facebook.com/v21.0/{$pageId}/feed", [
                'access_token' => $pageToken,
                'message' => $content,
            ]);
    }

    /**
     * Publish video to Facebook
     */
    protected function publishFacebookVideo(string $pageId, array $media, string $content, string $pageToken)
    {
        return Http::timeout(120)->post("https://graph.facebook.com/v21.0/{$pageId}/videos", [
            'access_token' => $pageToken,
            'file_url' => $media['url'],
            'description' => $content,
        ]);
    }

    /**
     * Publish single photo to Facebook
     */
    protected function publishFacebookPhoto(string $pageId, array $media, string $content, string $pageToken)
    {
        return Http::timeout(60)->post("https://graph.facebook.com/v21.0/{$pageId}/photos", [
            'access_token' => $pageToken,
            'url' => $media['url'],
            'message' => $content,
        ]);
    }

    /**
     * Publish photo album to Facebook
     */
    protected function publishFacebookAlbum(string $pageId, array $mediaUrls, string $content, string $pageToken)
    {
        $photoIds = [];

        foreach ($mediaUrls as $media) {
            if ($media['type'] === 'image') {
                $photoResponse = Http::timeout(60)->post("https://graph.facebook.com/v21.0/{$pageId}/photos", [
                    'access_token' => $pageToken,
                    'url' => $media['url'],
                    'published' => false,
                ]);

                if ($photoResponse->successful()) {
                    $photoIds[] = ['media_fbid' => $photoResponse->json('id')];
                }
            }
        }

        return Http::timeout(60)->post("https://graph.facebook.com/v21.0/{$pageId}/feed", [
            'access_token' => $pageToken,
            'message' => $content,
            'attached_media' => json_encode($photoIds),
        ]);
    }

    /**
     * Handle Facebook API response
     */
    protected function handleFacebookResponse($response): array
    {
        Log::info('Facebook API response', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        if ($response->successful()) {
            $postId = $response->json('id') ?? $response->json('post_id');
            return [
                'success' => true,
                'post_id' => $postId,
                'permalink' => "https://facebook.com/{$postId}",
                'message' => 'Published to Facebook successfully',
            ];
        }

        $error = $response->json('error.message', 'Unknown error');
        Log::error('Facebook publish failed', [
            'error' => $error,
            'error_code' => $response->json('error.code'),
        ]);

        return ['success' => false, 'message' => $error];
    }

    /**
     * Publish to Instagram
     *
     * Supports: photos, videos, carousels, reels
     * Options: collaborators, location, user_tags, first_comment, etc.
     *
     * @param string $content Caption
     * @param array $mediaUrls Media files
     * @param string $instagramAccountId IG Business Account ID
     * @param string|null $pageId Connected Facebook Page ID
     * @param string $accessToken User access token
     * @param array $postOptions Publishing options
     * @return array
     */
    public function publishToInstagram(
        string $content,
        array $mediaUrls,
        string $instagramAccountId,
        ?string $pageId,
        string $accessToken,
        array $postOptions = []
    ): array {
        try {
            if (empty($mediaUrls)) {
                return [
                    'success' => false,
                    'message' => 'Instagram posts require at least one image or video',
                ];
            }

            $firstMedia = $mediaUrls[0];

            // Build container data with all supported options
            $containerData = $this->buildInstagramContainerData($content, $firstMedia, $mediaUrls, $accessToken, $postOptions);

            // Create media container
            $containerResponse = Http::timeout(60)->post(
                "https://graph.facebook.com/v21.0/{$instagramAccountId}/media",
                $containerData
            );

            if (!$containerResponse->successful()) {
                $error = $containerResponse->json('error.message', 'Failed to create media container');
                return ['success' => false, 'message' => $error];
            }

            $containerId = $containerResponse->json('id');

            // Wait for video processing if needed
            if ($firstMedia['type'] === 'video') {
                $this->waitForVideoProcessing($containerId, $accessToken);
            }

            // Publish the container
            $publishResponse = Http::timeout(60)->post(
                "https://graph.facebook.com/v21.0/{$instagramAccountId}/media_publish",
                [
                    'access_token' => $accessToken,
                    'creation_id' => $containerId,
                ]
            );

            if (!$publishResponse->successful()) {
                $error = $publishResponse->json('error.message', 'Unknown error');
                return ['success' => false, 'message' => $error];
            }

            $postId = $publishResponse->json('id');

            // Get permalink
            $permalink = $this->getInstagramPermalink($postId, $accessToken);

            // Post first comment if requested
            $firstCommentPosted = false;
            if (!empty($postOptions['first_comment'])) {
                $firstCommentPosted = $this->postInstagramFirstComment($postId, $postOptions['first_comment'], $accessToken);
            }

            return [
                'success' => true,
                'post_id' => $postId,
                'permalink' => $permalink,
                'first_comment_posted' => $firstCommentPosted,
                'message' => 'Published to Instagram successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Instagram publish exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Build Instagram media container data with all supported Graph API options
     *
     * Supports:
     * - share_to_feed (for Reels)
     * - thumb_offset (cover frame offset in ms)
     * - cover_url (custom cover image)
     * - collaborators (invite collaborators)
     * - location_id (tag location)
     * - user_tags (tag people)
     * - product_tags (for shopping)
     * - alt_text (accessibility)
     *
     * @param string $content
     * @param array $firstMedia
     * @param array $mediaUrls
     * @param string $accessToken
     * @param array $postOptions
     * @return array Container data for Graph API
     */
    protected function buildInstagramContainerData(
        string $content,
        array $firstMedia,
        array $mediaUrls,
        string $accessToken,
        array $postOptions
    ): array {
        $containerData = [
            'access_token' => $accessToken,
            'caption' => $content,
        ];

        // Share to feed (for Reels)
        if (isset($postOptions['share_to_feed'])) {
            $containerData['share_to_feed'] = $postOptions['share_to_feed'] ? 'true' : 'false';
        }

        // Video cover options
        if ($firstMedia['type'] === 'video') {
            if (!empty($postOptions['cover_frame_offset'])) {
                $containerData['thumb_offset'] = (int) $postOptions['cover_frame_offset'];
            }
            if (!empty($postOptions['cover_image_url']) && $postOptions['cover_type'] === 'custom') {
                $containerData['cover_url'] = $postOptions['cover_image_url'];
            }
        }

        // Location tagging
        if (!empty($postOptions['location_id'])) {
            $containerData['location_id'] = $postOptions['location_id'];
        }

        // Collaborators
        if (!empty($postOptions['collaborators']) && is_array($postOptions['collaborators'])) {
            $collaborators = array_slice($postOptions['collaborators'], 0, 3); // Max 3
            if (!empty($collaborators)) {
                $containerData['collaborators'] = json_encode($collaborators);
            }
        }

        // User tags
        if (!empty($postOptions['user_tags']) && is_array($postOptions['user_tags'])) {
            $userTags = array_map(function ($tag) {
                return [
                    'username' => ltrim($tag['username'] ?? '', '@'),
                    'x' => $tag['x'] ?? 0.5,
                    'y' => $tag['y'] ?? 0.5,
                ];
            }, $postOptions['user_tags']);

            if (!empty($userTags)) {
                $containerData['user_tags'] = json_encode($userTags);
            }
        }

        // Product tags (Shopping)
        if (!empty($postOptions['product_tags']) && is_array($postOptions['product_tags'])) {
            $containerData['product_tags'] = json_encode($postOptions['product_tags']);
        }

        // Media URL
        if ($firstMedia['type'] === 'video') {
            $containerData['media_type'] = 'VIDEO';
            $containerData['video_url'] = $firstMedia['url'];
        } else {
            if (count($mediaUrls) === 1) {
                $containerData['image_url'] = $firstMedia['url'];
                // Alt text for accessibility
                if (!empty($postOptions['alt_text'])) {
                    $containerData['alt_text'] = $postOptions['alt_text'];
                }
            } else {
                // Carousel
                $containerData['media_type'] = 'CAROUSEL';
                $containerData['children'] = $this->createCarouselChildren(
                    $mediaUrls,
                    $accessToken,
                    $postOptions
                );
            }
        }

        Log::info('Instagram container data built', [
            'options_count' => count($containerData),
            'has_collaborators' => isset($containerData['collaborators']),
            'has_location' => isset($containerData['location_id']),
            'has_user_tags' => isset($containerData['user_tags']),
        ]);

        return $containerData;
    }

    /**
     * Create carousel children containers
     */
    protected function createCarouselChildren(array $mediaUrls, string $accessToken, array $postOptions): string
    {
        $children = [];
        $altTexts = $postOptions['alt_texts'] ?? [];

        foreach ($mediaUrls as $index => $media) {
            $childData = [
                'access_token' => $accessToken,
                'is_carousel_item' => true,
            ];

            if (!empty($altTexts[$index])) {
                $childData['alt_text'] = $altTexts[$index];
            }

            if ($media['type'] === 'video') {
                $childData['media_type'] = 'VIDEO';
                $childData['video_url'] = $media['url'];
            } else {
                $childData['image_url'] = $media['url'];
            }

            $childResponse = Http::timeout(60)->post(
                "https://graph.facebook.com/v21.0/" . config('instagram.account_id') . "/media",
                $childData
            );

            if ($childResponse->successful()) {
                $children[] = $childResponse->json('id');
            }
        }

        return implode(',', $children);
    }

    /**
     * Wait for Instagram video processing
     */
    protected function waitForVideoProcessing(string $containerId, string $accessToken): void
    {
        $maxAttempts = 30;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $statusResponse = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$containerId}", [
                'access_token' => $accessToken,
                'fields' => 'status_code',
            ]);

            $status = $statusResponse->json('status_code');

            if ($status === 'FINISHED') {
                return;
            }

            if ($status === 'ERROR') {
                throw new \Exception('Video processing failed');
            }

            sleep(2);
            $attempt++;
        }
    }

    /**
     * Get Instagram post permalink
     */
    protected function getInstagramPermalink(string $postId, string $accessToken): string
    {
        $response = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$postId}", [
            'access_token' => $accessToken,
            'fields' => 'permalink',
        ]);

        return $response->json('permalink', "https://instagram.com/p/{$postId}");
    }

    /**
     * Post first comment on Instagram
     */
    protected function postInstagramFirstComment(string $postId, string $comment, string $accessToken): bool
    {
        try {
            $response = Http::timeout(30)->post("https://graph.facebook.com/v21.0/{$postId}/comments", [
                'access_token' => $accessToken,
                'message' => $comment,
            ]);

            if ($response->successful()) {
                Log::info('First comment posted', ['post_id' => $postId]);
                return true;
            }

            Log::warning('Failed to post first comment', [
                'post_id' => $postId,
                'error' => $response->json('error.message'),
            ]);

            return false;

        } catch (\Exception $e) {
            Log::warning('Exception posting first comment', [
                'post_id' => $postId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
