<?php

namespace App\Services\Social\Publishers;

use Illuminate\Support\Facades\Http;

/**
 * Publisher for Meta platforms (Facebook and Instagram).
 */
class MetaPublisher extends AbstractPublisher
{
    protected const API_VERSION = 'v21.0';
    protected const API_BASE = 'https://graph.facebook.com/';

    /**
     * Publish content to Facebook or Instagram.
     */
    public function publish(string $content, array $media, array $options = []): array
    {
        // Log received media for debugging
        $this->logInfo('MetaPublisher::publish received', [
            'platform' => $this->platform,
            'media_count' => count($media),
            'media_items' => array_map(fn($m) => [
                'type' => $m['type'] ?? 'unknown',
                'url' => $m['url'] ?? $m['preview_url'] ?? 'no-url',
            ], $media),
        ]);

        $selectedAssets = $this->getSelectedAssets();

        // Handle both singular and plural key names for backward compatibility
        $selectedPageIds = $selectedAssets['pages'] ?? $selectedAssets['page'] ?? [];
        $selectedInstagramIds = $selectedAssets['instagram_accounts'] ?? $selectedAssets['instagram_account'] ?? [];

        // Also check top-level metadata for legacy format
        $metadata = $this->getMetadata();
        if (empty($selectedPageIds) && !empty($metadata['facebook_page_id'])) {
            $selectedPageIds = [$metadata['facebook_page_id']];
        }
        if (empty($selectedInstagramIds) && !empty($metadata['instagram_account_id'])) {
            $selectedInstagramIds = [$metadata['instagram_account_id']];
        }

        if ($this->platform === 'facebook') {
            return $this->publishToFacebook($content, $media, $selectedPageIds, $options);
        }

        if ($this->platform === 'instagram') {
            return $this->publishToInstagram($content, $media, $selectedInstagramIds, $selectedPageIds, $options);
        }

        return $this->failure("Unknown Meta platform: {$this->platform}");
    }

    /**
     * Publish to Facebook Page.
     */
    protected function publishToFacebook(string $content, array $media, array $pageIds, array $options): array
    {
        if (empty($pageIds)) {
            return $this->failure('No Facebook page selected. Configure in Settings > Platform Connections > Meta > Assets.');
        }

        $pageId = $pageIds[0];

        try {
            // Get page access token
            $pageToken = $this->getPageToken($pageId);
            if (!$pageToken) {
                return $this->failure('Failed to get page token');
            }

            // Determine post type based on media
            if (!empty($media)) {
                return $this->publishFacebookWithMedia($content, $media, $pageId, $pageToken);
            }

            // Text-only post
            return $this->publishFacebookText($content, $pageId, $pageToken);
        } catch (\Exception $e) {
            $this->logError('Facebook publish failed', ['error' => $e->getMessage()]);
            return $this->failure($e->getMessage());
        }
    }

    /**
     * Get page access token from user token.
     */
    protected function getPageToken(string $pageId): ?string
    {
        $result = $this->httpGet(
            self::API_BASE . self::API_VERSION . "/{$pageId}",
            [
                'access_token' => $this->getAccessToken(),
                'fields' => 'access_token,name',
            ]
        );

        if (!$result['success']) {
            $this->logError('Failed to get page token', ['error' => $result['error'] ?? 'Unknown']);
            return null;
        }

        $this->logInfo('Got page token', ['page_name' => $result['data']['name'] ?? 'Unknown']);
        return $result['data']['access_token'] ?? null;
    }

    /**
     * Publish text-only post to Facebook.
     */
    protected function publishFacebookText(string $content, string $pageId, string $pageToken): array
    {
        $result = $this->httpPost(
            self::API_BASE . self::API_VERSION . "/{$pageId}/feed",
            [
                'access_token' => $pageToken,
                'message' => $content,
            ]
        );

        if (!$result['success']) {
            return $this->failure($result['error'] ?? 'Failed to publish to Facebook');
        }

        $postId = $result['data']['id'] ?? null;
        return $this->success($postId, "https://www.facebook.com/{$postId}");
    }

    /**
     * Publish post with media to Facebook.
     */
    protected function publishFacebookWithMedia(string $content, array $media, string $pageId, string $pageToken): array
    {
        $firstMedia = $media[0];
        $mediaUrl = $firstMedia['url'] ?? $firstMedia['preview_url'] ?? null;

        if (!$mediaUrl) {
            return $this->publishFacebookText($content, $pageId, $pageToken);
        }

        $isVideo = ($firstMedia['type'] ?? 'image') === 'video';

        if ($isVideo) {
            return $this->publishFacebookVideo($content, $mediaUrl, $pageId, $pageToken);
        }

        return $this->publishFacebookPhoto($content, $mediaUrl, $pageId, $pageToken);
    }

    /**
     * Publish photo to Facebook.
     */
    protected function publishFacebookPhoto(string $content, string $imageUrl, string $pageId, string $pageToken): array
    {
        $result = $this->httpPost(
            self::API_BASE . self::API_VERSION . "/{$pageId}/photos",
            [
                'access_token' => $pageToken,
                'url' => $imageUrl,
                'caption' => $content,
            ]
        );

        if (!$result['success']) {
            return $this->failure($result['error'] ?? 'Failed to publish photo to Facebook');
        }

        $postId = $result['data']['id'] ?? $result['data']['post_id'] ?? null;
        return $this->success($postId, "https://www.facebook.com/{$postId}");
    }

    /**
     * Publish video to Facebook.
     */
    protected function publishFacebookVideo(string $content, string $videoUrl, string $pageId, string $pageToken): array
    {
        $result = $this->httpPost(
            self::API_BASE . self::API_VERSION . "/{$pageId}/videos",
            [
                'access_token' => $pageToken,
                'file_url' => $videoUrl,
                'description' => $content,
            ]
        );

        if (!$result['success']) {
            return $this->failure($result['error'] ?? 'Failed to publish video to Facebook');
        }

        $postId = $result['data']['id'] ?? null;
        return $this->success($postId, "https://www.facebook.com/{$postId}");
    }

    /**
     * Publish to Instagram.
     */
    protected function publishToInstagram(string $content, array $media, array $instagramIds, array $pageIds, array $options): array
    {
        if (empty($instagramIds)) {
            return $this->failure('No Instagram account selected. Configure in Settings > Platform Connections > Meta > Assets.');
        }

        if (empty($media)) {
            return $this->failure('Instagram requires at least one image or video');
        }

        $instagramId = $instagramIds[0];
        $pageId = !empty($pageIds) ? $pageIds[0] : null;

        // Get page token for Instagram publishing
        $accessToken = $this->getAccessToken();
        if ($pageId) {
            $pageToken = $this->getPageToken($pageId);
            if ($pageToken) {
                $accessToken = $pageToken;
            }
        }

        try {
            // Check if this is a carousel (multiple media items)
            if (count($media) > 1) {
                return $this->publishInstagramCarousel($instagramId, $content, $media, $accessToken);
            }

            // Single media item
            $firstMedia = $media[0];
            $mediaUrl = $firstMedia['url'] ?? $firstMedia['preview_url'] ?? null;

            if (!$mediaUrl) {
                return $this->failure('No valid media URL found');
            }

            $isVideo = ($firstMedia['type'] ?? 'image') === 'video';

            // Step 1: Create media container
            $containerResult = $this->createInstagramMediaContainer($instagramId, $mediaUrl, $content, $isVideo, $accessToken);
            if (!$containerResult['success']) {
                return $containerResult;
            }

            $containerId = $containerResult['container_id'];

            // Step 2: Wait for media processing
            $readyResult = $this->waitForInstagramMediaReady($containerId, $accessToken);
            if (!$readyResult['success']) {
                return $readyResult;
            }

            // Step 3: Publish the container
            return $this->publishInstagramContainer($instagramId, $containerId, $accessToken);
        } catch (\Exception $e) {
            $this->logError('Instagram publish failed', ['error' => $e->getMessage()]);
            return $this->failure($e->getMessage());
        }
    }

    /**
     * Publish an Instagram carousel (multiple images/videos).
     */
    protected function publishInstagramCarousel(string $instagramId, string $content, array $media, string $accessToken): array
    {
        $this->logInfo('Publishing Instagram carousel', [
            'instagram_id' => $instagramId,
            'media_count' => count($media),
        ]);

        $childrenIds = [];

        // Step 1: Create child containers for each media item
        foreach ($media as $index => $item) {
            $mediaUrl = $item['url'] ?? $item['preview_url'] ?? null;
            if (!$mediaUrl) {
                $this->logError("Carousel item {$index} has no valid URL");
                continue;
            }

            $isVideo = ($item['type'] ?? 'image') === 'video';

            // Create child container (is_carousel_item = true)
            $childResult = $this->createInstagramCarouselChild($instagramId, $mediaUrl, $isVideo, $accessToken);
            if (!$childResult['success']) {
                return $this->failure("Failed to create carousel item {$index}: " . ($childResult['message'] ?? 'Unknown error'));
            }

            $childId = $childResult['container_id'];

            // Wait for child to be ready
            $readyResult = $this->waitForInstagramMediaReady($childId, $accessToken);
            if (!$readyResult['success']) {
                return $this->failure("Carousel item {$index} processing failed");
            }

            $childrenIds[] = $childId;
            $this->logInfo("Carousel child {$index} ready", ['child_id' => $childId]);
        }

        if (empty($childrenIds)) {
            return $this->failure('No valid carousel items created');
        }

        // Step 2: Create the parent carousel container
        $this->logInfo('Creating carousel parent container', [
            'children_count' => count($childrenIds),
        ]);

        $carouselResult = $this->httpPost(
            self::API_BASE . self::API_VERSION . "/{$instagramId}/media",
            [
                'access_token' => $accessToken,
                'caption' => $content,
                'media_type' => 'CAROUSEL',
                'children' => implode(',', $childrenIds),
            ]
        );

        if (!$carouselResult['success']) {
            return $this->failure('Failed to create carousel container: ' . ($carouselResult['error'] ?? 'Unknown error'));
        }

        $carouselId = $carouselResult['data']['id'] ?? null;
        if (!$carouselId) {
            return $this->failure('Carousel container created but no ID returned');
        }

        // Step 3: Wait for carousel to be ready
        $readyResult = $this->waitForInstagramMediaReady($carouselId, $accessToken, 60);
        if (!$readyResult['success']) {
            return $readyResult;
        }

        // Step 4: Publish the carousel
        $this->logInfo('Publishing carousel', ['carousel_id' => $carouselId]);
        return $this->publishInstagramContainer($instagramId, $carouselId, $accessToken);
    }

    /**
     * Create a carousel child container.
     */
    protected function createInstagramCarouselChild(string $instagramId, string $mediaUrl, bool $isVideo, string $accessToken): array
    {
        $params = [
            'access_token' => $accessToken,
            'is_carousel_item' => true,
        ];

        if ($isVideo) {
            $params['media_type'] = 'VIDEO';
            $params['video_url'] = $mediaUrl;
        } else {
            $params['image_url'] = $mediaUrl;
        }

        $result = $this->httpPost(
            self::API_BASE . self::API_VERSION . "/{$instagramId}/media",
            $params
        );

        if (!$result['success']) {
            return $this->failure('Failed to create carousel child: ' . ($result['error'] ?? 'Unknown error'));
        }

        $containerId = $result['data']['id'] ?? null;
        if (!$containerId) {
            return $this->failure('Carousel child created but no ID returned');
        }

        return [
            'success' => true,
            'container_id' => $containerId,
        ];
    }

    /**
     * Create Instagram media container.
     */
    protected function createInstagramMediaContainer(string $instagramId, string $mediaUrl, string $caption, bool $isVideo, string $accessToken): array
    {
        $params = [
            'access_token' => $accessToken,
            'caption' => $caption,
        ];

        if ($isVideo) {
            $params['media_type'] = 'VIDEO';
            $params['video_url'] = $mediaUrl;
        } else {
            $params['image_url'] = $mediaUrl;
        }

        // Debug logging
        $this->logInfo('Creating Instagram media container', [
            'instagram_id' => $instagramId,
            'media_url' => $mediaUrl,
            'is_video' => $isVideo,
            'caption_length' => strlen($caption),
        ]);

        $result = $this->httpPost(
            self::API_BASE . self::API_VERSION . "/{$instagramId}/media",
            $params
        );

        // Log the full API response for debugging
        $this->logInfo('Instagram API response for media container', [
            'success' => $result['success'] ?? false,
            'data' => $result['data'] ?? null,
            'error' => $result['error'] ?? null,
            'response' => $result['response'] ?? null,
        ]);

        if (!$result['success']) {
            $this->logError('Failed to create Instagram media container', [
                'error' => $result['error'] ?? 'Unknown error',
                'response' => $result['response'] ?? [],
                'media_url' => $mediaUrl,
            ]);
            return $this->failure('Failed to create media container: ' . ($result['error'] ?? 'Unknown error'));
        }

        // Check if Instagram returned an error in the response body
        if (isset($result['data']['error'])) {
            $errorMsg = $result['data']['error']['message'] ?? 'Instagram API error';
            $this->logError('Instagram returned error in response', [
                'error' => $result['data']['error'],
                'media_url' => $mediaUrl,
            ]);
            return $this->failure($errorMsg);
        }

        $containerId = $result['data']['id'] ?? null;

        if (!$containerId) {
            $this->logError('Media container created but no ID returned', [
                'full_result' => $result,
                'data_keys' => is_array($result['data']) ? array_keys($result['data']) : 'not_array',
                'media_url' => $mediaUrl,
            ]);
            return $this->failure('Media ID is not available');
        }

        return [
            'success' => true,
            'container_id' => $containerId,
        ];
    }

    /**
     * Wait for Instagram media to be ready.
     */
    protected function waitForInstagramMediaReady(string $containerId, string $accessToken, int $maxWait = 120): array
    {
        $startTime = time();

        while ((time() - $startTime) < $maxWait) {
            $result = $this->httpGet(
                self::API_BASE . self::API_VERSION . "/{$containerId}",
                [
                    'access_token' => $accessToken,
                    'fields' => 'status_code',
                ]
            );

            if (!$result['success']) {
                return $this->failure('Failed to check media status');
            }

            $status = $result['data']['status_code'] ?? 'IN_PROGRESS';

            if ($status === 'FINISHED') {
                return ['success' => true];
            }

            if ($status === 'ERROR') {
                return $this->failure('Media processing failed');
            }

            sleep(5);
        }

        return $this->failure('Media processing timed out');
    }

    /**
     * Publish Instagram container.
     */
    protected function publishInstagramContainer(string $instagramId, string $containerId, string $accessToken): array
    {
        $result = $this->httpPost(
            self::API_BASE . self::API_VERSION . "/{$instagramId}/media_publish",
            [
                'access_token' => $accessToken,
                'creation_id' => $containerId,
            ]
        );

        if (!$result['success']) {
            return $this->failure($result['error'] ?? 'Failed to publish to Instagram');
        }

        $postId = $result['data']['id'] ?? null;

        // Fetch the actual permalink from Instagram API
        // The numeric ID can't be used directly in URLs - need the shortcode-based permalink
        $permalink = $this->getInstagramPermalink($postId, $accessToken);

        return $this->success($postId, $permalink);
    }

    /**
     * Get the permalink for an Instagram media post.
     */
    protected function getInstagramPermalink(string $mediaId, string $accessToken): ?string
    {
        $result = $this->httpGet(
            self::API_BASE . self::API_VERSION . "/{$mediaId}",
            [
                'access_token' => $accessToken,
                'fields' => 'permalink',
            ]
        );

        if ($result['success'] && !empty($result['data']['permalink'])) {
            return $result['data']['permalink'];
        }

        // Fallback: return null if we can't get the permalink
        $this->logError('Could not fetch Instagram permalink', [
            'media_id' => $mediaId,
            'error' => $result['error'] ?? 'Unknown error',
        ]);

        return null;
    }
}
