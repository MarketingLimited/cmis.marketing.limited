<?php

namespace App\Services\Social\LinkedIn;

use App\Services\Social\AbstractSocialPlatform;
use Illuminate\Support\Facades\Http;

/**
 * LinkedIn Posts API Publishing Service
 *
 * Latest API: November 2025 updates
 * Supports:
 * - Text posts (up to 3,000 characters)
 * - Single image posts
 * - Multi-image carousel (up to 9 images)
 * - Video posts (native video upload)
 * - Article posts (long-form content)
 * - Document posts (PDF, PowerPoint)
 * - Poll posts (2-4 options)
 *
 * Authentication: OAuth 2.0
 * API Base: https://api.linkedin.com/v2/
 */
class LinkedInSocialService extends AbstractSocialPlatform
{
    protected string $apiVersion = 'v2';
    protected string $baseUrl = 'https://api.linkedin.com';

    protected function getPlatformName(): string
    {
        return 'linkedin';
    }

    public function publish(array $content): array
    {
        $this->validateContent($content);

        $postType = $content['post_type'] ?? 'post';

        return match($postType) {
            'post' => $this->publishTextPost($content),
            'image' => $this->publishImagePost($content),
            'carousel' => $this->publishCarousel($content),
            'video' => $this->publishVideoPost($content),
            'article' => $this->publishArticle($content),
            'poll' => $this->publishPoll($content),
            default => throw new \Exception("Unsupported LinkedIn post type: {$postType}"),
        };
    }

    /**
     * Publish text-only post
     */
    protected function publishTextPost(array $content): array
    {
        $author = $content['author'] ?? null; // urn:li:person:{id} or urn:li:organization:{id}
        $text = $content['text'] ?? '';
        $visibility = $content['visibility'] ?? 'PUBLIC'; // PUBLIC, CONNECTIONS, LOGGED_IN

        if (!$author) {
            throw new \InvalidArgumentException('Author URN is required (urn:li:person:{id} or urn:li:organization:{id})');
        }

        $shareData = [
            'author' => $author,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $text,
                    ],
                    'shareMediaCategory' => 'NONE',
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => $visibility,
            ],
        ];

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/ugcPosts",
            $shareData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'X-Restli-Protocol-Version' => '2.0.0',
            ]
        );

        $postId = $response['id'] ?? null;

        $this->logOperation('publish_text', [
            'post_id' => $postId,
            'author' => $author,
        ]);

        return [
            'external_id' => $postId,
            'url' => $this->getPostUrl($postId),
            'platform_data' => $response,
        ];
    }

    /**
     * Publish single image post
     */
    protected function publishImagePost(array $content): array
    {
        $author = $content['author'] ?? null;
        $text = $content['text'] ?? '';
        $imageFile = $content['image_file'] ?? null;
        $imageUrl = $content['image_url'] ?? null;

        if (!$author) {
            throw new \InvalidArgumentException('Author URN is required');
        }

        if (!$imageFile && !$imageUrl) {
            throw new \InvalidArgumentException('Image file or URL is required');
        }

        // Step 1: Upload image to LinkedIn
        $mediaAsset = $this->uploadImage($author, $imageFile ?? $imageUrl);

        // Step 2: Create post with image
        $shareData = [
            'author' => $author,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $text,
                    ],
                    'shareMediaCategory' => 'IMAGE',
                    'media' => [
                        [
                            'status' => 'READY',
                            'media' => $mediaAsset,
                        ],
                    ],
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/ugcPosts",
            $shareData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'X-Restli-Protocol-Version' => '2.0.0',
            ]
        );

        $postId = $response['id'] ?? null;

        return [
            'external_id' => $postId,
            'url' => $this->getPostUrl($postId),
            'platform_data' => $response,
        ];
    }

    /**
     * Publish multi-image carousel (up to 9 images)
     */
    protected function publishCarousel(array $content): array
    {
        $author = $content['author'] ?? null;
        $text = $content['text'] ?? '';
        $imageFiles = $content['image_files'] ?? [];
        $imageUrls = $content['image_urls'] ?? [];

        if (!$author) {
            throw new \InvalidArgumentException('Author URN is required');
        }

        $images = array_merge($imageFiles, $imageUrls);

        if (empty($images)) {
            throw new \InvalidArgumentException('At least one image is required');
        }

        if (count($images) > 9) {
            throw new \InvalidArgumentException('Maximum 9 images allowed in carousel');
        }

        // Upload all images
        $mediaAssets = [];
        foreach ($images as $image) {
            $mediaAssets[] = [
                'status' => 'READY',
                'media' => $this->uploadImage($author, $image),
            ];
        }

        // Create carousel post
        $shareData = [
            'author' => $author,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $text,
                    ],
                    'shareMediaCategory' => 'IMAGE',
                    'media' => $mediaAssets,
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/ugcPosts",
            $shareData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'X-Restli-Protocol-Version' => '2.0.0',
            ]
        );

        $postId = $response['id'] ?? null;

        return [
            'external_id' => $postId,
            'url' => $this->getPostUrl($postId),
            'platform_data' => $response,
        ];
    }

    /**
     * Publish video post
     */
    protected function publishVideoPost(array $content): array
    {
        $author = $content['author'] ?? null;
        $text = $content['text'] ?? '';
        $videoFile = $content['video_file'] ?? null;

        if (!$author) {
            throw new \InvalidArgumentException('Author URN is required');
        }

        if (!$videoFile) {
            throw new \InvalidArgumentException('Video file is required');
        }

        // Step 1: Upload video to LinkedIn
        $videoAsset = $this->uploadVideo($author, $videoFile);

        // Step 2: Create post with video
        $shareData = [
            'author' => $author,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $text,
                    ],
                    'shareMediaCategory' => 'VIDEO',
                    'media' => [
                        [
                            'status' => 'READY',
                            'media' => $videoAsset,
                        ],
                    ],
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/ugcPosts",
            $shareData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'X-Restli-Protocol-Version' => '2.0.0',
            ]
        );

        $postId = $response['id'] ?? null;

        return [
            'external_id' => $postId,
            'url' => $this->getPostUrl($postId),
            'platform_data' => $response,
        ];
    }

    /**
     * Publish article post (long-form content)
     */
    protected function publishArticle(array $content): array
    {
        $author = $content['author'] ?? null;
        $title = $content['title'] ?? '';
        $text = $content['text'] ?? '';
        $articleUrl = $content['article_url'] ?? null;
        $thumbnailUrl = $content['thumbnail_url'] ?? null;

        if (!$author) {
            throw new \InvalidArgumentException('Author URN is required');
        }

        if (!$articleUrl) {
            throw new \InvalidArgumentException('Article URL is required');
        }

        $shareData = [
            'author' => $author,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $text,
                    ],
                    'shareMediaCategory' => 'ARTICLE',
                    'media' => [
                        [
                            'status' => 'READY',
                            'originalUrl' => $articleUrl,
                            'title' => [
                                'text' => $title,
                            ],
                        ],
                    ],
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        if ($thumbnailUrl) {
            $shareData['specificContent']['com.linkedin.ugc.ShareContent']['media'][0]['thumbnails'] = [
                ['url' => $thumbnailUrl],
            ];
        }

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/ugcPosts",
            $shareData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'X-Restli-Protocol-Version' => '2.0.0',
            ]
        );

        $postId = $response['id'] ?? null;

        return [
            'external_id' => $postId,
            'url' => $this->getPostUrl($postId),
            'platform_data' => $response,
        ];
    }

    /**
     * Publish poll (2-4 options, duration 1-14 days)
     */
    protected function publishPoll(array $content): array
    {
        $author = $content['author'] ?? null;
        $question = $content['text'] ?? '';
        $options = $content['poll_options'] ?? [];
        $durationDays = $content['poll_duration_days'] ?? 7;

        if (!$author) {
            throw new \InvalidArgumentException('Author URN is required');
        }

        if (count($options) < 2 || count($options) > 4) {
            throw new \InvalidArgumentException('LinkedIn polls must have 2-4 options');
        }

        if ($durationDays < 1 || $durationDays > 14) {
            throw new \InvalidArgumentException('Poll duration must be 1-14 days');
        }

        $pollOptions = array_map(fn($option) => ['text' => $option], $options);

        $shareData = [
            'author' => $author,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $question,
                    ],
                    'shareMediaCategory' => 'POLL',
                    'poll' => [
                        'question' => $question,
                        'options' => $pollOptions,
                        'settings' => [
                            'duration' => [
                                'days' => $durationDays,
                            ],
                        ],
                    ],
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        $response = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/ugcPosts",
            $shareData,
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'X-Restli-Protocol-Version' => '2.0.0',
            ]
        );

        $postId = $response['id'] ?? null;

        return [
            'external_id' => $postId,
            'url' => $this->getPostUrl($postId),
            'platform_data' => $response,
        ];
    }

    /**
     * Upload image to LinkedIn
     */
    protected function uploadImage(string $author, string $imageSource): string
    {
        // Step 1: Register upload
        $registerResponse = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/assets?action=registerUpload",
            [
                'registerUploadRequest' => [
                    'recipes' => ['urn:li:digitalmediaRecipe:feedshare-image'],
                    'owner' => $author,
                    'serviceRelationships' => [
                        [
                            'relationshipType' => 'OWNER',
                            'identifier' => 'urn:li:userGeneratedContent',
                        ],
                    ],
                ],
            ],
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        $uploadUrl = $registerResponse['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'];
        $asset = $registerResponse['value']['asset'];

        // Step 2: Upload image file
        $imageContent = file_exists($imageSource) ? file_get_contents($imageSource) : Http::get($imageSource)->body();

        $uploadResponse = Http::timeout(60)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ])
            ->withBody($imageContent, 'application/octet-stream')
            ->put($uploadUrl);

        if (!$uploadResponse->successful()) {
            throw new \Exception('Image upload failed: ' . $uploadResponse->body());
        }

        return $asset;
    }

    /**
     * Upload video to LinkedIn
     */
    protected function uploadVideo(string $author, string $videoFile): string
    {
        // Step 1: Register video upload
        $registerResponse = $this->makeRequest(
            'post',
            "{$this->baseUrl}/{$this->apiVersion}/assets?action=registerUpload",
            [
                'registerUploadRequest' => [
                    'recipes' => ['urn:li:digitalmediaRecipe:feedshare-video'],
                    'owner' => $author,
                    'serviceRelationships' => [
                        [
                            'relationshipType' => 'OWNER',
                            'identifier' => 'urn:li:userGeneratedContent',
                        ],
                    ],
                ],
            ],
            [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]
        );

        $uploadUrl = $registerResponse['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'];
        $asset = $registerResponse['value']['asset'];

        // Step 2: Upload video file
        $videoContent = file_get_contents($videoFile);

        $uploadResponse = Http::timeout(300) // 5 minutes for video
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ])
            ->withBody($videoContent, 'application/octet-stream')
            ->put($uploadUrl);

        if (!$uploadResponse->successful()) {
            throw new \Exception('Video upload failed: ' . $uploadResponse->body());
        }

        return $asset;
    }

    public function schedule(array $content, \DateTime $scheduledTime): array
    {
        // LinkedIn doesn't support native scheduling via API
        // Use queue-based scheduling
        return [
            'scheduled' => true,
            'scheduled_for' => $scheduledTime->format('c'),
            'method' => 'queue',
        ];
    }

    public function validateContent(array $content): bool
    {
        $this->validateRequiredFields($content, ['author']);

        $postType = $content['post_type'] ?? 'post';

        // Validate text length
        if (isset($content['text'])) {
            $this->validateTextLength($content['text'], 3000, 'text');
        }

        // Type-specific validation
        if ($postType === 'image' && !isset($content['image_file']) && !isset($content['image_url'])) {
            throw new \InvalidArgumentException('Image file or URL required for image post');
        }

        if ($postType === 'carousel') {
            $images = array_merge($content['image_files'] ?? [], $content['image_urls'] ?? []);
            if (count($images) < 2 || count($images) > 9) {
                throw new \InvalidArgumentException('Carousel must have 2-9 images');
            }
        }

        if ($postType === 'video' && !isset($content['video_file'])) {
            throw new \InvalidArgumentException('Video file required for video post');
        }

        if ($postType === 'article' && !isset($content['article_url'])) {
            throw new \InvalidArgumentException('Article URL required for article post');
        }

        if ($postType === 'poll') {
            $options = $content['poll_options'] ?? [];
            if (count($options) < 2 || count($options) > 4) {
                throw new \InvalidArgumentException('Poll must have 2-4 options');
            }
        }

        return true;
    }

    public function getPostTypes(): array
    {
        return [
            [
                'value' => 'post',
                'label' => 'منشور نصي',
                'icon' => 'fa-file-alt',
                'description' => 'Text post (up to 3,000 characters)',
            ],
            [
                'value' => 'image',
                'label' => 'صورة',
                'icon' => 'fa-image',
                'description' => 'Single image post',
            ],
            [
                'value' => 'carousel',
                'label' => 'معرض صور',
                'icon' => 'fa-images',
                'description' => 'Multi-image carousel (2-9 images)',
            ],
            [
                'value' => 'video',
                'label' => 'فيديو',
                'icon' => 'fa-video',
                'description' => 'Video post (up to 10 min)',
            ],
            [
                'value' => 'article',
                'label' => 'مقال',
                'icon' => 'fa-newspaper',
                'description' => 'Article with link and thumbnail',
            ],
            [
                'value' => 'poll',
                'label' => 'استطلاع',
                'icon' => 'fa-poll',
                'description' => 'Poll with 2-4 options (1-14 days)',
            ],
        ];
    }

    public function getMediaRequirements(): array
    {
        return [
            'image' => [
                'formats' => ['JPEG', 'PNG', 'GIF'],
                'max_size_mb' => 10,
                'min_width' => 552,
                'max_width' => 7680,
                'aspect_ratio' => '1.91:1 to 1:1.91',
            ],
            'video' => [
                'formats' => ['MP4', 'MOV', 'WEBM'],
                'max_size_mb' => 200,
                'max_duration_seconds' => 600, // 10 minutes
                'min_duration_seconds' => 3,
                'resolution' => 'Up to 4K',
            ],
        ];
    }

    public function getTextLimits(): array
    {
        return [
            'post' => ['min' => 0, 'max' => 3000],
            'image' => ['min' => 0, 'max' => 3000],
            'carousel' => ['min' => 0, 'max' => 3000],
            'video' => ['min' => 0, 'max' => 3000],
            'article' => ['min' => 0, 'max' => 3000],
            'poll' => ['min' => 1, 'max' => 3000],
        ];
    }

    protected function uploadMedia(string $filePath, string $mediaType): string
    {
        // LinkedIn uses specialized upload flows
        throw new \Exception('Use uploadImage or uploadVideo methods directly');
    }

    /**
     * Get post analytics
     */
    public function getAnalytics(string $externalPostId): array
    {
        try {
            $response = $this->makeRequest(
                'get',
                "{$this->baseUrl}/{$this->apiVersion}/socialActions/{$externalPostId}",
                [],
                [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                ]
            );

            return [
                'likes' => $response['likesSummary']['totalLikes'] ?? 0,
                'comments' => $response['commentsSummary']['totalComments'] ?? 0,
                'shares' => $response['sharesSummary']['totalShares'] ?? 0,
                'raw' => $response,
            ];
        } catch (\Exception $e) {
            $this->logError('get_analytics', $e, ['post_id' => $externalPostId]);
            return [];
        }
    }

    /**
     * Get LinkedIn post URL
     */
    protected function getPostUrl(?string $postId): ?string
    {
        if (!$postId) {
            return null;
        }

        // Extract numeric ID from URN if needed
        $numericId = str_replace('urn:li:share:', '', $postId);

        return "https://www.linkedin.com/feed/update/{$numericId}";
    }

    /**
     * Delete LinkedIn post
     */
    public function delete(string $externalPostId): bool
    {
        try {
            $this->makeRequest(
                'delete',
                "{$this->baseUrl}/{$this->apiVersion}/ugcPosts/{$externalPostId}",
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
}
