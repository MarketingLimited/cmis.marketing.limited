<?php

namespace App\Services\Social\Publishers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Publisher for Twitter/X.
 *
 * Uses Twitter API v2 for tweet creation and v1.1 for media uploads.
 * Requires OAuth 2.0 authentication with tweet.read, tweet.write, users.read scopes.
 */
class TwitterPublisher extends AbstractPublisher
{
    protected const API_V2_BASE = 'https://api.twitter.com/2';
    protected const API_V1_BASE = 'https://upload.twitter.com/1.1';

    /**
     * Maximum file sizes for Twitter media uploads.
     */
    protected const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5MB
    protected const MAX_GIF_SIZE = 15 * 1024 * 1024; // 15MB
    protected const MAX_VIDEO_SIZE = 512 * 1024 * 1024; // 512MB

    /**
     * Map of Twitter API error codes to user-friendly messages.
     */
    protected const ERROR_MESSAGES = [
        'Unauthorized' => 'twitter_unauthorized',
        'Forbidden' => 'twitter_forbidden',
        'RateLimitExceeded' => 'twitter_rate_limit',
        'DuplicateContent' => 'twitter_duplicate',
        'InvalidMedia' => 'twitter_invalid_media',
    ];

    /**
     * Publish content to Twitter/X.
     */
    public function publish(string $content, array $media, array $options = []): array
    {
        $this->logInfo('TwitterPublisher::publish called', [
            'content_length' => strlen($content),
            'media_count' => count($media),
            'options' => array_keys($options),
        ]);

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return $this->failure('No Twitter access token available. Please reconnect your Twitter account in Settings > Platform Connections.');
        }

        try {
            // Handle media uploads if present
            $mediaIds = [];
            if (!empty($media)) {
                $mediaResult = $this->uploadMedia($media, $accessToken);
                if (!$mediaResult['success']) {
                    return $this->failure($mediaResult['error'] ?? 'Failed to upload media to Twitter');
                }
                $mediaIds = $mediaResult['media_ids'];
            }

            // Create the tweet
            return $this->createTweet($content, $mediaIds, $options, $accessToken);

        } catch (\Exception $e) {
            $this->logError('Twitter publishing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->failure('Twitter publishing failed: ' . $e->getMessage());
        }
    }

    /**
     * Create a tweet using Twitter API v2.
     */
    protected function createTweet(string $text, array $mediaIds, array $options, string $accessToken): array
    {
        $payload = ['text' => $text];

        // Add media if uploaded
        if (!empty($mediaIds)) {
            $payload['media'] = ['media_ids' => $mediaIds];
        }

        // Add reply settings if specified
        if (!empty($options['reply_settings'])) {
            $payload['reply_settings'] = $options['reply_settings']; // mentionedUsers, following, everyone
        }

        // Add reply to tweet if this is a reply
        if (!empty($options['reply_to_tweet_id'])) {
            $payload['reply'] = ['in_reply_to_tweet_id' => $options['reply_to_tweet_id']];
        }

        // Add quote tweet if specified
        if (!empty($options['quote_tweet_id'])) {
            $payload['quote_tweet_id'] = $options['quote_tweet_id'];
        }

        // Add poll if specified
        if (!empty($options['poll'])) {
            $payload['poll'] = [
                'options' => $options['poll']['options'],
                'duration_minutes' => $options['poll']['duration_minutes'] ?? 1440, // Default 24 hours
            ];
        }

        $this->logInfo('Creating tweet', [
            'text_length' => strlen($text),
            'media_count' => count($mediaIds),
            'has_poll' => isset($payload['poll']),
        ]);

        $response = Http::timeout(30)
            ->withToken($accessToken)
            ->post(self::API_V2_BASE . '/tweets', $payload);

        $jsonBody = $response->json();

        $this->logInfo('Twitter API response', [
            'status' => $response->status(),
            'body_preview' => substr($response->body(), 0, 500),
        ]);

        if (!$response->successful()) {
            $errorMessage = $this->extractErrorMessage($jsonBody, $response->status());
            $this->logError('Twitter tweet creation failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'response' => $jsonBody,
            ]);
            return $this->failure($errorMessage);
        }

        $tweetId = $jsonBody['data']['id'] ?? null;

        if (!$tweetId) {
            return $this->failure('Tweet created but no ID returned');
        }

        // Get the username from metadata to construct permalink
        $metadata = $this->getMetadata();
        $username = $metadata['username'] ?? $metadata['screen_name'] ?? null;
        $permalink = $username ? "https://twitter.com/{$username}/status/{$tweetId}" : null;

        $this->logInfo('Tweet created successfully', [
            'tweet_id' => $tweetId,
            'permalink' => $permalink,
        ]);

        return $this->success($tweetId, $permalink);
    }

    /**
     * Upload media to Twitter using v1.1 media upload API.
     */
    protected function uploadMedia(array $media, string $accessToken): array
    {
        $mediaIds = [];

        foreach ($media as $item) {
            $mediaUrl = $item['url'] ?? $item['preview_url'] ?? null;
            $mediaType = $item['type'] ?? 'image';

            if (!$mediaUrl) {
                $this->logWarning('Media item has no URL, skipping', $item);
                continue;
            }

            // Get the local file path
            $localPath = $this->getLocalFilePath($item);

            if ($localPath && file_exists($localPath)) {
                // Upload from local file
                $result = $this->uploadLocalMedia($localPath, $mediaType, $accessToken);
            } else {
                // Upload from URL
                $result = $this->uploadMediaFromUrl($mediaUrl, $mediaType, $accessToken);
            }

            if (!$result['success']) {
                return $result;
            }

            $mediaIds[] = $result['media_id'];
        }

        // Twitter allows max 4 images or 1 video per tweet
        if (count($mediaIds) > 4) {
            $mediaIds = array_slice($mediaIds, 0, 4);
            $this->logWarning('Truncated media to 4 items (Twitter limit)');
        }

        return [
            'success' => true,
            'media_ids' => $mediaIds,
        ];
    }

    /**
     * Upload local media file to Twitter.
     */
    protected function uploadLocalMedia(string $filePath, string $mediaType, string $accessToken): array
    {
        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath) ?: ($mediaType === 'video' ? 'video/mp4' : 'image/jpeg');

        // Validate file size
        $maxSize = $this->getMaxSizeForType($mimeType);
        if ($fileSize > $maxSize) {
            return [
                'success' => false,
                'error' => "File size (" . round($fileSize / 1024 / 1024, 2) . "MB) exceeds Twitter's limit (" . round($maxSize / 1024 / 1024) . "MB)",
            ];
        }

        // For videos or large files, use chunked upload
        if ($mediaType === 'video' || $fileSize > 5 * 1024 * 1024) {
            return $this->chunkedUpload($filePath, $mimeType, $accessToken);
        }

        // Simple upload for small images
        return $this->simpleUpload($filePath, $accessToken);
    }

    /**
     * Simple media upload for small files.
     */
    protected function simpleUpload(string $filePath, string $accessToken): array
    {
        $fileContent = file_get_contents($filePath);
        $base64Content = base64_encode($fileContent);

        $response = Http::timeout(60)
            ->withToken($accessToken)
            ->asForm()
            ->post(self::API_V1_BASE . '/media/upload.json', [
                'media_data' => $base64Content,
            ]);

        $jsonBody = $response->json();

        if (!$response->successful()) {
            $this->logError('Twitter simple upload failed', [
                'status' => $response->status(),
                'response' => $jsonBody,
            ]);
            return [
                'success' => false,
                'error' => $this->extractErrorMessage($jsonBody, $response->status()),
            ];
        }

        $mediaId = $jsonBody['media_id_string'] ?? null;
        if (!$mediaId) {
            return [
                'success' => false,
                'error' => 'Media uploaded but no ID returned',
            ];
        }

        return [
            'success' => true,
            'media_id' => $mediaId,
        ];
    }

    /**
     * Chunked media upload for large files and videos.
     */
    protected function chunkedUpload(string $filePath, string $mimeType, string $accessToken): array
    {
        $fileSize = filesize($filePath);
        $mediaCategory = $this->getMediaCategory($mimeType);

        $this->logInfo('Starting chunked upload', [
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'media_category' => $mediaCategory,
        ]);

        // Step 1: INIT
        $initResponse = Http::timeout(30)
            ->withToken($accessToken)
            ->asForm()
            ->post(self::API_V1_BASE . '/media/upload.json', [
                'command' => 'INIT',
                'total_bytes' => $fileSize,
                'media_type' => $mimeType,
                'media_category' => $mediaCategory,
            ]);

        if (!$initResponse->successful()) {
            return [
                'success' => false,
                'error' => 'Failed to initialize upload: ' . $this->extractErrorMessage($initResponse->json(), $initResponse->status()),
            ];
        }

        $mediaId = $initResponse->json('media_id_string');
        if (!$mediaId) {
            return [
                'success' => false,
                'error' => 'INIT returned no media_id',
            ];
        }

        // Step 2: APPEND (chunked upload)
        $chunkSize = 4 * 1024 * 1024; // 4MB chunks
        $file = fopen($filePath, 'rb');
        $segmentIndex = 0;

        while (!feof($file)) {
            $chunk = fread($file, $chunkSize);
            $base64Chunk = base64_encode($chunk);

            $appendResponse = Http::timeout(120)
                ->withToken($accessToken)
                ->asForm()
                ->post(self::API_V1_BASE . '/media/upload.json', [
                    'command' => 'APPEND',
                    'media_id' => $mediaId,
                    'media_data' => $base64Chunk,
                    'segment_index' => $segmentIndex,
                ]);

            if (!$appendResponse->successful()) {
                fclose($file);
                return [
                    'success' => false,
                    'error' => "Failed to upload chunk {$segmentIndex}: " . $this->extractErrorMessage($appendResponse->json(), $appendResponse->status()),
                ];
            }

            $segmentIndex++;
        }

        fclose($file);

        // Step 3: FINALIZE
        $finalizeResponse = Http::timeout(60)
            ->withToken($accessToken)
            ->asForm()
            ->post(self::API_V1_BASE . '/media/upload.json', [
                'command' => 'FINALIZE',
                'media_id' => $mediaId,
            ]);

        if (!$finalizeResponse->successful()) {
            return [
                'success' => false,
                'error' => 'Failed to finalize upload: ' . $this->extractErrorMessage($finalizeResponse->json(), $finalizeResponse->status()),
            ];
        }

        $finalizeData = $finalizeResponse->json();

        // Step 4: Check processing status for videos
        if (isset($finalizeData['processing_info'])) {
            $statusResult = $this->waitForMediaProcessing($mediaId, $accessToken);
            if (!$statusResult['success']) {
                return $statusResult;
            }
        }

        return [
            'success' => true,
            'media_id' => $mediaId,
        ];
    }

    /**
     * Wait for media processing to complete.
     */
    protected function waitForMediaProcessing(string $mediaId, string $accessToken, int $maxWait = 180): array
    {
        $startTime = time();

        while ((time() - $startTime) < $maxWait) {
            $response = Http::timeout(30)
                ->withToken($accessToken)
                ->get(self::API_V1_BASE . '/media/upload.json', [
                    'command' => 'STATUS',
                    'media_id' => $mediaId,
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to check media status',
                ];
            }

            $data = $response->json();
            $processingInfo = $data['processing_info'] ?? null;

            if (!$processingInfo) {
                return ['success' => true];
            }

            $state = $processingInfo['state'] ?? 'unknown';

            if ($state === 'succeeded') {
                return ['success' => true];
            }

            if ($state === 'failed') {
                $error = $processingInfo['error']['message'] ?? 'Media processing failed';
                return [
                    'success' => false,
                    'error' => $error,
                ];
            }

            // Wait before checking again
            $checkAfterSecs = $processingInfo['check_after_secs'] ?? 5;
            sleep(min($checkAfterSecs, 10));
        }

        return [
            'success' => false,
            'error' => 'Media processing timed out',
        ];
    }

    /**
     * Upload media from URL (downloads first, then uploads).
     */
    protected function uploadMediaFromUrl(string $url, string $mediaType, string $accessToken): array
    {
        // Download the media
        $response = Http::timeout(60)->get($url);

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => "Failed to download media from URL: {$url}",
            ];
        }

        $content = $response->body();
        $mimeType = $response->header('Content-Type') ?: ($mediaType === 'video' ? 'video/mp4' : 'image/jpeg');

        // Create temp file
        $tempPath = sys_get_temp_dir() . '/twitter_upload_' . uniqid();
        file_put_contents($tempPath, $content);

        try {
            $result = $this->uploadLocalMedia($tempPath, $mediaType, $accessToken);
        } finally {
            @unlink($tempPath);
        }

        return $result;
    }

    /**
     * Get local file path from media item.
     */
    protected function getLocalFilePath(array $mediaItem): ?string
    {
        // Check for storage_path first
        if (!empty($mediaItem['storage_path'])) {
            $path = Storage::disk('public')->path($mediaItem['storage_path']);
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try to extract path from URL
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
     * Get maximum file size for media type.
     */
    protected function getMaxSizeForType(string $mimeType): int
    {
        if (str_starts_with($mimeType, 'video/')) {
            return self::MAX_VIDEO_SIZE;
        }
        if ($mimeType === 'image/gif') {
            return self::MAX_GIF_SIZE;
        }
        return self::MAX_IMAGE_SIZE;
    }

    /**
     * Get media category for chunked upload.
     */
    protected function getMediaCategory(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'video/')) {
            return 'tweet_video';
        }
        if ($mimeType === 'image/gif') {
            return 'tweet_gif';
        }
        return 'tweet_image';
    }

    /**
     * Extract user-friendly error message from Twitter API response.
     */
    protected function extractErrorMessage(array $response, int $statusCode): string
    {
        // Twitter API v2 error format
        if (isset($response['errors'][0]['message'])) {
            return $response['errors'][0]['message'];
        }

        // Twitter API v2 detail format
        if (isset($response['detail'])) {
            return $response['detail'];
        }

        // Twitter API v1.1 error format
        if (isset($response['error'])) {
            return $response['error'];
        }

        // Default messages based on status code
        return match ($statusCode) {
            401 => 'Your Twitter connection has expired. Please reconnect your Twitter account in Settings > Platform Connections.',
            403 => 'Permission denied. Please check your Twitter app permissions.',
            429 => 'Twitter rate limit reached. Please wait a few minutes before posting again.',
            default => "Twitter API error (HTTP {$statusCode})",
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
