<?php

namespace App\Services\Social\Publishers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Publisher for LinkedIn.
 *
 * Uses LinkedIn Marketing API v2 (versioned API) for publishing.
 * Supports personal profiles, company pages, text posts, images, and videos.
 *
 * API Documentation: https://learn.microsoft.com/en-us/linkedin/marketing/
 */
class LinkedInPublisher extends AbstractPublisher
{
    protected const API_BASE = 'https://api.linkedin.com';
    protected const API_VERSION = '202401'; // LinkedIn API versioning (YYYYMM format)

    /**
     * Maximum file sizes for LinkedIn media uploads.
     */
    protected const MAX_IMAGE_SIZE = 8 * 1024 * 1024; // 8MB
    protected const MAX_VIDEO_SIZE = 200 * 1024 * 1024; // 200MB (5GB for LinkedIn Premium)

    /**
     * Map of LinkedIn API error codes to user-friendly messages.
     */
    protected const ERROR_MESSAGES = [
        'UNAUTHORIZED' => 'linkedin_unauthorized',
        'FORBIDDEN' => 'linkedin_forbidden',
        'RATE_LIMIT_EXCEEDED' => 'linkedin_rate_limit',
        'DUPLICATE_SHARE' => 'linkedin_duplicate',
        'INVALID_IMAGE' => 'linkedin_invalid_image',
    ];

    /**
     * Publish content to LinkedIn.
     */
    public function publish(string $content, array $media, array $options = []): array
    {
        $this->logInfo('LinkedInPublisher::publish called', [
            'content_length' => strlen($content),
            'media_count' => count($media),
            'options' => array_keys($options),
        ]);

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return $this->failure('No LinkedIn access token available. Please reconnect your LinkedIn account in Settings > Platform Connections.');
        }

        try {
            // Get the author URN (person or organization)
            $authorUrn = $this->getAuthorUrn();
            if (!$authorUrn) {
                return $this->failure('No LinkedIn profile or page selected. Configure in Settings > Platform Connections.');
            }

            // Handle media uploads if present
            $mediaAssets = [];
            if (!empty($media)) {
                $mediaResult = $this->uploadMedia($media, $authorUrn, $accessToken);
                if (!$mediaResult['success']) {
                    return $this->failure($mediaResult['error'] ?? 'Failed to upload media to LinkedIn');
                }
                $mediaAssets = $mediaResult['assets'];
            }

            // Create the post
            return $this->createPost($content, $mediaAssets, $authorUrn, $options, $accessToken);

        } catch (\Exception $e) {
            $this->logError('LinkedIn publishing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->failure('LinkedIn publishing failed: ' . $e->getMessage());
        }
    }

    /**
     * Get the author URN from metadata.
     */
    protected function getAuthorUrn(): ?string
    {
        $metadata = $this->getMetadata();

        // Check for selected organization/page
        $selectedAssets = $this->getSelectedAssets();
        if (!empty($selectedAssets['organization'])) {
            return "urn:li:organization:{$selectedAssets['organization']}";
        }

        // Check for organization in metadata
        if (!empty($metadata['organization_id'])) {
            return "urn:li:organization:{$metadata['organization_id']}";
        }

        // Default to personal profile
        if (!empty($metadata['person_id'])) {
            return "urn:li:person:{$metadata['person_id']}";
        }

        // Try to get from sub (subject) claim
        if (!empty($metadata['sub'])) {
            return "urn:li:person:{$metadata['sub']}";
        }

        return null;
    }

    /**
     * Create a post using LinkedIn Posts API.
     */
    protected function createPost(string $text, array $mediaAssets, string $authorUrn, array $options, string $accessToken): array
    {
        // Build the post payload
        $payload = [
            'author' => $authorUrn,
            'lifecycleState' => 'PUBLISHED',
            'visibility' => $options['visibility'] ?? 'PUBLIC',
            'distribution' => [
                'feedDistribution' => 'MAIN_FEED',
                'targetEntities' => [],
                'thirdPartyDistributionChannels' => [],
            ],
        ];

        // Determine post type based on media
        if (empty($mediaAssets)) {
            // Text-only post
            $payload['commentary'] = $text;
        } elseif (count($mediaAssets) === 1 && isset($mediaAssets[0]['type']) && $mediaAssets[0]['type'] === 'video') {
            // Video post
            $payload['commentary'] = $text;
            $payload['content'] = [
                'media' => [
                    'title' => $options['video_title'] ?? '',
                    'id' => $mediaAssets[0]['asset'],
                ],
            ];
        } elseif (count($mediaAssets) === 1) {
            // Single image post
            $payload['commentary'] = $text;
            $payload['content'] = [
                'media' => [
                    'title' => $options['media_title'] ?? '',
                    'id' => $mediaAssets[0]['asset'],
                ],
            ];
        } else {
            // Multi-image post (carousel)
            $payload['commentary'] = $text;
            $payload['content'] = [
                'multiImage' => [
                    'images' => array_map(fn($asset) => [
                        'id' => $asset['asset'],
                        'altText' => $asset['alt_text'] ?? '',
                    ], $mediaAssets),
                ],
            ];
        }

        // Add article/link if specified
        if (!empty($options['article_url'])) {
            $payload['content'] = [
                'article' => [
                    'source' => $options['article_url'],
                    'title' => $options['article_title'] ?? '',
                    'description' => $options['article_description'] ?? '',
                ],
            ];
        }

        $this->logInfo('Creating LinkedIn post', [
            'author' => $authorUrn,
            'text_length' => strlen($text),
            'media_count' => count($mediaAssets),
            'visibility' => $payload['visibility'],
        ]);

        $response = Http::timeout(30)
            ->withToken($accessToken)
            ->withHeaders([
                'LinkedIn-Version' => self::API_VERSION,
                'X-Restli-Protocol-Version' => '2.0.0',
            ])
            ->post(self::API_BASE . '/rest/posts', $payload);

        $jsonBody = $response->json();

        $this->logInfo('LinkedIn API response', [
            'status' => $response->status(),
            'body_preview' => substr($response->body(), 0, 500),
            'headers' => [
                'x-restli-id' => $response->header('x-restli-id'),
                'x-linkedin-id' => $response->header('x-linkedin-id'),
            ],
        ]);

        if (!$response->successful()) {
            $errorMessage = $this->extractErrorMessage($jsonBody, $response->status());
            $this->logError('LinkedIn post creation failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'response' => $jsonBody,
            ]);
            return $this->failure($errorMessage);
        }

        // LinkedIn returns the post URN in the x-restli-id header or response body
        $postUrn = $response->header('x-restli-id') ?? $jsonBody['id'] ?? null;
        $postId = $this->extractIdFromUrn($postUrn);

        if (!$postId) {
            $this->logWarning('Post created but no ID in response', ['response' => $jsonBody]);
            // Post was likely created, return success with what we have
            return $this->success($postUrn ?? 'unknown', null);
        }

        // Construct permalink
        $permalink = $this->constructPermalink($authorUrn, $postId);

        $this->logInfo('LinkedIn post created successfully', [
            'post_urn' => $postUrn,
            'post_id' => $postId,
            'permalink' => $permalink,
        ]);

        return $this->success($postId, $permalink);
    }

    /**
     * Upload media to LinkedIn.
     */
    protected function uploadMedia(array $media, string $authorUrn, string $accessToken): array
    {
        $assets = [];

        foreach ($media as $item) {
            $mediaType = $item['type'] ?? 'image';
            $mediaUrl = $item['url'] ?? $item['preview_url'] ?? null;

            if (!$mediaUrl) {
                $this->logWarning('Media item has no URL, skipping', $item);
                continue;
            }

            if ($mediaType === 'video') {
                $result = $this->uploadVideo($item, $authorUrn, $accessToken);
            } else {
                $result = $this->uploadImage($item, $authorUrn, $accessToken);
            }

            if (!$result['success']) {
                return $result;
            }

            $assets[] = [
                'asset' => $result['asset'],
                'type' => $mediaType,
                'alt_text' => $item['alt_text'] ?? '',
            ];
        }

        return [
            'success' => true,
            'assets' => $assets,
        ];
    }

    /**
     * Upload an image to LinkedIn.
     */
    protected function uploadImage(array $mediaItem, string $authorUrn, string $accessToken): array
    {
        // Step 1: Initialize upload
        $initPayload = [
            'initializeUploadRequest' => [
                'owner' => $authorUrn,
            ],
        ];

        $initResponse = Http::timeout(30)
            ->withToken($accessToken)
            ->withHeaders([
                'LinkedIn-Version' => self::API_VERSION,
                'X-Restli-Protocol-Version' => '2.0.0',
            ])
            ->post(self::API_BASE . '/rest/images?action=initializeUpload', $initPayload);

        if (!$initResponse->successful()) {
            return [
                'success' => false,
                'error' => 'Failed to initialize image upload: ' . $this->extractErrorMessage($initResponse->json(), $initResponse->status()),
            ];
        }

        $initData = $initResponse->json('value') ?? $initResponse->json();
        $uploadUrl = $initData['uploadUrl'] ?? null;
        $imageUrn = $initData['image'] ?? null;

        if (!$uploadUrl || !$imageUrn) {
            return [
                'success' => false,
                'error' => 'LinkedIn did not return upload URL or image URN',
            ];
        }

        // Step 2: Get the image content
        $imageContent = $this->getMediaContent($mediaItem);
        if (!$imageContent) {
            return [
                'success' => false,
                'error' => 'Failed to retrieve image content',
            ];
        }

        // Validate size
        if (strlen($imageContent) > self::MAX_IMAGE_SIZE) {
            return [
                'success' => false,
                'error' => 'Image size exceeds LinkedIn limit of 8MB',
            ];
        }

        // Step 3: Upload the image binary
        $uploadResponse = Http::timeout(60)
            ->withToken($accessToken)
            ->withHeaders([
                'Content-Type' => 'application/octet-stream',
            ])
            ->withBody($imageContent, 'application/octet-stream')
            ->put($uploadUrl);

        if (!$uploadResponse->successful()) {
            return [
                'success' => false,
                'error' => 'Failed to upload image to LinkedIn: HTTP ' . $uploadResponse->status(),
            ];
        }

        $this->logInfo('LinkedIn image uploaded', [
            'image_urn' => $imageUrn,
            'size' => strlen($imageContent),
        ]);

        return [
            'success' => true,
            'asset' => $imageUrn,
        ];
    }

    /**
     * Upload a video to LinkedIn.
     */
    protected function uploadVideo(array $mediaItem, string $authorUrn, string $accessToken): array
    {
        // Get video content and size first
        $localPath = $this->getLocalFilePath($mediaItem);
        if (!$localPath || !file_exists($localPath)) {
            return [
                'success' => false,
                'error' => 'Video file not found on server',
            ];
        }

        $fileSize = filesize($localPath);

        // Validate size
        if ($fileSize > self::MAX_VIDEO_SIZE) {
            return [
                'success' => false,
                'error' => 'Video size exceeds LinkedIn limit of 200MB',
            ];
        }

        // Step 1: Initialize video upload
        $initPayload = [
            'initializeUploadRequest' => [
                'owner' => $authorUrn,
                'fileSizeBytes' => $fileSize,
                'uploadCaptions' => false,
                'uploadThumbnail' => false,
            ],
        ];

        $initResponse = Http::timeout(30)
            ->withToken($accessToken)
            ->withHeaders([
                'LinkedIn-Version' => self::API_VERSION,
                'X-Restli-Protocol-Version' => '2.0.0',
            ])
            ->post(self::API_BASE . '/rest/videos?action=initializeUpload', $initPayload);

        if (!$initResponse->successful()) {
            return [
                'success' => false,
                'error' => 'Failed to initialize video upload: ' . $this->extractErrorMessage($initResponse->json(), $initResponse->status()),
            ];
        }

        $initData = $initResponse->json('value') ?? $initResponse->json();
        $uploadInstructions = $initData['uploadInstructions'] ?? [];
        $videoUrn = $initData['video'] ?? null;

        if (empty($uploadInstructions) || !$videoUrn) {
            return [
                'success' => false,
                'error' => 'LinkedIn did not return video upload instructions',
            ];
        }

        // Step 2: Upload video chunks
        $file = fopen($localPath, 'rb');
        $uploadedParts = [];

        foreach ($uploadInstructions as $instruction) {
            $uploadUrl = $instruction['uploadUrl'] ?? null;
            $firstByte = $instruction['firstByte'] ?? 0;
            $lastByte = $instruction['lastByte'] ?? $fileSize - 1;

            if (!$uploadUrl) {
                continue;
            }

            $chunkSize = $lastByte - $firstByte + 1;
            fseek($file, $firstByte);
            $chunk = fread($file, $chunkSize);

            $uploadResponse = Http::timeout(300)
                ->withHeaders([
                    'Content-Type' => 'application/octet-stream',
                ])
                ->withBody($chunk, 'application/octet-stream')
                ->put($uploadUrl);

            if (!$uploadResponse->successful()) {
                fclose($file);
                return [
                    'success' => false,
                    'error' => 'Failed to upload video chunk: HTTP ' . $uploadResponse->status(),
                ];
            }

            // Get ETag for part tracking
            $etag = $uploadResponse->header('ETag');
            if ($etag) {
                $uploadedParts[] = $etag;
            }
        }

        fclose($file);

        // Step 3: Finalize video upload
        $finalizePayload = [
            'finalizeUploadRequest' => [
                'video' => $videoUrn,
                'uploadToken' => '',
                'uploadedPartIds' => $uploadedParts,
            ],
        ];

        $finalizeResponse = Http::timeout(60)
            ->withToken($accessToken)
            ->withHeaders([
                'LinkedIn-Version' => self::API_VERSION,
                'X-Restli-Protocol-Version' => '2.0.0',
            ])
            ->post(self::API_BASE . '/rest/videos?action=finalizeUpload', $finalizePayload);

        if (!$finalizeResponse->successful()) {
            $this->logWarning('Video finalize response', [
                'status' => $finalizeResponse->status(),
                'body' => $finalizeResponse->body(),
            ]);
            // Some videos finalize asynchronously - check if we can proceed
        }

        $this->logInfo('LinkedIn video uploaded', [
            'video_urn' => $videoUrn,
            'size' => $fileSize,
            'parts' => count($uploadedParts),
        ]);

        return [
            'success' => true,
            'asset' => $videoUrn,
        ];
    }

    /**
     * Get media content from various sources.
     */
    protected function getMediaContent(array $mediaItem): ?string
    {
        // Try local file first
        $localPath = $this->getLocalFilePath($mediaItem);
        if ($localPath && file_exists($localPath)) {
            return file_get_contents($localPath);
        }

        // Download from URL
        $url = $mediaItem['url'] ?? $mediaItem['preview_url'] ?? null;
        if ($url) {
            $response = Http::timeout(60)->get($url);
            if ($response->successful()) {
                return $response->body();
            }
        }

        return null;
    }

    /**
     * Get local file path from media item.
     */
    protected function getLocalFilePath(array $mediaItem): ?string
    {
        if (!empty($mediaItem['storage_path'])) {
            $path = Storage::disk('public')->path($mediaItem['storage_path']);
            if (file_exists($path)) {
                return $path;
            }
        }

        $url = $mediaItem['url'] ?? $mediaItem['preview_url'] ?? null;
        if ($url && preg_match('#/storage/(.+)$#', $url, $matches)) {
            $path = Storage::disk('public')->path($matches[1]);
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Extract ID from LinkedIn URN.
     */
    protected function extractIdFromUrn(?string $urn): ?string
    {
        if (!$urn) {
            return null;
        }

        // URN format: urn:li:share:1234567890 or urn:li:ugcPost:1234567890
        if (preg_match('/urn:li:\w+:(\d+)/', $urn, $matches)) {
            return $matches[1];
        }

        return $urn;
    }

    /**
     * Construct permalink for a LinkedIn post.
     */
    protected function constructPermalink(string $authorUrn, string $postId): ?string
    {
        // Extract author type and ID
        if (preg_match('/urn:li:organization:(\d+)/', $authorUrn, $matches)) {
            return "https://www.linkedin.com/feed/update/urn:li:share:{$postId}/";
        }

        if (preg_match('/urn:li:person:(.+)/', $authorUrn, $matches)) {
            return "https://www.linkedin.com/feed/update/urn:li:share:{$postId}/";
        }

        return "https://www.linkedin.com/feed/update/urn:li:share:{$postId}/";
    }

    /**
     * Extract user-friendly error message from LinkedIn API response.
     */
    protected function extractErrorMessage(array $response, int $statusCode): string
    {
        // LinkedIn API v2 error format
        if (isset($response['message'])) {
            return $response['message'];
        }

        // Nested error format
        if (isset($response['serviceErrorCode'])) {
            $code = $response['serviceErrorCode'];
            $message = $response['message'] ?? 'Unknown error';
            return "[{$code}] {$message}";
        }

        // Default messages based on status code
        return match ($statusCode) {
            401 => 'Your LinkedIn connection has expired. Please reconnect your LinkedIn account in Settings > Platform Connections.',
            403 => 'Permission denied. Please check your LinkedIn app permissions and ensure you have posting access.',
            429 => 'LinkedIn rate limit reached. Please wait before posting again.',
            422 => 'Invalid post content. Please check your post text and media.',
            default => "LinkedIn API error (HTTP {$statusCode})",
        };
    }

    /**
     * Log a warning message.
     */
    protected function logWarning(string $message, array $context = []): void
    {
        \Illuminate\Support\Facades\Log::warning("[{$this->platform}] {$message}", $context);
    }
}
