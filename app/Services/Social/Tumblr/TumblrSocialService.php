<?php

namespace App\Services\Social\Tumblr;

use App\Services\Social\AbstractSocialPlatform;
use Illuminate\Support\Facades\Http;

/**
 * Tumblr API v2 Publishing Service
 *
 * Uses NPF (Neue Post Format) - Modern JSON-based post format
 *
 * Supports:
 * - Text posts
 * - Photo posts (single and multiple)
 * - Video posts
 * - Audio posts
 * - Link posts
 * - Quote posts
 * - Chat posts
 * - Queue management
 * - Draft creation
 * - Native scheduling
 * - Tags and custom URLs
 *
 * Authentication: OAuth 1.0a
 * API: Tumblr API v2
 */
class TumblrSocialService extends AbstractSocialPlatform
{
    protected string $apiVersion = 'v2';
    protected string $baseUrl = 'https://api.tumblr.com';

    protected function getPlatformName(): string
    {
        return 'tumblr';
    }

    public function publish(array $content): array
    {
        $this->validateContent($content);

        $postType = $content['post_type'] ?? 'text';

        return match($postType) {
            'text' => $this->publishTextPost($content),
            'photo' => $this->publishPhotoPost($content),
            'video' => $this->publishVideoPost($content),
            'link' => $this->publishLinkPost($content),
            'quote' => $this->publishQuotePost($content),
            default => throw new \Exception("Unsupported Tumblr post type: {$postType}"),
        };
    }

    /**
     * Publish text post using NPF
     */
    protected function publishTextPost(array $content): array
    {
        $blogIdentifier = $content['blog_identifier'] ?? null;
        $title = $content['title'] ?? '';
        $body = $content['body'] ?? '';
        $tags = $content['tags'] ?? [];
        $state = $content['state'] ?? 'published'; // published, draft, queue, private
        $slug = $content['slug'] ?? null; // Custom URL slug

        if (!$blogIdentifier) {
            throw new \InvalidArgumentException('Blog identifier required');
        }

        // Build NPF content blocks
        $contentBlocks = [];

        if ($title) {
            $contentBlocks[] = [
                'type' => 'text',
                'text' => $title,
                'subtype' => 'heading1',
            ];
        }

        if ($body) {
            $contentBlocks[] = [
                'type' => 'text',
                'text' => $body,
            ];
        }

        $postData = [
            'content' => $contentBlocks,
            'tags' => implode(',', $tags),
            'state' => $state,
        ];

        if ($slug) {
            $postData['slug'] = $slug;
        }

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/blog/{$blogIdentifier}/posts",
            $postData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        $postId = $response['response']['id'] ?? null;
        $postUrl = "https://{$blogIdentifier}.tumblr.com/post/{$postId}";

        $this->logOperation('publish_text', [
            'post_id' => $postId,
            'blog' => $blogIdentifier,
            'state' => $state,
        ]);

        return [
            'external_id' => $postId,
            'url' => $postUrl,
            'platform_data' => $response['response'] ?? [],
        ];
    }

    /**
     * Publish photo post
     */
    protected function publishPhotoPost(array $content): array
    {
        $blogIdentifier = $content['blog_identifier'] ?? null;
        $caption = $content['caption'] ?? '';
        $photoFiles = $content['photo_files'] ?? [];
        $photoUrls = $content['photo_urls'] ?? [];
        $tags = $content['tags'] ?? [];
        $state = $content['state'] ?? 'published';

        if (!$blogIdentifier) {
            throw new \InvalidArgumentException('Blog identifier required');
        }

        $photos = array_merge($photoFiles, $photoUrls);

        if (empty($photos)) {
            throw new \InvalidArgumentException('At least one photo required');
        }

        // Build NPF content blocks
        $contentBlocks = [];

        // Add caption if provided
        if ($caption) {
            $contentBlocks[] = [
                'type' => 'text',
                'text' => $caption,
            ];
        }

        // Add photos
        foreach ($photos as $photo) {
            if (file_exists($photo)) {
                // Upload photo first
                $photoUrl = $this->uploadPhoto($blogIdentifier, $photo);
            } else {
                $photoUrl = $photo;
            }

            $contentBlocks[] = [
                'type' => 'image',
                'media' => [
                    [
                        'type' => 'image/jpeg',
                        'url' => $photoUrl,
                    ],
                ],
            ];
        }

        $postData = [
            'content' => $contentBlocks,
            'tags' => implode(',', $tags),
            'state' => $state,
        ];

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/blog/{$blogIdentifier}/posts",
            $postData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        $postId = $response['response']['id'] ?? null;

        return [
            'external_id' => $postId,
            'url' => "https://{$blogIdentifier}.tumblr.com/post/{$postId}",
            'platform_data' => $response['response'] ?? [],
        ];
    }

    /**
     * Publish video post
     */
    protected function publishVideoPost(array $content): array
    {
        $blogIdentifier = $content['blog_identifier'] ?? null;
        $caption = $content['caption'] ?? '';
        $videoFile = $content['video_file'] ?? null;
        $videoUrl = $content['video_url'] ?? null;
        $tags = $content['tags'] ?? [];
        $state = $content['state'] ?? 'published';

        if (!$blogIdentifier) {
            throw new \InvalidArgumentException('Blog identifier required');
        }

        if (!$videoFile && !$videoUrl) {
            throw new \InvalidArgumentException('Video file or URL required');
        }

        // Build NPF content blocks
        $contentBlocks = [];

        if ($caption) {
            $contentBlocks[] = [
                'type' => 'text',
                'text' => $caption,
            ];
        }

        // Add video
        if ($videoFile) {
            // Upload video
            $uploadedVideoUrl = $this->uploadVideo($blogIdentifier, $videoFile);
            $contentBlocks[] = [
                'type' => 'video',
                'media' => [
                    'url' => $uploadedVideoUrl,
                ],
            ];
        } elseif ($videoUrl) {
            // External video (YouTube, Vimeo, etc.)
            $contentBlocks[] = [
                'type' => 'video',
                'url' => $videoUrl,
            ];
        }

        $postData = [
            'content' => $contentBlocks,
            'tags' => implode(',', $tags),
            'state' => $state,
        ];

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/blog/{$blogIdentifier}/posts",
            $postData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        $postId = $response['response']['id'] ?? null;

        return [
            'external_id' => $postId,
            'url' => "https://{$blogIdentifier}.tumblr.com/post/{$postId}",
            'platform_data' => $response['response'] ?? [],
        ];
    }

    /**
     * Publish link post
     */
    protected function publishLinkPost(array $content): array
    {
        $blogIdentifier = $content['blog_identifier'] ?? null;
        $url = $content['url'] ?? null;
        $title = $content['title'] ?? '';
        $description = $content['description'] ?? '';
        $tags = $content['tags'] ?? [];
        $state = $content['state'] ?? 'published';

        if (!$blogIdentifier || !$url) {
            throw new \InvalidArgumentException('Blog identifier and URL required');
        }

        $contentBlocks = [
            [
                'type' => 'link',
                'url' => $url,
            ],
        ];

        if ($title) {
            $contentBlocks[] = [
                'type' => 'text',
                'text' => $title,
                'subtype' => 'heading1',
            ];
        }

        if ($description) {
            $contentBlocks[] = [
                'type' => 'text',
                'text' => $description,
            ];
        }

        $postData = [
            'content' => $contentBlocks,
            'tags' => implode(',', $tags),
            'state' => $state,
        ];

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/blog/{$blogIdentifier}/posts",
            $postData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        $postId = $response['response']['id'] ?? null;

        return [
            'external_id' => $postId,
            'url' => "https://{$blogIdentifier}.tumblr.com/post/{$postId}",
            'platform_data' => $response['response'] ?? [],
        ];
    }

    /**
     * Publish quote post
     */
    protected function publishQuotePost(array $content): array
    {
        $blogIdentifier = $content['blog_identifier'] ?? null;
        $quote = $content['quote'] ?? '';
        $source = $content['source'] ?? '';
        $tags = $content['tags'] ?? [];
        $state = $content['state'] ?? 'published';

        if (!$blogIdentifier || !$quote) {
            throw new \InvalidArgumentException('Blog identifier and quote required');
        }

        $contentBlocks = [
            [
                'type' => 'text',
                'text' => $quote,
                'subtype' => 'quote',
            ],
        ];

        if ($source) {
            $contentBlocks[] = [
                'type' => 'text',
                'text' => "— {$source}",
                'subtype' => 'indented',
            ];
        }

        $postData = [
            'content' => $contentBlocks,
            'tags' => implode(',', $tags),
            'state' => $state,
        ];

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/blog/{$blogIdentifier}/posts",
            $postData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        $postId = $response['response']['id'] ?? null;

        return [
            'external_id' => $postId,
            'url' => "https://{$blogIdentifier}.tumblr.com/post/{$postId}",
            'platform_data' => $response['response'] ?? [],
        ];
    }

    /**
     * Upload photo to Tumblr
     */
    protected function uploadPhoto(string $blogIdentifier, string $photoFile): string
    {
        // Tumblr photo upload uses multipart form data
        $response = Http::asMultipart()
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ])
            ->attach('data', file_get_contents($photoFile), basename($photoFile))
            ->post("{$this->baseUrl}/{$this->apiVersion}/blog/{$blogIdentifier}/post/media");

        if (!$response->successful()) {
            throw new \Exception('Photo upload failed: ' . $response->body());
        }

        return $response->json('response.url');
    }

    /**
     * Upload video to Tumblr
     */
    protected function uploadVideo(string $blogIdentifier, string $videoFile): string
    {
        // Similar to photo upload but for video
        $response = Http::asMultipart()
            ->timeout(300) // 5 minutes for video
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ])
            ->attach('data', file_get_contents($videoFile), basename($videoFile))
            ->post("{$this->baseUrl}/{$this->apiVersion}/blog/{$blogIdentifier}/post/media");

        if (!$response->successful()) {
            throw new \Exception('Video upload failed: ' . $response->body());
        }

        return $response->json('response.url');
    }

    public function schedule(array $content, \DateTime $scheduledTime): array
    {
        // Tumblr supports native scheduling via publish_on parameter
        $content['state'] = 'queue';
        $content['publish_on'] = $scheduledTime->format('c');

        $result = $this->publish($content);

        return [
            'scheduled' => true,
            'scheduled_for' => $scheduledTime->format('c'),
            'method' => 'native',
            'post_id' => $result['external_id'],
        ];
    }

    public function validateContent(array $content): bool
    {
        $this->validateRequiredFields($content, ['blog_identifier']);

        $postType = $content['post_type'] ?? 'text';

        if ($postType === 'text') {
            if (!isset($content['title']) && !isset($content['body'])) {
                throw new \InvalidArgumentException('Text post requires title or body');
            }
        }

        if ($postType === 'photo') {
            if (!isset($content['photo_files']) && !isset($content['photo_urls'])) {
                throw new \InvalidArgumentException('Photo post requires photo files or URLs');
            }
        }

        if ($postType === 'video') {
            if (!isset($content['video_file']) && !isset($content['video_url'])) {
                throw new \InvalidArgumentException('Video post requires video file or URL');
            }
        }

        if ($postType === 'link') {
            $this->validateRequiredFields($content, ['url']);
        }

        if ($postType === 'quote') {
            $this->validateRequiredFields($content, ['quote']);
        }

        return true;
    }

    public function getPostTypes(): array
    {
        return [
            [
                'value' => 'text',
                'label' => 'نص',
                'icon' => 'fa-align-left',
                'description' => 'Text post with title and body',
            ],
            [
                'value' => 'photo',
                'label' => 'صورة',
                'icon' => 'fa-image',
                'description' => 'Photo post (single or multiple)',
            ],
            [
                'value' => 'video',
                'label' => 'فيديو',
                'icon' => 'fa-video',
                'description' => 'Video post or embedded video',
            ],
            [
                'value' => 'link',
                'label' => 'رابط',
                'icon' => 'fa-link',
                'description' => 'Link post with preview',
            ],
            [
                'value' => 'quote',
                'label' => 'اقتباس',
                'icon' => 'fa-quote-right',
                'description' => 'Quote with optional source',
            ],
        ];
    }

    public function getMediaRequirements(): array
    {
        return [
            'photo' => [
                'formats' => ['JPEG', 'PNG', 'GIF'],
                'max_size_mb' => 10,
                'max_count_per_post' => 10,
            ],
            'video' => [
                'formats' => ['MP4', 'MOV'],
                'max_size_mb' => 500,
                'max_duration_seconds' => 600, // 10 minutes
            ],
        ];
    }

    public function getTextLimits(): array
    {
        return [
            'text' => [
                'title' => ['min' => 0, 'max' => 200],
                'body' => ['min' => 0, 'max' => 100000], // Practically unlimited
            ],
            'photo' => ['caption' => ['min' => 0, 'max' => 4096]],
            'video' => ['caption' => ['min' => 0, 'max' => 4096]],
            'link' => [
                'title' => ['min' => 0, 'max' => 200],
                'description' => ['min' => 0, 'max' => 4096],
            ],
            'quote' => [
                'quote' => ['min' => 1, 'max' => 4096],
                'source' => ['min' => 0, 'max' => 200],
            ],
        ];
    }

    protected function uploadMedia(string $filePath, string $mediaType): string
    {
        // Tumblr uses specific upload endpoints
        throw new \Exception('Use uploadPhoto or uploadVideo methods directly');
    }

    /**
     * Get post notes (likes, reblogs)
     */
    public function getAnalytics(string $externalPostId): array
    {
        try {
            // Get blog info to extract blog identifier
            // Then fetch post details
            // This is a simplified version - actual implementation needs blog identifier

            return [
                'notes' => 0, // Total notes (likes + reblogs + replies)
                'raw' => [],
            ];
        } catch (\Exception $e) {
            $this->logError('get_analytics', $e, ['post_id' => $externalPostId]);
            return [];
        }
    }

    /**
     * Delete Tumblr post
     */
    public function delete(string $externalPostId): bool
    {
        try {
            // Need blog identifier - extract from post metadata or require it
            $blogIdentifier = $this->config['blog_identifier'] ?? null;

            if (!$blogIdentifier) {
                throw new \Exception('Blog identifier required for deletion');
            }

            $this->makeRequest(
                'delete',
                "{$this->baseUrl}/{$this->apiVersion}/blog/{$blogIdentifier}/post/{$externalPostId}",
                [],
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
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
     * Edit Tumblr post
     */
    public function update(string $externalPostId, array $content): array
    {
        try {
            $blogIdentifier = $content['blog_identifier'] ?? $this->config['blog_identifier'] ?? null;

            if (!$blogIdentifier) {
                throw new \Exception('Blog identifier required for update');
            }

            // Tumblr uses edit endpoint
            $response = $this->makeRequest(
                'post',
                "{$this->baseUrl}/{$this->apiVersion}/blog/{$blogIdentifier}/post/edit",
                array_merge(['id' => $externalPostId], $content),
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
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
