<?php

namespace App\Services\Social\TikTok;

use App\Services\Social\AbstractSocialPlatform;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * TikTok Content Posting API Service
 *
 * IMPORTANT: Requires audit approval for PUBLIC posting
 * - Unaudited apps: All posts are PRIVATE only
 * - Audited apps: Can post PUBLIC content
 *
 * Latest API version: v2 (2025)
 * New features: Photo carousel support
 */
class TikTokSocialService extends AbstractSocialPlatform
{
    protected string $apiVersion = 'v2';
    protected string $baseUrl = 'https://open.tiktokapis.com';

    protected function getPlatformName(): string
    {
        return 'tiktok';
    }

    public function publish(array $content): array
    {
        $this->validateContent($content);

        $postType = $content['post_type'] ?? 'video';

        return match($postType) {
            'video' => $this->publishVideo($content),
            'photo' => $this->publishPhotos($content),
            default => throw new \Exception("Unsupported TikTok post type: {$postType}"),
        };
    }

    /**
     * Publish video to TikTok
     */
    protected function publishVideo(array $content): array
    {
        $videoSource = $content['video_source'] ?? 'FILE_UPLOAD';
        $videoUrl = $content['video_url'] ?? null;
        $videoFile = $content['video_file'] ?? null;
        $title = $content['text'] ?? '';
        $privacyLevel = $content['privacy_level'] ?? 'PUBLIC_TO_EVERYONE';
        $disableComment = $content['disable_comment'] ?? false;
        $disableDuet = $content['disable_duet'] ?? false;
        $disableStitch = $content['disable_stitch'] ?? false;

        $postInfo = [
            'title' => $title,
            'privacy_level' => $privacyLevel,
            'disable_comment' => $disableComment,
            'disable_duet' => $disableDuet,
            'disable_stitch' => $disableStitch,
        ];

        // Method 1: Upload from file
        if ($videoSource === 'FILE_UPLOAD' && $videoFile) {
            return $this->uploadVideoFile($videoFile, $postInfo);
        }

        // Method 2: Pull from URL
        if ($videoSource === 'PULL_FROM_URL' && $videoUrl) {
            return $this->uploadVideoFromUrl($videoUrl, $postInfo);
        }

        throw new \InvalidArgumentException('Must provide either video_file or video_url');
    }

    /**
     * Upload video from local file
     */
    protected function uploadVideoFile(string $filePath, array $postInfo): array
    {
        // Step 1: Initialize upload
        $initResponse = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/post/publish/video/init/",
            [
                'post_info' => $postInfo,
                'source_info' => [
                    'source' => 'FILE_UPLOAD',
                    'video_size' => filesize($filePath),
                ],
            ],
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        $uploadUrl = $initResponse['data']['upload_url'];
        $publishId = $initResponse['data']['publish_id'];

        // Step 2: Upload video file
        $videoContent = file_get_contents($filePath);

        $uploadResponse = Http::timeout(300)
            ->withHeaders([
                'Content-Type' => 'video/mp4',
                'Content-Length' => strlen($videoContent),
            ])
            ->withBody($videoContent, 'video/mp4')
            ->put($uploadUrl);

        if (!$uploadResponse->successful()) {
            throw new \Exception('Video upload failed: ' . $uploadResponse->body());
        }

        return [
            'external_id' => $publishId,
            'url' => null, // TikTok doesn't return URL immediately
            'platform_data' => [
                'publish_id' => $publishId,
                'status' => 'processing',
            ],
        ];
    }

    /**
     * Upload video from URL
     */
    protected function uploadVideoFromUrl(string $videoUrl, array $postInfo): array
    {
        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/post/publish/video/init/",
            [
                'post_info' => $postInfo,
                'source_info' => [
                    'source' => 'PULL_FROM_URL',
                    'video_url' => $videoUrl,
                ],
            ],
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        $publishId = $response['data']['publish_id'];

        return [
            'external_id' => $publishId,
            'url' => null,
            'platform_data' => [
                'publish_id' => $publishId,
                'status' => 'processing',
            ],
        ];
    }

    /**
     * Publish photo carousel (NEW 2025)
     */
    protected function publishPhotos(array $content): array
    {
        $photoFiles = $content['photo_files'] ?? [];
        $photoUrls = $content['photo_urls'] ?? [];
        $title = $content['text'] ?? '';
        $privacyLevel = $content['privacy_level'] ?? 'PUBLIC_TO_EVERYONE';

        if (empty($photoFiles) && empty($photoUrls)) {
            throw new \InvalidArgumentException('Must provide photo files or URLs');
        }

        $postInfo = [
            'title' => $title,
            'privacy_level' => $privacyLevel,
        ];

        // Upload photos and create carousel
        // Note: Exact implementation depends on final TikTok API spec for photos
        // This is a placeholder based on video upload pattern

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/post/publish/content/init/",
            [
                'post_info' => $postInfo,
                'source_info' => [
                    'source' => 'PHOTO_UPLOAD',
                    'photo_count' => count($photoFiles),
                ],
            ],
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        return [
            'external_id' => $response['data']['publish_id'] ?? null,
            'url' => null,
            'platform_data' => $response['data'] ?? [],
        ];
    }

    public function schedule(array $content, \DateTime $scheduledTime): array
    {
        // TikTok doesn't support native scheduling via API
        // Use queue-based scheduling
        return [
            'scheduled' => true,
            'scheduled_for' => $scheduledTime->format('c'),
            'method' => 'queue',
        ];
    }

    public function validateContent(array $content): bool
    {
        $postType = $content['post_type'] ?? 'video';

        if ($postType === 'video') {
            if (!isset($content['video_file']) && !isset($content['video_url'])) {
                throw new \InvalidArgumentException('Video file or URL required');
            }

            // Validate video file if provided
            if (isset($content['video_file'])) {
                $this->validateMediaFile(
                    $content['video_file'],
                    ['video/mp4', 'video/quicktime'],
                    4096 // 4GB max
                );
            }
        }

        if ($postType === 'photo') {
            if (!isset($content['photo_files']) && !isset($content['photo_urls'])) {
                throw new \InvalidArgumentException('Photo files or URLs required');
            }
        }

        // Title validation
        if (isset($content['text'])) {
            $this->validateTextLength($content['text'], 2200, 'title');
        }

        return true;
    }

    public function getPostTypes(): array
    {
        return [
            [
                'value' => 'video',
                'label' => 'فيديو',
                'icon' => 'fa-video',
                'description' => 'Video up to 10 minutes (requires audit for public)',
            ],
            [
                'value' => 'photo',
                'label' => 'صور',
                'icon' => 'fa-images',
                'description' => 'Photo carousel (NEW 2025)',
            ],
        ];
    }

    public function getMediaRequirements(): array
    {
        return [
            'video' => [
                'formats' => ['MP4', 'MOV', 'WEBM'],
                'max_size_mb' => 4096,
                'max_duration_seconds' => 600, // 10 minutes
                'min_duration_seconds' => 3,
                'resolution' => '720p minimum, 1080p recommended',
                'aspect_ratio' => '9:16 (vertical), 16:9 (horizontal), 1:1 (square)',
            ],
            'photo' => [
                'formats' => ['JPEG', 'PNG', 'WEBP'],
                'max_size_mb' => 10,
                'min_count' => 1,
                'max_count' => 35,
                'aspect_ratio' => '9:16, 16:9, or 1:1',
            ],
        ];
    }

    public function getTextLimits(): array
    {
        return [
            'video' => [
                'min' => 1,
                'max' => 2200,
            ],
            'photo' => [
                'min' => 1,
                'max' => 2200,
            ],
        ];
    }

    protected function uploadMedia(string $filePath, string $mediaType): string
    {
        // TikTok uses specialized upload flow, not generic media upload
        throw new \Exception('Use publishVideo or publishPhotos methods directly');
    }

    /**
     * Check publishing status
     */
    public function checkPublishStatus(string $publishId): array
    {
        try {
            $response = $this->makeRequest(
                'post',
                "{$this->baseUrl}/{$this->apiVersion}/post/publish/status/fetch/",
                [
                    'publish_id' => $publishId,
                ],
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ]
            );

            return $response['data'] ?? [];
        } catch (\Exception $e) {
            $this->logError('check_status', $e, ['publish_id' => $publishId]);
            throw $e;
        }
    }

    /**
     * Get video info after publishing
     */
    public function getAnalytics(string $externalPostId): array
    {
        // TikTok analytics require separate API calls
        // This is a placeholder - implement based on TikTok Analytics API
        return [];
    }
}
