<?php

namespace App\Services\Social\Threads;

use App\Services\Social\AbstractSocialPlatform;
use Illuminate\Support\Facades\Http;

/**
 * Threads Publishing Service (Meta)
 *
 * Uses Meta Graph API for Threads publishing
 * Latest features as of July 25, 2025:
 * - Simplified text publishing with auto_publish_text
 * - Poll creation and retrieval
 * - Location tagging
 * - Topic tags
 * - GIF support
 * - Reply restrictions
 */
class ThreadsSocialService extends AbstractSocialPlatform
{
    protected string $apiVersion = 'v21.0';
    protected string $baseUrl = 'https://graph.threads.net';

    protected function getPlatformName(): string
    {
        return 'threads';
    }

    public function publish(array $content): array
    {
        $this->validateContent($content);

        $postType = $content['post_type'] ?? 'post';

        return match($postType) {
            'post' => $this->publishTextPost($content),
            'poll' => $this->publishPoll($content),
            default => throw new \Exception("Unsupported Threads post type: {$postType}"),
        };
    }

    /**
     * Publish text/media post to Threads
     */
    protected function publishTextPost(array $content): array
    {
        $userId = $content['user_id'] ?? 'me';
        $text = $content['text'] ?? '';
        $mediaUrls = $content['media'] ?? [];
        $locationId = $content['location_id'] ?? null;
        $topicTag = $content['topic_tag'] ?? null;
        $replyControl = $content['reply_control'] ?? null; // 'everyone' or 'followers_only'

        // Simple text-only post with auto_publish
        if (empty($mediaUrls)) {
            return $this->publishSimpleText($userId, $text, $locationId, $topicTag, $replyControl);
        }

        // Media post requires container → publish flow
        return $this->publishMediaPost($userId, $text, $mediaUrls, $locationId, $topicTag, $replyControl);
    }

    /**
     * Simplified text publishing (new July 2025 feature)
     */
    protected function publishSimpleText(
        string $userId,
        string $text,
        ?string $locationId = null,
        ?string $topicTag = null,
        ?string $replyControl = null
    ): array {
        $params = [
            'text' => $text,
            'access_token' => $this->getAccessToken(),
            'auto_publish_text' => 'true', // NEW: Single API call publishing
        ];

        if ($locationId) {
            $params['location_id'] = $locationId;
        }

        if ($topicTag) {
            $params['topic_tag'] = $topicTag;
        }

        if ($replyControl) {
            $params['reply_control'] = $replyControl;
        }

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/{$userId}/threads_publish",
            $params
        );

        return [
            'external_id' => $response['id'] ?? null,
            'url' => "https://www.threads.net/@{$userId}/post/" . ($response['id'] ?? ''),
            'platform_data' => $response,
        ];
    }

    /**
     * Publish media post (traditional container flow)
     */
    protected function publishMediaPost(
        string $userId,
        string $text,
        array $mediaUrls,
        ?string $locationId = null,
        ?string $topicTag = null,
        ?string $replyControl = null
    ): array {
        // Step 1: Create media container
        $containerParams = [
            'text' => $text,
            'access_token' => $this->getAccessToken(),
        ];

        // Add media
        if (count($mediaUrls) === 1) {
            $mediaUrl = $mediaUrls[0];
            $mediaType = $this->detectMediaType($mediaUrl);

            if ($mediaType === 'image') {
                $containerParams['image_url'] = $mediaUrl;
            } elseif ($mediaType === 'video') {
                $containerParams['video_url'] = $mediaUrl;
            } elseif ($mediaType === 'gif') {
                $containerParams['media_type'] = 'GIF';
                $containerParams['image_url'] = $mediaUrl;
            }
        }

        if ($locationId) {
            $containerParams['location_id'] = $locationId;
        }

        if ($topicTag) {
            $containerParams['topic_tag'] = $topicTag;
        }

        if ($replyControl) {
            $containerParams['reply_control'] = $replyControl;
        }

        $containerResponse = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/{$userId}/threads",
            $containerParams
        );

        $containerId = $containerResponse['id'];

        // Step 2: Publish container
        $publishResponse = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/{$userId}/threads_publish",
            [
                'creation_id' => $containerId,
                'access_token' => $this->getAccessToken(),
            ]
        );

        return [
            'external_id' => $publishResponse['id'] ?? null,
            'url' => "https://www.threads.net/@{$userId}/post/" . ($publishResponse['id'] ?? ''),
            'platform_data' => $publishResponse,
        ];
    }

    /**
     * Publish poll to Threads (NEW July 2025)
     */
    protected function publishPoll(array $content): array
    {
        $userId = $content['user_id'] ?? 'me';
        $question = $content['text'] ?? '';
        $options = $content['poll_options'] ?? [];
        $durationMinutes = $content['poll_duration'] ?? 1440; // Default 24 hours

        if (count($options) < 2 || count($options) > 4) {
            throw new \InvalidArgumentException('Threads polls must have 2-4 options');
        }

        $params = [
            'text' => $question,
            'poll_options' => $options,
            'poll_duration_minutes' => $durationMinutes,
            'access_token' => $this->getAccessToken(),
            'auto_publish_text' => 'true',
        ];

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/{$userId}/threads_publish",
            $params
        );

        return [
            'external_id' => $response['id'] ?? null,
            'url' => "https://www.threads.net/@{$userId}/post/" . ($response['id'] ?? ''),
            'platform_data' => $response,
        ];
    }

    public function schedule(array $content, \DateTime $scheduledTime): array
    {
        // Threads doesn't support native scheduling via API
        // Return data for queue-based scheduling
        return [
            'scheduled' => true,
            'scheduled_for' => $scheduledTime->format('c'),
            'method' => 'queue',
        ];
    }

    public function validateContent(array $content): bool
    {
        $this->validateRequiredFields($content, ['text']);

        $text = $content['text'];
        $maxLength = 500;

        $this->validateTextLength($text, $maxLength);

        return true;
    }

    public function getPostTypes(): array
    {
        return [
            [
                'value' => 'post',
                'label' => 'منشور',
                'icon' => 'fa-at',
                'description' => 'Text, image, video, or GIF post',
            ],
            [
                'value' => 'poll',
                'label' => 'استطلاع',
                'icon' => 'fa-poll',
                'description' => 'Poll with 2-4 options (NEW July 2025)',
            ],
        ];
    }

    public function getMediaRequirements(): array
    {
        return [
            'image' => [
                'formats' => ['JPEG', 'PNG', 'GIF'],
                'max_size_mb' => 8,
                'min_width' => 320,
                'max_width' => 1440,
            ],
            'video' => [
                'formats' => ['MP4', 'MOV'],
                'max_size_mb' => 100,
                'max_duration_seconds' => 90,
                'min_width' => 320,
                'max_width' => 1920,
            ],
            'gif' => [
                'formats' => ['GIF'],
                'max_size_mb' => 8,
            ],
        ];
    }

    public function getTextLimits(): array
    {
        return [
            'post' => [
                'min' => 1,
                'max' => 500,
            ],
            'poll' => [
                'min' => 1,
                'max' => 500,
            ],
        ];
    }

    protected function uploadMedia(string $filePath, string $mediaType): string
    {
        // Threads doesn't have separate media upload
        // Media is passed as URL in post creation
        throw new \Exception('Threads uses direct media URLs, not separate upload');
    }

    /**
     * Detect media type from URL or file
     */
    protected function detectMediaType(string $mediaUrl): string
    {
        $extension = strtolower(pathinfo($mediaUrl, PATHINFO_EXTENSION));

        return match($extension) {
            'gif' => 'gif',
            'mp4', 'mov' => 'video',
            'jpg', 'jpeg', 'png' => 'image',
            default => 'image',
        };
    }

    public function getAnalytics(string $externalPostId): array
    {
        try {
            $response = $this->makeRequest(
                'get',
                "{$this->baseUrl}/{$this->apiVersion}/{$externalPostId}/insights",
                [
                    'metric' => 'views,likes,replies,reposts,quotes',
                    'access_token' => $this->getAccessToken(),
                ]
            );

            return [
                'views' => $this->extractMetric($response, 'views'),
                'likes' => $this->extractMetric($response, 'likes'),
                'replies' => $this->extractMetric($response, 'replies'),
                'reposts' => $this->extractMetric($response, 'reposts'),
                'quotes' => $this->extractMetric($response, 'quotes'),
                'raw' => $response,
            ];
        } catch (\Exception $e) {
            $this->logError('get_analytics', $e, ['post_id' => $externalPostId]);
            return [];
        }
    }

    protected function extractMetric(array $response, string $metricName): int
    {
        $data = $response['data'] ?? [];
        foreach ($data as $metric) {
            if ($metric['name'] === $metricName) {
                return $metric['values'][0]['value'] ?? 0;
            }
        }
        return 0;
    }

    public function delete(string $externalPostId): bool
    {
        try {
            $this->makeRequest(
                'delete',
                "{$this->baseUrl}/{$this->apiVersion}/{$externalPostId}",
                [
                    'access_token' => $this->getAccessToken(),
                ]
            );
            return true;
        } catch (\Exception $e) {
            $this->logError('delete', $e, ['post_id' => $externalPostId]);
            return false;
        }
    }
}
