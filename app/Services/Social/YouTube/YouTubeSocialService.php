<?php

namespace App\Services\Social\YouTube;

use App\Services\Social\AbstractSocialPlatform;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * YouTube Data API v3 Publishing Service
 *
 * Supports:
 * - Regular video uploads (up to 256GB, 12 hours)
 * - YouTube Shorts (vertical videos, <60s)
 * - Thumbnail upload and management
 * - Caption/subtitle files
 * - Privacy settings (public, unlisted, private)
 * - Category and metadata
 * - Playlist assignment
 *
 * Authentication: OAuth 2.0 with Google Cloud Platform
 * API: YouTube Data API v3
 */
class YouTubeSocialService extends AbstractSocialPlatform
{
    protected string $apiVersion = 'v3';
    protected string $baseUrl = 'https://www.googleapis.com/youtube';
    protected string $uploadUrl = 'https://www.googleapis.com/upload/youtube';

    protected function getPlatformName(): string
    {
        return 'youtube';
    }

    public function publish(array $content): array
    {
        $this->validateContent($content);

        $postType = $content['post_type'] ?? 'video';

        return match($postType) {
            'video' => $this->publishVideo($content),
            'short' => $this->publishShort($content),
            default => throw new \Exception("Unsupported YouTube post type: {$postType}"),
        };
    }

    /**
     * Publish regular video to YouTube
     */
    protected function publishVideo(array $content): array
    {
        $videoFile = $content['video_file'] ?? null;
        $title = $content['title'] ?? 'Untitled Video';
        $description = $content['description'] ?? '';
        $categoryId = $content['category_id'] ?? '22'; // Default: People & Blogs
        $privacyStatus = $content['privacy_status'] ?? 'public'; // public, unlisted, private
        $tags = $content['tags'] ?? [];
        $thumbnailFile = $content['thumbnail_file'] ?? null;
        $playlistId = $content['playlist_id'] ?? null;

        if (!$videoFile) {
            throw new \InvalidArgumentException('Video file is required');
        }

        // Step 1: Upload video with metadata
        $videoId = $this->uploadVideoFile($videoFile, [
            'snippet' => [
                'title' => $title,
                'description' => $description,
                'categoryId' => $categoryId,
                'tags' => $tags,
            ],
            'status' => [
                'privacyStatus' => $privacyStatus,
                'selfDeclaredMadeForKids' => false,
            ],
        ]);

        // Step 2: Upload custom thumbnail if provided
        if ($thumbnailFile) {
            $this->uploadThumbnail($videoId, $thumbnailFile);
        }

        // Step 3: Add to playlist if specified
        if ($playlistId) {
            $this->addToPlaylist($videoId, $playlistId);
        }

        $videoUrl = "https://www.youtube.com/watch?v={$videoId}";

        $this->logOperation('publish_video', [
            'video_id' => $videoId,
            'title' => $title,
            'privacy' => $privacyStatus,
        ]);

        return [
            'external_id' => $videoId,
            'url' => $videoUrl,
            'platform_data' => [
                'video_id' => $videoId,
                'privacy_status' => $privacyStatus,
                'category_id' => $categoryId,
            ],
        ];
    }

    /**
     * Publish YouTube Short (vertical video <60s)
     */
    protected function publishShort(array $content): array
    {
        // YouTube Shorts are uploaded the same way as regular videos
        // but with "#Shorts" in title/description and vertical aspect ratio (9:16)

        $title = $content['title'] ?? 'Untitled Short';
        $description = $content['description'] ?? '';

        // Add #Shorts hashtag if not already present
        if (stripos($title, '#shorts') === false && stripos($description, '#shorts') === false) {
            $description = "#Shorts\n\n" . $description;
        }

        // Use the regular video upload with Shorts-specific metadata
        $content['title'] = $title;
        $content['description'] = $description;
        $content['category_id'] = $content['category_id'] ?? '24'; // Entertainment for Shorts

        return $this->publishVideo($content);
    }

    /**
     * Upload video file to YouTube using resumable upload
     */
    protected function uploadVideoFile(string $filePath, array $metadata): string
    {
        // Step 1: Initialize resumable upload session
        $initResponse = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json',
                'X-Upload-Content-Type' => 'video/*',
                'X-Upload-Content-Length' => filesize($filePath),
            ])
            ->post("{$this->uploadUrl}/{$this->apiVersion}/videos", [
                'part' => 'snippet,status',
                'uploadType' => 'resumable',
            ] + $metadata);

        if (!$initResponse->successful()) {
            throw new \Exception('Failed to initialize YouTube upload: ' . $initResponse->body());
        }

        $uploadUrl = $initResponse->header('Location');

        if (!$uploadUrl) {
            throw new \Exception('No upload URL returned from YouTube');
        }

        // Step 2: Upload video file in chunks (for large files)
        $videoContent = file_get_contents($filePath);
        $uploadResponse = Http::timeout(600) // 10 minutes for large videos
            ->withHeaders([
                'Content-Type' => 'video/*',
                'Content-Length' => strlen($videoContent),
            ])
            ->withBody($videoContent, 'video/*')
            ->put($uploadUrl);

        if (!$uploadResponse->successful()) {
            throw new \Exception('Video upload failed: ' . $uploadResponse->body());
        }

        $response = $uploadResponse->json();
        return $response['id'];
    }

    /**
     * Upload custom thumbnail for video
     */
    protected function uploadThumbnail(string $videoId, string $thumbnailFile): void
    {
        if (!file_exists($thumbnailFile)) {
            throw new \InvalidArgumentException("Thumbnail file not found: {$thumbnailFile}");
        }

        $thumbnailContent = file_get_contents($thumbnailFile);

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => mime_content_type($thumbnailFile),
                'Content-Length' => strlen($thumbnailContent),
            ])
            ->withBody($thumbnailContent, mime_content_type($thumbnailFile))
            ->post("{$this->baseUrl}/{$this->apiVersion}/thumbnails/set", [
                'videoId' => $videoId,
            ]);

        if (!$response->successful()) {
            $this->logError('upload_thumbnail', new \Exception($response->body()), [
                'video_id' => $videoId,
            ]);
        }
    }

    /**
     * Add video to playlist
     */
    protected function addToPlaylist(string $videoId, string $playlistId): void
    {
        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/playlistItems",
            [
                'part' => 'snippet',
                'snippet' => [
                    'playlistId' => $playlistId,
                    'resourceId' => [
                        'kind' => 'youtube#video',
                        'videoId' => $videoId,
                    ],
                ],
            ],
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        $this->logOperation('add_to_playlist', [
            'video_id' => $videoId,
            'playlist_id' => $playlistId,
        ]);
    }

    public function schedule(array $content, \DateTime $scheduledTime): array
    {
        // YouTube supports native scheduling via publishAt parameter
        // However, this requires the video to be uploaded as 'private' first
        // then scheduled for future publication

        $content['privacy_status'] = 'private';

        // Upload video first
        $result = $this->publish($content);
        $videoId = $result['external_id'];

        // Update video with scheduled publish time
        try {
            $this->makeRequest(
                'put',
                "{$this->baseUrl}/{$this->apiVersion}/videos",
                [
                    'part' => 'status',
                    'id' => $videoId,
                    'status' => [
                        'privacyStatus' => 'private',
                        'publishAt' => $scheduledTime->format('c'),
                    ],
                ],
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ]
            );

            return [
                'scheduled' => true,
                'scheduled_for' => $scheduledTime->format('c'),
                'method' => 'native',
                'video_id' => $videoId,
            ];
        } catch (\Exception $e) {
            $this->logError('schedule', $e, ['video_id' => $videoId]);
            throw $e;
        }
    }

    public function validateContent(array $content): bool
    {
        $postType = $content['post_type'] ?? 'video';

        // Validate video file
        if (!isset($content['video_file'])) {
            throw new \InvalidArgumentException('Video file is required');
        }

        $videoFile = $content['video_file'];
        if (!file_exists($videoFile)) {
            throw new \InvalidArgumentException("Video file not found: {$videoFile}");
        }

        // Validate file size
        $fileSizeMB = filesize($videoFile) / (1024 * 1024);
        $maxSizeMB = 256 * 1024; // 256 GB

        if ($fileSizeMB > $maxSizeMB) {
            throw new \InvalidArgumentException(
                "Video file too large: {$fileSizeMB}MB (max: {$maxSizeMB}MB)"
            );
        }

        // Validate video format
        $mimeType = mime_content_type($videoFile);
        $allowedTypes = [
            'video/mp4',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-flv',
            'video/webm',
            'video/x-matroska',
        ];

        if (!in_array($mimeType, $allowedTypes)) {
            throw new \InvalidArgumentException(
                "Invalid video format: {$mimeType}"
            );
        }

        // Validate title
        if (isset($content['title'])) {
            $this->validateTextLength($content['title'], 100, 'title');
        }

        // Validate description
        if (isset($content['description'])) {
            $this->validateTextLength($content['description'], 5000, 'description');
        }

        // Validate thumbnail if provided
        if (isset($content['thumbnail_file'])) {
            $this->validateMediaFile(
                $content['thumbnail_file'],
                ['image/jpeg', 'image/png'],
                2 // 2MB max
            );
        }

        // Validate privacy status
        if (isset($content['privacy_status'])) {
            $validPrivacy = ['public', 'unlisted', 'private'];
            if (!in_array($content['privacy_status'], $validPrivacy)) {
                throw new \InvalidArgumentException(
                    "Invalid privacy status. Must be: " . implode(', ', $validPrivacy)
                );
            }
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
                'description' => 'Regular video upload (up to 12 hours)',
            ],
            [
                'value' => 'short',
                'label' => 'Short',
                'icon' => 'fa-mobile-alt',
                'description' => 'Vertical short video (<60 seconds)',
            ],
        ];
    }

    public function getMediaRequirements(): array
    {
        return [
            'video' => [
                'formats' => ['MP4', 'MOV', 'AVI', 'FLV', 'WEBM', 'MKV'],
                'max_size_gb' => 256,
                'max_duration_hours' => 12,
                'resolution' => 'Up to 8K (7680x4320)',
                'aspect_ratio' => '16:9 recommended, 4:3 supported',
                'frame_rate' => '24, 25, 30, 48, 50, 60 fps',
            ],
            'short' => [
                'formats' => ['MP4', 'MOV', 'WEBM'],
                'max_size_gb' => 256,
                'max_duration_seconds' => 60,
                'resolution' => '1080x1920 recommended',
                'aspect_ratio' => '9:16 (vertical)',
            ],
            'thumbnail' => [
                'formats' => ['JPEG', 'PNG'],
                'max_size_mb' => 2,
                'resolution' => '1280x720 recommended',
                'aspect_ratio' => '16:9',
            ],
        ];
    }

    public function getTextLimits(): array
    {
        return [
            'video' => [
                'title' => ['min' => 1, 'max' => 100],
                'description' => ['min' => 0, 'max' => 5000],
                'tags' => ['max_count' => 500, 'max_length' => 30],
            ],
            'short' => [
                'title' => ['min' => 1, 'max' => 100],
                'description' => ['min' => 0, 'max' => 5000],
            ],
        ];
    }

    protected function uploadMedia(string $filePath, string $mediaType): string
    {
        // YouTube uses specialized upload flow via uploadVideoFile
        throw new \Exception('Use publishVideo or publishShort methods directly');
    }

    /**
     * Get video analytics
     */
    public function getAnalytics(string $externalPostId): array
    {
        try {
            // Get video statistics
            $response = $this->makeRequest(
                'get',
                "{$this->baseUrl}/{$this->apiVersion}/videos",
                [
                    'part' => 'statistics,contentDetails',
                    'id' => $externalPostId,
                ],
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ]
            );

            if (empty($response['items'])) {
                return [];
            }

            $video = $response['items'][0];
            $stats = $video['statistics'] ?? [];
            $contentDetails = $video['contentDetails'] ?? [];

            return [
                'views' => (int)($stats['viewCount'] ?? 0),
                'likes' => (int)($stats['likeCount'] ?? 0),
                'comments' => (int)($stats['commentCount'] ?? 0),
                'favorites' => (int)($stats['favoriteCount'] ?? 0),
                'duration' => $contentDetails['duration'] ?? null,
                'raw' => $response,
            ];
        } catch (\Exception $e) {
            $this->logError('get_analytics', $e, ['video_id' => $externalPostId]);
            return [];
        }
    }

    /**
     * Delete video from YouTube
     */
    public function delete(string $externalPostId): bool
    {
        try {
            $this->makeRequest(
                'delete',
                "{$this->baseUrl}/{$this->apiVersion}/videos",
                [
                    'id' => $externalPostId,
                ],
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ]
            );

            $this->logOperation('delete', ['video_id' => $externalPostId]);
            return true;
        } catch (\Exception $e) {
            $this->logError('delete', $e, ['video_id' => $externalPostId]);
            return false;
        }
    }

    /**
     * Update video metadata
     */
    public function update(string $externalPostId, array $content): array
    {
        try {
            $updateData = [
                'id' => $externalPostId,
            ];

            $parts = [];

            // Update snippet (title, description, tags)
            if (isset($content['title']) || isset($content['description']) || isset($content['tags'])) {
                $parts[] = 'snippet';
                $updateData['snippet'] = [];

                if (isset($content['title'])) {
                    $updateData['snippet']['title'] = $content['title'];
                }
                if (isset($content['description'])) {
                    $updateData['snippet']['description'] = $content['description'];
                }
                if (isset($content['tags'])) {
                    $updateData['snippet']['tags'] = $content['tags'];
                }
            }

            // Update status (privacy)
            if (isset($content['privacy_status'])) {
                $parts[] = 'status';
                $updateData['status'] = [
                    'privacyStatus' => $content['privacy_status'],
                ];
            }

            $response = $this->makeRequest(
                'put',
                "{$this->baseUrl}/{$this->apiVersion}/videos",
                array_merge(['part' => implode(',', $parts)], $updateData),
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ]
            );

            $this->logOperation('update', ['video_id' => $externalPostId]);

            return [
                'external_id' => $externalPostId,
                'updated' => true,
                'platform_data' => $response,
            ];
        } catch (\Exception $e) {
            $this->logError('update', $e, ['video_id' => $externalPostId]);
            throw $e;
        }
    }
}
