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

        $firstMedia = $media[0];
        $mediaUrl = $firstMedia['url'] ?? $firstMedia['preview_url'] ?? null;

        if (!$mediaUrl) {
            return $this->failure('No valid media URL found');
        }

        $isVideo = ($firstMedia['type'] ?? 'image') === 'video';

        try {
            // Step 1: Create media container
            $containerResult = $this->createInstagramMediaContainer($instagramId, $mediaUrl, $content, $isVideo, $accessToken);
            if (!$containerResult['success']) {
                return $containerResult;
            }

            $containerId = $containerResult['container_id'];

            // Step 2: Wait for media processing (for videos)
            if ($isVideo) {
                $readyResult = $this->waitForInstagramMediaReady($containerId, $accessToken);
                if (!$readyResult['success']) {
                    return $readyResult;
                }
            }

            // Step 3: Publish the container
            return $this->publishInstagramContainer($instagramId, $containerId, $accessToken);
        } catch (\Exception $e) {
            $this->logError('Instagram publish failed', ['error' => $e->getMessage()]);
            return $this->failure($e->getMessage());
        }
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
        return $this->success($postId, "https://www.instagram.com/p/{$postId}");
    }
}
