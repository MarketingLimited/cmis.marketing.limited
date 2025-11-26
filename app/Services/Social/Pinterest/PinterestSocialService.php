<?php

namespace App\Services\Social\Pinterest;

use App\Services\Social\AbstractSocialPlatform;
use Illuminate\Support\Facades\Http;

/**
 * Pinterest API v5 Publishing Service
 *
 * Supports:
 * - Pin creation (image, video, idea pins)
 * - Board management
 * - Rich metadata (title, description, link)
 * - Product pins with pricing
 * - Video pins (up to 15 minutes)
 * - Idea pins (multi-page stories)
 *
 * Authentication: OAuth 2.0
 * API: Pinterest API v5
 * Base URL: https://api.pinterest.com/v5/
 */
class PinterestSocialService extends AbstractSocialPlatform
{
    protected string $apiVersion = 'v5';
    protected string $baseUrl = 'https://api.pinterest.com';

    protected function getPlatformName(): string
    {
        return 'pinterest';
    }

    public function publish(array $content): array
    {
        $this->validateContent($content);

        $postType = $content['post_type'] ?? 'pin';

        return match($postType) {
            'pin' => $this->publishPin($content),
            'video_pin' => $this->publishVideoPin($content),
            'idea_pin' => $this->publishIdeaPin($content),
            default => throw new \Exception("Unsupported Pinterest post type: {$postType}"),
        };
    }

    /**
     * Publish standard pin (image + metadata)
     */
    protected function publishPin(array $content): array
    {
        $boardId = $content['board_id'] ?? null;
        $title = $content['title'] ?? '';
        $description = $content['description'] ?? '';
        $link = $content['link'] ?? null;
        $imageFile = $content['image_file'] ?? null;
        $imageUrl = $content['image_url'] ?? null;
        $altText = $content['alt_text'] ?? null;

        if (!$boardId) {
            throw new \InvalidArgumentException('Board ID is required');
        }

        if (!$imageFile && !$imageUrl) {
            throw new \InvalidArgumentException('Image file or URL is required');
        }

        // Upload image if file is provided
        $mediaSource = null;
        if ($imageFile) {
            $mediaSource = [
                'source_type' => 'image_base64',
                'data' => base64_encode(file_get_contents($imageFile)),
                'content_type' => mime_content_type($imageFile),
            ];
        } elseif ($imageUrl) {
            $mediaSource = [
                'source_type' => 'image_url',
                'url' => $imageUrl,
            ];
        }

        $pinData = [
            'board_id' => $boardId,
            'title' => $title,
            'description' => $description,
            'media_source' => $mediaSource,
        ];

        if ($link) {
            $pinData['link'] = $link;
        }

        if ($altText) {
            $pinData['alt_text'] = $altText;
        }

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/pins",
            $pinData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        $pinId = $response['id'] ?? null;
        $pinUrl = "https://www.pinterest.com/pin/{$pinId}/";

        $this->logOperation('publish_pin', [
            'pin_id' => $pinId,
            'board_id' => $boardId,
        ]);

        return [
            'external_id' => $pinId,
            'url' => $pinUrl,
            'platform_data' => $response,
        ];
    }

    /**
     * Publish video pin
     */
    protected function publishVideoPin(array $content): array
    {
        $boardId = $content['board_id'] ?? null;
        $title = $content['title'] ?? '';
        $description = $content['description'] ?? '';
        $videoFile = $content['video_file'] ?? null;
        $videoUrl = $content['video_url'] ?? null;
        $coverImageFile = $content['cover_image_file'] ?? null;

        if (!$boardId) {
            throw new \InvalidArgumentException('Board ID is required');
        }

        if (!$videoFile && !$videoUrl) {
            throw new \InvalidArgumentException('Video file or URL is required');
        }

        // Upload video
        $mediaSource = null;
        if ($videoFile) {
            // For video files, use upload endpoint
            $mediaSource = $this->uploadVideoFile($videoFile);
        } elseif ($videoUrl) {
            $mediaSource = [
                'source_type' => 'video_url',
                'url' => $videoUrl,
            ];
        }

        $pinData = [
            'board_id' => $boardId,
            'title' => $title,
            'description' => $description,
            'media_source' => $mediaSource,
        ];

        // Add cover image if provided
        if ($coverImageFile) {
            $pinData['media_source']['cover_image_data'] = base64_encode(file_get_contents($coverImageFile));
            $pinData['media_source']['cover_image_content_type'] = mime_content_type($coverImageFile);
        }

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/pins",
            $pinData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        $pinId = $response['id'] ?? null;

        return [
            'external_id' => $pinId,
            'url' => "https://www.pinterest.com/pin/{$pinId}/",
            'platform_data' => $response,
        ];
    }

    /**
     * Publish Idea Pin (multi-page story)
     */
    protected function publishIdeaPin(array $content): array
    {
        $boardId = $content['board_id'] ?? null;
        $title = $content['title'] ?? '';
        $pages = $content['pages'] ?? []; // Array of pages with images/videos and text

        if (!$boardId) {
            throw new \InvalidArgumentException('Board ID is required');
        }

        if (empty($pages) || count($pages) < 2) {
            throw new \InvalidArgumentException('Idea Pin must have at least 2 pages');
        }

        if (count($pages) > 20) {
            throw new \InvalidArgumentException('Idea Pin can have maximum 20 pages');
        }

        // Build pages data
        $pagesData = [];
        foreach ($pages as $page) {
            $pageData = [];

            // Image or video for this page
            if (isset($page['image_file'])) {
                $pageData['media_source'] = [
                    'source_type' => 'image_base64',
                    'data' => base64_encode(file_get_contents($page['image_file'])),
                    'content_type' => mime_content_type($page['image_file']),
                ];
            } elseif (isset($page['image_url'])) {
                $pageData['media_source'] = [
                    'source_type' => 'image_url',
                    'url' => $page['image_url'],
                ];
            }

            // Text overlay
            if (isset($page['text'])) {
                $pageData['text'] = $page['text'];
            }

            $pagesData[] = $pageData;
        }

        $ideaPinData = [
            'board_id' => $boardId,
            'title' => $title,
            'pages' => $pagesData,
        ];

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/pins",
            $ideaPinData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        $pinId = $response['id'] ?? null;

        return [
            'external_id' => $pinId,
            'url' => "https://www.pinterest.com/pin/{$pinId}/",
            'platform_data' => $response,
        ];
    }

    /**
     * Upload video file to Pinterest
     */
    protected function uploadVideoFile(string $videoFile): array
    {
        // Step 1: Register video upload
        $registerResponse = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/media",
            [
                'media_type' => 'video',
            ],
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        $uploadUrl = $registerResponse['upload_url'] ?? null;
        $mediaId = $registerResponse['media_id'] ?? null;

        if (!$uploadUrl || !$mediaId) {
            throw new \Exception('Failed to register video upload');
        }

        // Step 2: Upload video to provided URL
        $videoContent = file_get_contents($videoFile);

        $uploadResponse = Http::timeout(300)
            ->withHeaders([
                'Content-Type' => 'video/mp4',
            ])
            ->withBody($videoContent, 'video/mp4')
            ->put($uploadUrl);

        if (!$uploadResponse->successful()) {
            throw new \Exception('Video upload failed: ' . $uploadResponse->body());
        }

        return [
            'source_type' => 'video_id',
            'media_id' => $mediaId,
        ];
    }

    /**
     * Create a new board
     */
    public function createBoard(string $name, string $description = '', bool $isPrivate = false): array
    {
        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/boards",
            [
                'name' => $name,
                'description' => $description,
                'privacy' => $isPrivate ? 'SECRET' : 'PUBLIC',
            ],
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        $this->logOperation('create_board', [
            'board_id' => $response['id'] ?? null,
            'name' => $name,
        ]);

        return $response;
    }

    /**
     * Get user's boards
     */
    public function getBoards(): array
    {
        try {
            $response = $this->makeRequest(
                'get',
                "{$this->baseUrl}/{$this->apiVersion}/boards",
                [],
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ]
            );

            return $response['items'] ?? [];
        } catch (\Exception $e) {
            $this->logError('get_boards', $e);
            return [];
        }
    }

    public function schedule(array $content, \DateTime $scheduledTime): array
    {
        // Pinterest supports native scheduling via publish_time parameter
        $content['publish_time'] = $scheduledTime->format('c');

        // Publish with scheduled time
        $result = $this->publish($content);

        return [
            'scheduled' => true,
            'scheduled_for' => $scheduledTime->format('c'),
            'method' => 'native',
            'pin_id' => $result['external_id'],
        ];
    }

    public function validateContent(array $content): bool
    {
        $postType = $content['post_type'] ?? 'pin';

        // All post types require board_id
        $this->validateRequiredFields($content, ['board_id']);

        if ($postType === 'pin') {
            if (!isset($content['image_file']) && !isset($content['image_url'])) {
                throw new \InvalidArgumentException('Image file or URL required for pin');
            }

            // Validate title
            if (isset($content['title'])) {
                $this->validateTextLength($content['title'], 100, 'title');
            }

            // Validate description
            if (isset($content['description'])) {
                $this->validateTextLength($content['description'], 500, 'description');
            }
        }

        if ($postType === 'video_pin') {
            if (!isset($content['video_file']) && !isset($content['video_url'])) {
                throw new \InvalidArgumentException('Video file or URL required for video pin');
            }
        }

        if ($postType === 'idea_pin') {
            if (!isset($content['pages']) || count($content['pages']) < 2) {
                throw new \InvalidArgumentException('Idea Pin must have at least 2 pages');
            }

            if (count($content['pages']) > 20) {
                throw new \InvalidArgumentException('Idea Pin can have maximum 20 pages');
            }
        }

        return true;
    }

    public function getPostTypes(): array
    {
        return [
            [
                'value' => 'pin',
                'label' => 'دبوس',
                'icon' => 'fa-thumbtack',
                'description' => 'Standard image pin with link',
            ],
            [
                'value' => 'video_pin',
                'label' => 'دبوس فيديو',
                'icon' => 'fa-video',
                'description' => 'Video pin (up to 15 minutes)',
            ],
            [
                'value' => 'idea_pin',
                'label' => 'دبوس فكرة',
                'icon' => 'fa-lightbulb',
                'description' => 'Multi-page story (2-20 pages)',
            ],
        ];
    }

    public function getMediaRequirements(): array
    {
        return [
            'image' => [
                'formats' => ['JPEG', 'PNG'],
                'max_size_mb' => 32,
                'aspect_ratio' => '2:3 (1000x1500) recommended',
                'min_width' => 100,
            ],
            'video' => [
                'formats' => ['MP4', 'MOV', 'M4V'],
                'max_size_mb' => 2048, // 2GB
                'max_duration_seconds' => 900, // 15 minutes
                'min_duration_seconds' => 4,
                'aspect_ratio' => 'Square (1:1), Portrait (2:3, 9:16), Widescreen (16:9)',
                'encoding' => 'H.264 or H.265',
            ],
        ];
    }

    public function getTextLimits(): array
    {
        return [
            'pin' => [
                'title' => ['min' => 0, 'max' => 100],
                'description' => ['min' => 0, 'max' => 500],
                'alt_text' => ['min' => 0, 'max' => 500],
            ],
            'video_pin' => [
                'title' => ['min' => 0, 'max' => 100],
                'description' => ['min' => 0, 'max' => 500],
            ],
            'idea_pin' => [
                'title' => ['min' => 0, 'max' => 100],
                'page_text' => ['min' => 0, 'max' => 500],
            ],
        ];
    }

    protected function uploadMedia(string $filePath, string $mediaType): string
    {
        // Pinterest uses inline base64 for images or uploadVideoFile for videos
        throw new \Exception('Use publishPin or publishVideoPin methods directly');
    }

    /**
     * Get pin analytics
     */
    public function getAnalytics(string $externalPostId): array
    {
        try {
            $response = $this->makeRequest(
                'get',
                "{$this->baseUrl}/{$this->apiVersion}/pins/{$externalPostId}/analytics",
                [
                    'metric_types' => 'IMPRESSION,SAVE,PIN_CLICK,OUTBOUND_CLICK,VIDEO_V50_WATCH_TIME',
                ],
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ]
            );

            $metrics = $response['all_time'] ?? [];

            return [
                'impressions' => $metrics['IMPRESSION'] ?? 0,
                'saves' => $metrics['SAVE'] ?? 0,
                'clicks' => $metrics['PIN_CLICK'] ?? 0,
                'outbound_clicks' => $metrics['OUTBOUND_CLICK'] ?? 0,
                'video_watch_time' => $metrics['VIDEO_V50_WATCH_TIME'] ?? 0,
                'raw' => $response,
            ];
        } catch (\Exception $e) {
            $this->logError('get_analytics', $e, ['pin_id' => $externalPostId]);
            return [];
        }
    }

    /**
     * Delete pin
     */
    public function delete(string $externalPostId): bool
    {
        try {
            $this->makeRequest(
                'delete',
                "{$this->baseUrl}/{$this->apiVersion}/pins/{$externalPostId}",
                [],
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ]
            );

            $this->logOperation('delete', ['pin_id' => $externalPostId]);
            return true;
        } catch (\Exception $e) {
            $this->logError('delete', $e, ['pin_id' => $externalPostId]);
            return false;
        }
    }

    /**
     * Update pin
     */
    public function update(string $externalPostId, array $content): array
    {
        try {
            $updateData = [];

            if (isset($content['title'])) {
                $updateData['title'] = $content['title'];
            }

            if (isset($content['description'])) {
                $updateData['description'] = $content['description'];
            }

            if (isset($content['link'])) {
                $updateData['link'] = $content['link'];
            }

            if (isset($content['alt_text'])) {
                $updateData['alt_text'] = $content['alt_text'];
            }

            $response = $this->makeRequest(
                'patch',
                "{$this->baseUrl}/{$this->apiVersion}/pins/{$externalPostId}",
                $updateData,
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ]
            );

            $this->logOperation('update', ['pin_id' => $externalPostId]);

            return [
                'external_id' => $externalPostId,
                'updated' => true,
                'platform_data' => $response,
            ];
        } catch (\Exception $e) {
            $this->logError('update', $e, ['pin_id' => $externalPostId]);
            throw $e;
        }
    }
}
