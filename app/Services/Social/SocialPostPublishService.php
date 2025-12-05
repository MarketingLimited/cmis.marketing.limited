<?php

namespace App\Services\Social;

use App\Models\Platform\PlatformConnection;
use App\Services\Platform\GoogleAssetsService;
use App\Services\Social\Publishers\TikTokPublisher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Social Post Publishing Service
 *
 * Handles publishing posts to social media platforms.
 * Supports: Meta (Facebook, Instagram), TikTok
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

            // Validate post status - allow draft, scheduled, and failed (for retry)
            if (!in_array($socialPost->status, ['draft', 'scheduled', 'failed'])) {
                return ['success' => false, 'message' => 'This post cannot be published'];
            }

            // Determine platform type for connection lookup
            $platform = $socialPost->platform;
            $connectionPlatform = $this->getConnectionPlatform($platform);

            // Get platform connection
            $connection = PlatformConnection::where('org_id', $orgId)
                ->where('platform', $connectionPlatform)
                ->where('status', 'active')
                ->first();

            if (!$connection) {
                return ['success' => false, 'message' => "No active {$platform} connection found. Please connect your {$platform} account in Settings > Platform Connections."];
            }

            // Update status to publishing
            DB::table('cmis.social_posts')
                ->where('id', $postId)
                ->update(['status' => 'publishing', 'updated_at' => now()]);

            // Parse media URLs
            $mediaUrls = json_decode($socialPost->media ?? '[]', true);

            // Route to platform-specific publishing method
            $result = $this->publishToPlatform(
                $postId,
                $platform,
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
            $containerData = $this->buildInstagramContainerData($content, $firstMedia, $mediaUrls, $instagramAccountId, $accessToken, $postOptions);

            // Create media container with retry logic for transient errors
            $containerResponse = null;
            $maxRetries = 3;
            $retryDelay = 2;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $containerResponse = Http::timeout(60)->post(
                    "https://graph.facebook.com/v21.0/{$instagramAccountId}/media",
                    $containerData
                );

                // Log response for debugging
                Log::info('Instagram container response', [
                    'status' => $containerResponse->status(),
                    'successful' => $containerResponse->successful(),
                    'body' => $containerResponse->json(),
                    'attempt' => $attempt,
                ]);

                // Success - break out of retry loop
                if ($containerResponse->successful()) {
                    break;
                }

                // Check if it's a retryable error (5xx server errors)
                if ($containerResponse->status() >= 500 && $attempt < $maxRetries) {
                    Log::warning('Instagram container creation failed with 5xx error, retrying', [
                        'attempt' => $attempt,
                        'status' => $containerResponse->status(),
                        'retry_in' => $retryDelay * $attempt,
                    ]);
                    sleep($retryDelay * $attempt); // Exponential backoff
                    continue;
                }

                // Non-retryable error or max retries exceeded
                $error = $containerResponse->json('error.message', 'Failed to create media container');
                Log::error('Instagram container creation failed', ['error' => $error, 'response' => $containerResponse->json()]);
                return ['success' => false, 'message' => $error];
            }

            $containerId = $containerResponse->json('id');

            if (!$containerId) {
                $error = $containerResponse->json('error.message', 'Media ID is not available');
                Log::error('Instagram container ID missing', ['response' => $containerResponse->json()]);
                return ['success' => false, 'message' => $error];
            }

            // Determine media type for wait timing
            // Carousels take longer since Instagram needs to assemble all children
            $mediaType = count($mediaUrls) > 1 ? 'carousel' : $firstMedia['type'];

            // Wait for container to be ready (required for all media types)
            // Instagram needs time to process the uploaded media before publishing
            $this->waitForContainerReady($containerId, $accessToken, $mediaType);

            // Publish the container
            Log::info('Instagram media_publish starting', [
                'instagram_account_id' => $instagramAccountId,
                'container_id' => $containerId,
            ]);

            $publishResponse = Http::timeout(60)->post(
                "https://graph.facebook.com/v21.0/{$instagramAccountId}/media_publish",
                [
                    'access_token' => $accessToken,
                    'creation_id' => $containerId,
                ]
            );

            // Log full response for debugging
            Log::info('Instagram media_publish response', [
                'status' => $publishResponse->status(),
                'successful' => $publishResponse->successful(),
                'body' => $publishResponse->json(),
            ]);

            if (!$publishResponse->successful()) {
                $error = $publishResponse->json('error.message', 'Unknown error');
                Log::error('Instagram media_publish failed', ['error' => $error, 'full_response' => $publishResponse->json()]);
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
        string $instagramAccountId,
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
                    $instagramAccountId,
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
     * Create carousel children containers with retry logic and waiting
     *
     * @param array $mediaUrls Array of media items with 'url' and 'type'
     * @param string $instagramAccountId The Instagram Business Account ID
     * @param string $accessToken The access token
     * @param array $postOptions Additional options including alt_texts
     * @return string Comma-separated list of child container IDs
     * @throws \Exception If any child container creation fails
     */
    protected function createCarouselChildren(
        array $mediaUrls,
        string $instagramAccountId,
        string $accessToken,
        array $postOptions
    ): string {
        $children = [];
        $altTexts = $postOptions['alt_texts'] ?? [];
        $maxRetries = 3;
        $retryDelay = 2;

        Log::info('Creating carousel children', [
            'instagram_account_id' => $instagramAccountId,
            'media_count' => count($mediaUrls),
        ]);

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

            Log::info("Creating carousel child {$index}", [
                'media_type' => $media['type'],
                'media_url' => $media['url'],
            ]);

            // Create child container with retry logic
            $childResponse = null;
            $childId = null;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $childResponse = Http::timeout(60)->post(
                    "https://graph.facebook.com/v21.0/{$instagramAccountId}/media",
                    $childData
                );

                Log::info("Carousel child {$index} response", [
                    'attempt' => $attempt,
                    'status' => $childResponse->status(),
                    'successful' => $childResponse->successful(),
                    'body' => $childResponse->json(),
                ]);

                if ($childResponse->successful()) {
                    $childId = $childResponse->json('id');
                    break;
                }

                // Retry on 5xx errors
                if ($childResponse->status() >= 500 && $attempt < $maxRetries) {
                    Log::warning("Carousel child {$index} creation failed with 5xx error, retrying", [
                        'attempt' => $attempt,
                        'status' => $childResponse->status(),
                        'retry_in' => $retryDelay * $attempt,
                    ]);
                    sleep($retryDelay * $attempt);
                    continue;
                }

                // Non-retryable error
                $error = $childResponse->json('error.message', 'Failed to create carousel child');
                Log::error("Carousel child {$index} creation failed", [
                    'error' => $error,
                    'response' => $childResponse->json(),
                ]);
                throw new \Exception("Failed to create carousel item {$index}: {$error}");
            }

            if (!$childId) {
                throw new \Exception("Failed to get ID for carousel item {$index}");
            }

            // Wait for child container to be ready before proceeding
            $this->waitForContainerReady($childId, $accessToken, $media['type']);

            $children[] = $childId;
            Log::info("Carousel child {$index} ready", ['child_id' => $childId]);
        }

        Log::info('All carousel children created and ready', [
            'children_count' => count($children),
            'children_ids' => $children,
        ]);

        return implode(',', $children);
    }

    /**
     * Wait for Instagram container to be ready for publishing
     *
     * Instagram processes uploaded media asynchronously. This method polls
     * the container status until it's FINISHED or an error occurs.
     *
     * @param string $containerId The container ID returned from media creation
     * @param string $accessToken The Instagram access token
     * @param string $mediaType The type of media (image, video, carousel)
     * @throws \Exception If container processing fails or times out
     */
    protected function waitForContainerReady(string $containerId, string $accessToken, string $mediaType = 'image'): void
    {
        // Processing times vary: videos longest, carousels medium, images shortest
        // Carousels need extra time for Instagram to assemble all children
        $waitConfig = [
            'video' => ['max_attempts' => 30, 'sleep' => 2],
            'carousel' => ['max_attempts' => 20, 'sleep' => 2],
            'image' => ['max_attempts' => 10, 'sleep' => 1],
        ];

        $config = $waitConfig[$mediaType] ?? $waitConfig['image'];
        $maxAttempts = $config['max_attempts'];
        $sleepSeconds = $config['sleep'];
        $attempt = 0;

        Log::info('Waiting for Instagram container to be ready', [
            'container_id' => $containerId,
            'media_type' => $mediaType,
            'max_attempts' => $maxAttempts,
        ]);

        while ($attempt < $maxAttempts) {
            $statusResponse = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$containerId}", [
                'access_token' => $accessToken,
                'fields' => 'status_code',
            ]);

            $status = $statusResponse->json('status_code');

            Log::debug('Instagram container status check', [
                'container_id' => $containerId,
                'attempt' => $attempt + 1,
                'status' => $status,
            ]);

            if ($status === 'FINISHED') {
                Log::info('Instagram container ready', [
                    'container_id' => $containerId,
                    'attempts_taken' => $attempt + 1,
                ]);
                return;
            }

            if ($status === 'ERROR') {
                $errorMessage = $statusResponse->json('status', 'Unknown processing error');
                Log::error('Instagram container processing failed', [
                    'container_id' => $containerId,
                    'error' => $errorMessage,
                ]);
                throw new \Exception("Media processing failed: {$errorMessage}");
            }

            // Status is likely 'IN_PROGRESS' or similar
            sleep($sleepSeconds);
            $attempt++;
        }

        Log::warning('Instagram container timed out waiting for ready status', [
            'container_id' => $containerId,
            'attempts' => $maxAttempts,
        ]);

        // Even if we time out, try to publish anyway - Instagram might be ready
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

    /**
     * Get the connection platform type for a given post platform
     *
     * Maps social platforms to their connection platform types
     * e.g., facebook/instagram -> meta, tiktok -> tiktok
     */
    protected function getConnectionPlatform(string $platform): string
    {
        return match ($platform) {
            'facebook', 'instagram' => 'meta',
            'tiktok' => 'tiktok',
            'twitter', 'x' => 'twitter',
            'linkedin' => 'linkedin',
            'youtube' => 'google', // YouTube uses Google OAuth connection
            default => $platform,
        };
    }

    /**
     * Route publishing to platform-specific method
     */
    protected function publishToPlatform(
        string $postId,
        string $platform,
        ?string $accountId,
        string $content,
        array $mediaUrls,
        PlatformConnection $connection
    ): array {
        return match ($platform) {
            'facebook', 'instagram' => $this->publishToMeta(
                $postId,
                $platform,
                $accountId,
                $content,
                $mediaUrls,
                $connection
            ),
            'tiktok' => $this->publishToTikTok(
                $postId,
                $content,
                $mediaUrls,
                $connection
            ),
            'youtube' => $this->publishToYouTube(
                $postId,
                $content,
                $mediaUrls,
                $connection
            ),
            default => [
                'success' => false,
                'message' => "{$platform} publishing not yet implemented. Please check back soon.",
            ],
        };
    }

    /**
     * Publish content to TikTok
     *
     * Uses TikTok Content Posting API v2 with FILE_UPLOAD method.
     * Note: Unaudited apps can only post PRIVATE/SELF_ONLY content.
     */
    public function publishToTikTok(
        string $postId,
        string $content,
        array $mediaUrls,
        PlatformConnection $connection
    ): array {
        try {
            Log::info('Publishing to TikTok', [
                'post_id' => $postId,
                'has_media' => !empty($mediaUrls),
                'media_count' => count($mediaUrls),
            ]);

            // TikTok requires video content
            if (empty($mediaUrls)) {
                return [
                    'success' => false,
                    'message' => 'TikTok requires at least one video. Please add a video to your post.',
                ];
            }

            // Find video media
            $videoMedia = null;
            foreach ($mediaUrls as $media) {
                if (($media['type'] ?? 'image') === 'video') {
                    $videoMedia = $media;
                    break;
                }
            }

            if (!$videoMedia) {
                return [
                    'success' => false,
                    'message' => 'TikTok requires a video. No video found in media attachments.',
                ];
            }

            // Get local file path from URL
            $videoPath = $this->getLocalFilePath($videoMedia);
            if (!$videoPath || !file_exists($videoPath)) {
                return [
                    'success' => false,
                    'message' => 'Video file not found on server. Please re-upload the video.',
                ];
            }

            // Use the TikTokPublisher for actual publishing
            $publisher = new TikTokPublisher($connection, 'tiktok');

            $result = $publisher->publish($content, $mediaUrls, [
                'privacy_level' => 'SELF_ONLY', // Safe default for unaudited apps
            ]);

            if ($result['success']) {
                return [
                    'success' => true,
                    'post_id' => $result['external_id'] ?? null,
                    'permalink' => $result['permalink'] ?? null,
                    'message' => 'Published to TikTok successfully. Video is processing.',
                ];
            }

            return [
                'success' => false,
                'message' => $result['error'] ?? 'Failed to publish to TikTok',
            ];

        } catch (\Exception $e) {
            Log::error('TikTok publishing exception', [
                'post_id' => $postId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'TikTok publishing failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Publish content to YouTube
     *
     * Uses YouTube Data API v3 for video uploads.
     * Requires: Google OAuth connection with YouTube scopes.
     */
    public function publishToYouTube(
        string $postId,
        string $content,
        array $mediaUrls,
        PlatformConnection $connection
    ): array {
        try {
            Log::info('Publishing to YouTube', [
                'post_id' => $postId,
                'has_media' => !empty($mediaUrls),
                'media_count' => count($mediaUrls),
            ]);

            // YouTube requires video content
            if (empty($mediaUrls)) {
                return [
                    'success' => false,
                    'message' => 'YouTube requires a video. Please add a video to your post.',
                ];
            }

            // Find video media
            $videoMedia = null;
            foreach ($mediaUrls as $media) {
                if (($media['type'] ?? 'image') === 'video') {
                    $videoMedia = $media;
                    break;
                }
            }

            if (!$videoMedia) {
                return [
                    'success' => false,
                    'message' => 'YouTube requires a video. No video found in media attachments.',
                ];
            }

            // Check if Google connection has YouTube scopes
            $scopes = $connection->scopes ?? [];
            $hasYouTubeScope = false;
            foreach ($scopes as $scope) {
                if (str_contains($scope, 'youtube')) {
                    $hasYouTubeScope = true;
                    break;
                }
            }

            if (!$hasYouTubeScope) {
                return [
                    'success' => false,
                    'message' => 'YouTube authorization is required. Please go to Settings > Platform Connections > Google Assets and connect your YouTube channel.',
                ];
            }

            // Get local file path from URL
            $videoPath = $this->getLocalFilePath($videoMedia);
            if (!$videoPath || !file_exists($videoPath)) {
                return [
                    'success' => false,
                    'message' => 'Video file not found on server. Please re-upload the video.',
                ];
            }

            // Get valid access token (refresh if expired)
            $googleAssetsService = app(GoogleAssetsService::class);
            $accessToken = $googleAssetsService->getValidAccessToken($connection);

            if (!$accessToken) {
                return [
                    'success' => false,
                    'message' => 'Failed to get valid Google access token. Please reconnect your Google account.',
                ];
            }

            // Get post metadata for title
            $postMetadata = DB::table('cmis.social_posts')
                ->where('id', $postId)
                ->first();

            $metadata = $postMetadata->metadata ? json_decode($postMetadata->metadata, true) : [];
            $title = $metadata['title'] ?? substr($content, 0, 100) ?: 'Video from CMIS';
            $description = $content;
            $privacyStatus = $metadata['privacy_status'] ?? 'public';

            // Get target channel from post (account_id) or connection settings
            $targetChannelId = $postMetadata->account_id
                ?? ($connection->account_metadata['selected_youtube_channel'] ?? null);

            // Verify which YouTube channel is currently authorized
            $channelResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://www.googleapis.com/youtube/v3/channels', [
                'part' => 'snippet',
                'mine' => 'true',
            ]);

            $authorizedChannel = null;
            $authorizedChannelTitle = 'Unknown';
            if ($channelResponse->successful()) {
                $channels = $channelResponse->json('items', []);
                if (!empty($channels)) {
                    $authorizedChannel = $channels[0]['id'] ?? null;
                    $authorizedChannelTitle = $channels[0]['snippet']['title'] ?? 'Unknown';
                }
            }

            // Log channel info for debugging
            Log::info('YouTube channel check', [
                'post_id' => $postId,
                'target_channel' => $targetChannelId,
                'authorized_channel' => $authorizedChannel,
                'authorized_channel_title' => $authorizedChannelTitle,
            ]);

            // CRITICAL: Validate channel authorization
            if ($targetChannelId && $authorizedChannel && $targetChannelId !== $authorizedChannel) {
                // Target channel doesn't match authorized channel - ERROR
                $targetChannelName = 'Unknown';
                $youtubeChannels = $connection->account_metadata['youtube_channels'] ?? [];
                foreach ($youtubeChannels as $ch) {
                    if ($ch['id'] === $targetChannelId) {
                        $targetChannelName = $ch['title'] ?? 'Unknown';
                        break;
                    }
                }

                Log::warning('YouTube channel mismatch - blocking upload', [
                    'post_id' => $postId,
                    'target_channel_id' => $targetChannelId,
                    'target_channel_name' => $targetChannelName,
                    'authorized_channel_id' => $authorizedChannel,
                    'authorized_channel_name' => $authorizedChannelTitle,
                ]);

                return [
                    'success' => false,
                    'message' => "YouTube channel mismatch! Post targets '{$targetChannelName}' but OAuth is authorized for '{$authorizedChannelTitle}'. Please re-authorize YouTube with the correct Brand Account.",
                ];
            }

            // WARNING: No target channel selected - will upload to authorized channel
            if (!$targetChannelId && $authorizedChannel) {
                Log::warning('YouTube: No target channel selected, uploading to default authorized channel', [
                    'post_id' => $postId,
                    'authorized_channel_id' => $authorizedChannel,
                    'authorized_channel_name' => $authorizedChannelTitle,
                ]);
            }

            // Step 1: Initialize resumable upload
            $fileSize = filesize($videoPath);
            $initResponse = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                    'X-Upload-Content-Type' => 'video/*',
                    'X-Upload-Content-Length' => $fileSize,
                ])
                ->post('https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status', [
                    'snippet' => [
                        'title' => $title,
                        'description' => $description,
                        'categoryId' => '22', // People & Blogs
                    ],
                    'status' => [
                        'privacyStatus' => $privacyStatus,
                        'selfDeclaredMadeForKids' => false,
                    ],
                ]);

            if (!$initResponse->successful()) {
                $error = $initResponse->json('error.message', 'Failed to initialize YouTube upload');
                Log::error('YouTube upload init failed', [
                    'status' => $initResponse->status(),
                    'error' => $error,
                    'response' => $initResponse->json(),
                ]);
                return ['success' => false, 'message' => $error];
            }

            $uploadUrl = $initResponse->header('Location');
            if (!$uploadUrl) {
                return ['success' => false, 'message' => 'No upload URL returned from YouTube'];
            }

            // Step 2: Upload video file
            Log::info('YouTube uploading video file', [
                'file_size' => $fileSize,
                'upload_url' => $uploadUrl,
            ]);

            $videoContent = file_get_contents($videoPath);
            $uploadResponse = Http::timeout(600) // 10 minutes for large videos
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'video/*',
                    'Content-Length' => strlen($videoContent),
                ])
                ->withBody($videoContent, 'video/*')
                ->put($uploadUrl);

            if (!$uploadResponse->successful()) {
                $error = $uploadResponse->json('error.message', 'Video upload failed');
                Log::error('YouTube video upload failed', [
                    'status' => $uploadResponse->status(),
                    'error' => $error,
                ]);
                return ['success' => false, 'message' => $error];
            }

            $videoId = $uploadResponse->json('id');
            $videoUrl = "https://www.youtube.com/watch?v={$videoId}";

            Log::info('YouTube publish successful', [
                'post_id' => $postId,
                'video_id' => $videoId,
                'url' => $videoUrl,
                'channel_id' => $authorizedChannel,
                'channel_name' => $authorizedChannelTitle,
            ]);

            return [
                'success' => true,
                'post_id' => $videoId,
                'permalink' => $videoUrl,
                'channel_id' => $authorizedChannel,
                'channel_name' => $authorizedChannelTitle,
                'message' => "Published to YouTube channel '{$authorizedChannelTitle}' successfully",
            ];

        } catch (\Exception $e) {
            Log::error('YouTube publishing exception', [
                'post_id' => $postId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'YouTube publishing failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get local file path from media item
     */
    protected function getLocalFilePath(array $mediaItem): ?string
    {
        // Check for storage_path first (from MediaAsset)
        if (!empty($mediaItem['storage_path'])) {
            $path = Storage::disk('public')->path($mediaItem['storage_path']);
            if (file_exists($path)) {
                return $path;
            }
        }

        // Check for processed_path (H.264 converted video)
        if (!empty($mediaItem['processed_path'])) {
            $path = Storage::disk('public')->path($mediaItem['processed_path']);
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try to extract path from URL
        $url = $mediaItem['processed_url'] ?? $mediaItem['url'] ?? $mediaItem['preview_url'] ?? null;
        if ($url) {
            // Handle /storage/ URLs
            if (preg_match('#/storage/(.+)$#', $url, $matches)) {
                $path = Storage::disk('public')->path($matches[1]);
                if (file_exists($path)) {
                    return $path;
                }
            }

            // Handle full public path
            $publicPath = public_path('storage/' . basename(parse_url($url, PHP_URL_PATH)));
            if (file_exists($publicPath)) {
                return $publicPath;
            }
        }

        // Check for path directly
        if (!empty($mediaItem['path'])) {
            $path = Storage::disk('public')->path($mediaItem['path']);
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
