<?php

namespace App\Services\Social\Reddit;

use App\Services\Social\AbstractSocialPlatform;
use Illuminate\Support\Facades\Http;

/**
 * Reddit Data API Publishing Service
 *
 * Supports:
 * - Text post submission
 * - Link post submission
 * - Image post submission
 * - Video post submission
 * - Crosspost functionality
 * - Subreddit validation
 * - Flair selection
 * - NSFW tagging
 * - Spoiler tagging
 *
 * Authentication: OAuth 2.0
 * API: Reddit Data API
 * Base URL: https://oauth.reddit.com
 */
class RedditSocialService extends AbstractSocialPlatform
{
    protected string $baseUrl = 'https://oauth.reddit.com';
    protected string $userAgent = 'CMIS-Platform:v1.0.0'; // Required by Reddit API

    protected function getPlatformName(): string
    {
        return 'reddit';
    }

    public function publish(array $content): array
    {
        $this->validateContent($content);

        $postType = $content['post_type'] ?? 'text';

        return match($postType) {
            'text' => $this->publishTextPost($content),
            'link' => $this->publishLinkPost($content),
            'image' => $this->publishImagePost($content),
            'video' => $this->publishVideoPost($content),
            'crosspost' => $this->publishCrosspost($content),
            default => throw new \Exception("Unsupported Reddit post type: {$postType}"),
        };
    }

    /**
     * Publish text post (self post)
     */
    protected function publishTextPost(array $content): array
    {
        $subreddit = $content['subreddit'] ?? null;
        $title = $content['title'] ?? '';
        $text = $content['text'] ?? '';
        $flairId = $content['flair_id'] ?? null;
        $flairText = $content['flair_text'] ?? null;
        $nsfw = $content['nsfw'] ?? false;
        $spoiler = $content['spoiler'] ?? false;
        $sendReplies = $content['send_replies'] ?? true;

        if (!$subreddit) {
            throw new \InvalidArgumentException('Subreddit required');
        }

        // Validate subreddit exists and user can post
        $this->validateSubreddit($subreddit);

        $postData = [
            'sr' => $subreddit,
            'kind' => 'self',
            'title' => $title,
            'text' => $text,
            'nsfw' => $nsfw,
            'spoiler' => $spoiler,
            'sendreplies' => $sendReplies,
        ];

        if ($flairId) {
            $postData['flair_id'] = $flairId;
        } elseif ($flairText) {
            $postData['flair_text'] = $flairText;
        }

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/api/submit",
            $postData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'User-Agent' => $this->userAgent,
            ]
        );

        $postUrl = $response['json']['data']['url'] ?? null;
        $postId = $response['json']['data']['name'] ?? null;

        $this->logOperation('publish_text', [
            'post_id' => $postId,
            'subreddit' => $subreddit,
        ]);

        return [
            'external_id' => $postId,
            'url' => $postUrl,
            'platform_data' => $response['json']['data'] ?? [],
        ];
    }

    /**
     * Publish link post
     */
    protected function publishLinkPost(array $content): array
    {
        $subreddit = $content['subreddit'] ?? null;
        $title = $content['title'] ?? '';
        $url = $content['url'] ?? '';
        $flairId = $content['flair_id'] ?? null;
        $nsfw = $content['nsfw'] ?? false;
        $spoiler = $content['spoiler'] ?? false;

        if (!$subreddit || !$url) {
            throw new \InvalidArgumentException('Subreddit and URL required');
        }

        $this->validateSubreddit($subreddit);

        $postData = [
            'sr' => $subreddit,
            'kind' => 'link',
            'title' => $title,
            'url' => $url,
            'nsfw' => $nsfw,
            'spoiler' => $spoiler,
        ];

        if ($flairId) {
            $postData['flair_id'] = $flairId;
        }

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/api/submit",
            $postData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'User-Agent' => $this->userAgent,
            ]
        );

        $postUrl = $response['json']['data']['url'] ?? null;
        $postId = $response['json']['data']['name'] ?? null;

        return [
            'external_id' => $postId,
            'url' => $postUrl,
            'platform_data' => $response['json']['data'] ?? [],
        ];
    }

    /**
     * Publish image post
     */
    protected function publishImagePost(array $content): array
    {
        $subreddit = $content['subreddit'] ?? null;
        $title = $content['title'] ?? '';
        $imageFile = $content['image_file'] ?? null;
        $imageUrl = $content['image_url'] ?? null;
        $flairId = $content['flair_id'] ?? null;
        $nsfw = $content['nsfw'] ?? false;
        $spoiler = $content['spoiler'] ?? false;

        if (!$subreddit) {
            throw new \InvalidArgumentException('Subreddit required');
        }

        if (!$imageFile && !$imageUrl) {
            throw new \InvalidArgumentException('Image file or URL required');
        }

        $this->validateSubreddit($subreddit);

        // Step 1: Upload image to Reddit if file is provided
        if ($imageFile) {
            $uploadedUrl = $this->uploadImage($imageFile);
        } else {
            $uploadedUrl = $imageUrl;
        }

        // Step 2: Submit post with image
        $postData = [
            'sr' => $subreddit,
            'kind' => 'image',
            'title' => $title,
            'url' => $uploadedUrl,
            'nsfw' => $nsfw,
            'spoiler' => $spoiler,
        ];

        if ($flairId) {
            $postData['flair_id'] = $flairId;
        }

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/api/submit",
            $postData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'User-Agent' => $this->userAgent,
            ]
        );

        $postUrl = $response['json']['data']['url'] ?? null;
        $postId = $response['json']['data']['name'] ?? null;

        return [
            'external_id' => $postId,
            'url' => $postUrl,
            'platform_data' => $response['json']['data'] ?? [],
        ];
    }

    /**
     * Publish video post
     */
    protected function publishVideoPost(array $content): array
    {
        $subreddit = $content['subreddit'] ?? null;
        $title = $content['title'] ?? '';
        $videoFile = $content['video_file'] ?? null;
        $videoUrl = $content['video_url'] ?? null;
        $flairId = $content['flair_id'] ?? null;
        $nsfw = $content['nsfw'] ?? false;
        $spoiler = $content['spoiler'] ?? false;

        if (!$subreddit) {
            throw new \InvalidArgumentException('Subreddit required');
        }

        if (!$videoFile && !$videoUrl) {
            throw new \InvalidArgumentException('Video file or URL required');
        }

        $this->validateSubreddit($subreddit);

        // Step 1: Upload video to Reddit if file is provided
        if ($videoFile) {
            $uploadedUrl = $this->uploadVideo($videoFile);
        } else {
            $uploadedUrl = $videoUrl;
        }

        // Step 2: Submit video post
        $postData = [
            'sr' => $subreddit,
            'kind' => 'videogif', // or 'video'
            'title' => $title,
            'url' => $uploadedUrl,
            'nsfw' => $nsfw,
            'spoiler' => $spoiler,
        ];

        if ($flairId) {
            $postData['flair_id'] = $flairId;
        }

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/api/submit",
            $postData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'User-Agent' => $this->userAgent,
            ]
        );

        $postUrl = $response['json']['data']['url'] ?? null;
        $postId = $response['json']['data']['name'] ?? null;

        return [
            'external_id' => $postId,
            'url' => $postUrl,
            'platform_data' => $response['json']['data'] ?? [],
        ];
    }

    /**
     * Crosspost from another Reddit post
     */
    protected function publishCrosspost(array $content): array
    {
        $subreddit = $content['subreddit'] ?? null;
        $title = $content['title'] ?? '';
        $originalPostId = $content['original_post_id'] ?? null;
        $flairId = $content['flair_id'] ?? null;
        $nsfw = $content['nsfw'] ?? false;
        $spoiler = $content['spoiler'] ?? false;

        if (!$subreddit || !$originalPostId) {
            throw new \InvalidArgumentException('Subreddit and original post ID required');
        }

        $this->validateSubreddit($subreddit);

        $postData = [
            'sr' => $subreddit,
            'kind' => 'crosspost',
            'title' => $title,
            'crosspost_fullname' => $originalPostId,
            'nsfw' => $nsfw,
            'spoiler' => $spoiler,
        ];

        if ($flairId) {
            $postData['flair_id'] = $flairId;
        }

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/api/submit",
            $postData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'User-Agent' => $this->userAgent,
            ]
        );

        $postUrl = $response['json']['data']['url'] ?? null;
        $postId = $response['json']['data']['name'] ?? null;

        return [
            'external_id' => $postId,
            'url' => $postUrl,
            'platform_data' => $response['json']['data'] ?? [],
        ];
    }

    /**
     * Validate subreddit exists and user can post
     */
    protected function validateSubreddit(string $subreddit): void
    {
        try {
            $response = $this->makeRequest(
                'get',
                "{$this->baseUrl}/r/{$subreddit}/about",
                [],
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'User-Agent' => $this->userAgent,
                ]
            );

            $subredditData = $response['data'] ?? null;

            if (!$subredditData) {
                throw new \Exception("Subreddit r/{$subreddit} not found");
            }

            // Check if submissions are restricted
            if ($subredditData['submission_type'] === 'restricted') {
                throw new \Exception("Subreddit r/{$subreddit} is restricted");
            }
        } catch (\Exception $e) {
            $this->logError('validate_subreddit', $e, ['subreddit' => $subreddit]);
            throw new \InvalidArgumentException("Invalid subreddit: {$subreddit}");
        }
    }

    /**
     * Get available flairs for subreddit
     */
    public function getSubredditFlairs(string $subreddit): array
    {
        try {
            $response = $this->makeRequest(
                'get',
                "{$this->baseUrl}/r/{$subreddit}/api/link_flair",
                [],
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'User-Agent' => $this->userAgent,
                ]
            );

            return $response ?? [];
        } catch (\Exception $e) {
            $this->logError('get_flairs', $e, ['subreddit' => $subreddit]);
            return [];
        }
    }

    /**
     * Upload image to Reddit
     */
    protected function uploadImage(string $imageFile): string
    {
        // Get upload lease from Reddit
        $leaseResponse = $this->makeRequest(
            'post',
            "{$this->baseUrl}/api/media/asset",
            [
                'filepath' => basename($imageFile),
                'mimetype' => mime_content_type($imageFile),
            ],
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'User-Agent' => $this->userAgent,
            ]
        );

        $uploadUrl = $leaseResponse['args']['action'] ?? null;
        $uploadFields = $leaseResponse['args']['fields'] ?? [];

        if (!$uploadUrl) {
            throw new \Exception('Failed to get image upload URL');
        }

        // Upload image to S3
        $imageContent = file_get_contents($imageFile);

        $multipartData = [];
        foreach ($uploadFields as $field) {
            $multipartData[] = [
                'name' => $field['name'],
                'contents' => $field['value'],
            ];
        }

        $multipartData[] = [
            'name' => 'file',
            'contents' => $imageContent,
            'filename' => basename($imageFile),
        ];

        $uploadResponse = Http::asMultipart()
            ->post($uploadUrl, $multipartData);

        if (!$uploadResponse->successful()) {
            throw new \Exception('Image upload failed');
        }

        return $leaseResponse['asset']['asset_id'] ?? null;
    }

    /**
     * Upload video to Reddit
     */
    protected function uploadVideo(string $videoFile): string
    {
        // Similar to image upload but with video MIME type
        $leaseResponse = $this->makeRequest(
            'post',
            "{$this->baseUrl}/api/media/asset",
            [
                'filepath' => basename($videoFile),
                'mimetype' => mime_content_type($videoFile),
            ],
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'User-Agent' => $this->userAgent,
            ]
        );

        $uploadUrl = $leaseResponse['args']['action'] ?? null;
        $uploadFields = $leaseResponse['args']['fields'] ?? [];

        if (!$uploadUrl) {
            throw new \Exception('Failed to get video upload URL');
        }

        // Upload video
        $videoContent = file_get_contents($videoFile);

        $multipartData = [];
        foreach ($uploadFields as $field) {
            $multipartData[] = [
                'name' => $field['name'],
                'contents' => $field['value'],
            ];
        }

        $multipartData[] = [
            'name' => 'file',
            'contents' => $videoContent,
            'filename' => basename($videoFile),
        ];

        $uploadResponse = Http::asMultipart()
            ->timeout(300) // 5 minutes for video
            ->post($uploadUrl, $multipartData);

        if (!$uploadResponse->successful()) {
            throw new \Exception('Video upload failed');
        }

        return $leaseResponse['asset']['asset_id'] ?? null;
    }

    public function schedule(array $content, \DateTime $scheduledTime): array
    {
        // Reddit doesn't support native scheduling via API
        // Use queue-based scheduling
        return [
            'scheduled' => true,
            'scheduled_for' => $scheduledTime->format('c'),
            'method' => 'queue',
        ];
    }

    public function validateContent(array $content): bool
    {
        $this->validateRequiredFields($content, ['subreddit', 'title']);

        $postType = $content['post_type'] ?? 'text';

        // Validate title length
        $this->validateTextLength($content['title'], 300, 'title');

        // Type-specific validation
        if ($postType === 'text' && !isset($content['text'])) {
            throw new \InvalidArgumentException('Text required for text post');
        }

        if ($postType === 'link' && !isset($content['url'])) {
            throw new \InvalidArgumentException('URL required for link post');
        }

        if ($postType === 'image') {
            if (!isset($content['image_file']) && !isset($content['image_url'])) {
                throw new \InvalidArgumentException('Image file or URL required');
            }
        }

        if ($postType === 'video') {
            if (!isset($content['video_file']) && !isset($content['video_url'])) {
                throw new \InvalidArgumentException('Video file or URL required');
            }
        }

        if ($postType === 'crosspost' && !isset($content['original_post_id'])) {
            throw new \InvalidArgumentException('Original post ID required for crosspost');
        }

        return true;
    }

    public function getPostTypes(): array
    {
        return [
            [
                'value' => 'text',
                'label' => 'منشور نصي',
                'icon' => 'fa-align-left',
                'description' => 'Text post (self post)',
            ],
            [
                'value' => 'link',
                'label' => 'رابط',
                'icon' => 'fa-link',
                'description' => 'Link to external URL',
            ],
            [
                'value' => 'image',
                'label' => 'صورة',
                'icon' => 'fa-image',
                'description' => 'Image post',
            ],
            [
                'value' => 'video',
                'label' => 'فيديو',
                'icon' => 'fa-video',
                'description' => 'Video post or GIF',
            ],
            [
                'value' => 'crosspost',
                'label' => 'إعادة نشر',
                'icon' => 'fa-share',
                'description' => 'Crosspost from another subreddit',
            ],
        ];
    }

    public function getMediaRequirements(): array
    {
        return [
            'image' => [
                'formats' => ['JPEG', 'PNG', 'GIF'],
                'max_size_mb' => 20,
            ],
            'video' => [
                'formats' => ['MP4', 'MOV'],
                'max_size_mb' => 1024, // 1GB
                'max_duration_seconds' => 900, // 15 minutes
            ],
        ];
    }

    public function getTextLimits(): array
    {
        return [
            'title' => ['min' => 1, 'max' => 300],
            'text' => ['min' => 0, 'max' => 40000],
        ];
    }

    protected function uploadMedia(string $filePath, string $mediaType): string
    {
        // Reddit uses specific upload endpoints
        return match($mediaType) {
            'image' => $this->uploadImage($filePath),
            'video' => $this->uploadVideo($filePath),
            default => throw new \Exception("Unsupported media type: {$mediaType}"),
        };
    }

    /**
     * Get post analytics (score, comments, awards)
     */
    public function getAnalytics(string $externalPostId): array
    {
        try {
            $response = $this->makeRequest(
                'get',
                "{$this->baseUrl}/api/info",
                ['id' => $externalPostId],
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'User-Agent' => $this->userAgent,
                ]
            );

            $postData = $response['data']['children'][0]['data'] ?? [];

            return [
                'score' => $postData['score'] ?? 0,
                'upvotes' => $postData['ups'] ?? 0,
                'downvotes' => $postData['downs'] ?? 0,
                'comments' => $postData['num_comments'] ?? 0,
                'awards' => $postData['total_awards_received'] ?? 0,
                'raw' => $postData,
            ];
        } catch (\Exception $e) {
            $this->logError('get_analytics', $e, ['post_id' => $externalPostId]);
            return [];
        }
    }

    /**
     * Delete Reddit post
     */
    public function delete(string $externalPostId): bool
    {
        try {
            $this->makeRequest(
                'post',
                "{$this->baseUrl}/api/del",
                ['id' => $externalPostId],
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'User-Agent' => $this->userAgent,
                ]
            );

            $this->logOperation('delete', ['post_id' => $externalPostId]);
            return true;
        } catch (\Exception $e) {
            $this->logError('delete', $e, ['post_id' => $externalPostId]);
            return false;
        }
    }

    /**
     * Edit Reddit post (text posts only)
     */
    public function update(string $externalPostId, array $content): array
    {
        try {
            if (!isset($content['text'])) {
                throw new \Exception('Only text can be edited in Reddit posts');
            }

            $response = $this->makeRequest(
                'post',
                "{$this->baseUrl}/api/editusertext",
                [
                    'thing_id' => $externalPostId,
                    'text' => $content['text'],
                ],
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'User-Agent' => $this->userAgent,
                ]
            );

            $this->logOperation('update', ['post_id' => $externalPostId]);

            return [
                'external_id' => $externalPostId,
                'updated' => true,
                'platform_data' => $response,
            ];
        } catch (\Exception $e) {
            $this->logError('update', $e, ['post_id' => $externalPostId]);
            throw $e;
        }
    }
}
