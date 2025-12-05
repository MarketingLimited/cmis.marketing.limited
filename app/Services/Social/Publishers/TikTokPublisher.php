<?php

namespace App\Services\Social\Publishers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Publisher for TikTok.
 *
 * Uses TikTok Content Posting API v2 for video publishing.
 * Uses FILE_UPLOAD method (direct upload) since PULL_FROM_URL requires domain verification.
 * Note: Unaudited apps post as PRIVATE only. Audited apps can post PUBLIC.
 */
class TikTokPublisher extends AbstractPublisher
{
    protected const API_BASE = 'https://open.tiktokapis.com';
    protected const API_VERSION = 'v2';

    /**
     * Map of TikTok API error codes to user-friendly messages.
     */
    protected const ERROR_MESSAGES = [
        'unaudited_client_can_only_post_to_private_accounts' => 'tiktok_unaudited_app',
        'url_ownership_unverified' => 'tiktok_url_unverified',
        'access_token_invalid' => 'tiktok_token_invalid',
        'token_expired' => 'tiktok_token_expired',
        'scope_not_authorized' => 'tiktok_scope_missing',
        'spam_risk_too_many_posts' => 'tiktok_rate_limit',
        'spam_risk_user_banned_from_posting' => 'tiktok_user_banned',
        'video_file_too_large' => 'tiktok_file_too_large',
        'video_duration_too_long' => 'tiktok_duration_too_long',
        'video_duration_too_short' => 'tiktok_duration_too_short',
        'invalid_video_format' => 'tiktok_invalid_format',
        'privacy_level_option_not_found' => 'tiktok_privacy_error',
    ];

    /**
     * Get user-friendly error message for TikTok API error code.
     */
    protected function getUserFriendlyError(string $errorCode, string $defaultMessage): string
    {
        $translationKey = self::ERROR_MESSAGES[$errorCode] ?? null;

        if ($translationKey) {
            // Try to get translated message
            $translated = __("publish.errors.{$translationKey}");
            if ($translated !== "publish.errors.{$translationKey}") {
                return $translated;
            }
        }

        // Fallback to detailed error messages
        return match ($errorCode) {
            'unaudited_client_can_only_post_to_private_accounts' =>
                'Your TikTok app is not yet approved for public posting. ' .
                'Please add your TikTok account as a Test User in the TikTok Developer Console, ' .
                'or submit your app for audit approval. ' .
                'See: developers.tiktok.com/doc/content-sharing-guidelines',

            'url_ownership_unverified' =>
                'TikTok requires domain verification for URL-based uploads. ' .
                'The video is being uploaded directly instead.',

            'access_token_invalid', 'token_expired' =>
                'Your TikTok connection has expired. Please reconnect your TikTok account in Settings > Platform Connections.',

            'scope_not_authorized' =>
                'Missing required TikTok permissions. Please reconnect your TikTok account and approve all requested permissions.',

            'spam_risk_too_many_posts' =>
                'TikTok rate limit reached. Please wait a few minutes before posting again.',

            'spam_risk_user_banned_from_posting' =>
                'Your TikTok account has been temporarily restricted from posting. Please check your TikTok app for details.',

            'video_file_too_large' =>
                'Video file is too large for TikTok. Maximum size is 4GB.',

            'video_duration_too_long' =>
                'Video is too long for TikTok. Maximum duration is 10 minutes.',

            'video_duration_too_short' =>
                'Video is too short for TikTok. Minimum duration is 3 seconds.',

            'invalid_video_format' =>
                'Invalid video format. TikTok requires MP4 or MOV format with H.264 codec.',

            default => $defaultMessage,
        };
    }

    /**
     * Publish content to TikTok.
     */
    public function publish(string $content, array $media, array $options = []): array
    {
        $this->logInfo('TikTokPublisher::publish called', [
            'content_length' => strlen($content),
            'media_count' => count($media),
            'options' => array_keys($options),
        ]);

        // TikTok requires video
        if (empty($media)) {
            return $this->failure('TikTok requires at least one video');
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return $this->failure('No TikTok access token available. Please reconnect your TikTok account.');
        }

        try {
            // Get the first video media item
            $videoMedia = $this->findVideoMedia($media);
            if (!$videoMedia) {
                return $this->failure('TikTok requires a video. No video found in media attachments.');
            }

            // Get the local file path from URL
            $videoPath = $this->getLocalFilePath($videoMedia);
            if (!$videoPath || !file_exists($videoPath)) {
                return $this->failure('Video file not found on server. Path: ' . ($videoPath ?? 'null'));
            }

            // Validate video specs and auto-process if needed
            $validation = $this->validateVideoSpecs($videoPath);

            // If video doesn't meet requirements but can be fixed, process it
            if (!$validation['valid'] && $validation['can_fix'] ?? false) {
                $this->logInfo('Video needs processing for TikTok, auto-processing', [
                    'original_path' => $videoPath,
                    'issue' => $validation['error'],
                ]);

                try {
                    $videoProcessingService = app(\App\Services\Media\VideoProcessingService::class);
                    $orgId = $this->connection->org_id;
                    $processed = $videoProcessingService->processVideoForPlatform($videoPath, $orgId, 'tiktok');

                    // Use the processed video path
                    $videoPath = $processed['processed_path']
                        ? \Illuminate\Support\Facades\Storage::disk('public')->path($processed['processed_path'])
                        : $videoPath;

                    $this->logInfo('Video processed successfully for TikTok', [
                        'processed_path' => $videoPath,
                        'original_size' => $processed['original_size'] ?? 'unknown',
                        'final_size' => "{$processed['width']}x{$processed['height']}",
                    ]);

                    // Re-validate after processing
                    $validation = $this->validateVideoSpecs($videoPath);
                } catch (\Exception $e) {
                    $this->logError('Failed to auto-process video for TikTok', [
                        'error' => $e->getMessage(),
                    ]);
                    return $this->failure('Video does not meet TikTok requirements and auto-processing failed: ' . $validation['error']);
                }
            }

            if (!$validation['valid']) {
                return $this->failure($validation['error']);
            }

            $this->logInfo('Publishing video to TikTok via FILE_UPLOAD', [
                'video_path' => $videoPath,
                'file_size' => filesize($videoPath),
                'content_preview' => substr($content, 0, 100),
                'video_specs' => $validation['specs'],
            ]);

            // Get privacy level from options or use default
            // Note: Unaudited apps can only post PRIVATE
            $privacyLevel = $options['privacy_level'] ?? 'SELF_ONLY';

            // Step 1: Initialize video upload
            $initResult = $this->initFileUpload($videoPath, $content, $privacyLevel, $options);

            if (!$initResult['success']) {
                return $this->failure($initResult['error'] ?? 'Failed to initialize TikTok video upload');
            }

            $publishId = $initResult['publish_id'];
            $uploadUrl = $initResult['upload_url'];

            $this->logInfo('TikTok upload initialized', [
                'publish_id' => $publishId,
                'upload_url' => substr($uploadUrl, 0, 100) . '...',
            ]);

            // Step 2: Upload the video file
            $uploadResult = $this->uploadVideoFile($videoPath, $uploadUrl);

            if (!$uploadResult['success']) {
                return $this->failure($uploadResult['error'] ?? 'Failed to upload video to TikTok');
            }

            $this->logInfo('TikTok video uploaded successfully', [
                'publish_id' => $publishId,
            ]);

            // TikTok processes videos asynchronously
            // Return success with publish_id - the actual video URL will be available later
            return $this->success(
                $publishId,
                null // TikTok doesn't provide permalink immediately
            );

        } catch (\Exception $e) {
            $this->logError('TikTok publishing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->failure('TikTok publishing failed: ' . $e->getMessage());
        }
    }

    /**
     * Find video media from the media array.
     */
    protected function findVideoMedia(array $media): ?array
    {
        foreach ($media as $item) {
            if (($item['type'] ?? 'image') === 'video') {
                return $item;
            }
        }
        return null;
    }

    /**
     * Get local file path from media item.
     */
    protected function getLocalFilePath(array $mediaItem): ?string
    {
        // Check for storage_path first (from MediaAsset)
        if (!empty($mediaItem['storage_path'])) {
            $path = Storage::disk('public')->path($mediaItem['storage_path']);
            if (file_exists($path)) {
                return $path;
            }
        }

        // Check for processed_path (H.264 converted video)
        if (!empty($mediaItem['processed_path'])) {
            $path = Storage::disk('public')->path($mediaItem['processed_path']);
            if (file_exists($path)) {
                return $path;
            }
        }

        // Try to extract path from URL
        $url = $mediaItem['processed_url'] ?? $mediaItem['url'] ?? $mediaItem['preview_url'] ?? null;
        if ($url) {
            // Handle /storage/ URLs
            if (preg_match('#/storage/(.+)$#', $url, $matches)) {
                $path = Storage::disk('public')->path($matches[1]);
                if (file_exists($path)) {
                    return $path;
                }
            }

            // Handle full public path
            $publicPath = public_path('storage/' . basename(parse_url($url, PHP_URL_PATH)));
            if (file_exists($publicPath)) {
                return $publicPath;
            }
        }

        // Check for path directly
        if (!empty($mediaItem['path'])) {
            $path = Storage::disk('public')->path($mediaItem['path']);
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Initialize video upload to TikTok using FILE_UPLOAD method.
     */
    protected function initFileUpload(string $filePath, string $title, string $privacyLevel, array $options = []): array
    {
        $fileSize = filesize($filePath);

        $postInfo = [
            'title' => $title,
            'privacy_level' => $privacyLevel,
            'disable_comment' => $options['disable_comment'] ?? false,
            'disable_duet' => $options['disable_duet'] ?? false,
            'disable_stitch' => $options['disable_stitch'] ?? false,
        ];

        $requestBody = [
            'post_info' => $postInfo,
            'source_info' => [
                'source' => 'FILE_UPLOAD',
                'video_size' => $fileSize,
                'chunk_size' => $fileSize, // Single chunk upload for files < 64MB
                'total_chunk_count' => 1,
            ],
        ];

        $this->logInfo('TikTok FILE_UPLOAD init request', [
            'url' => self::API_BASE . '/' . self::API_VERSION . '/post/publish/video/init/',
            'post_info' => $postInfo,
            'video_size' => $fileSize,
        ]);

        $result = $this->httpPostWithAuth(
            self::API_BASE . '/' . self::API_VERSION . '/post/publish/video/init/',
            $requestBody
        );

        if (!$result['success']) {
            $this->logError('TikTok init failed', [
                'error' => $result['error'] ?? 'Unknown error',
                'response' => $result['response'] ?? null,
            ]);
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Failed to initialize video upload',
            ];
        }

        $data = $result['data'] ?? [];
        $publishId = $data['publish_id'] ?? null;
        $uploadUrl = $data['upload_url'] ?? null;

        if (!$publishId || !$uploadUrl) {
            return [
                'success' => false,
                'error' => 'TikTok did not return publish_id or upload_url',
            ];
        }

        return [
            'success' => true,
            'publish_id' => $publishId,
            'upload_url' => $uploadUrl,
            'data' => $data,
        ];
    }

    /**
     * Upload the video file to TikTok's upload URL.
     */
    protected function uploadVideoFile(string $filePath, string $uploadUrl): array
    {
        try {
            $videoContent = file_get_contents($filePath);
            $fileSize = strlen($videoContent);

            $this->logInfo('Uploading video to TikTok', [
                'file_size' => $fileSize,
                'upload_url' => substr($uploadUrl, 0, 80) . '...',
            ]);

            $response = Http::timeout(300) // 5 minute timeout for large files
                ->withHeaders([
                    'Content-Type' => 'video/mp4',
                    'Content-Length' => $fileSize,
                    'Content-Range' => "bytes 0-" . ($fileSize - 1) . "/{$fileSize}",
                ])
                ->withBody($videoContent, 'video/mp4')
                ->put($uploadUrl);

            Log::debug('[TikTok] Upload Response', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Upload failed with status ' . $response->status() . ': ' . $response->body(),
                ];
            }

            return [
                'success' => true,
            ];

        } catch (\Exception $e) {
            Log::error('[TikTok] Upload Exception', [
                'exception' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check the status of a TikTok video publish.
     */
    public function checkPublishStatus(string $publishId): array
    {
        $result = $this->httpPostWithAuth(
            self::API_BASE . '/' . self::API_VERSION . '/post/publish/status/fetch/',
            ['publish_id' => $publishId]
        );

        if (!$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Failed to check status',
            ];
        }

        return [
            'success' => true,
            'status' => $result['data']['status'] ?? 'UNKNOWN',
            'data' => $result['data'] ?? [],
        ];
    }

    /**
     * Make an authenticated POST request to TikTok API.
     */
    protected function httpPostWithAuth(string $url, array $data): array
    {
        $accessToken = $this->getAccessToken();

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json; charset=UTF-8',
                ])
                ->post($url, $data);

            $jsonBody = $response->json();

            Log::debug('[TikTok] API Response', [
                'url' => $url,
                'status' => $response->status(),
                'body_preview' => substr($response->body(), 0, 500),
            ]);

            // TikTok uses error.code in response body
            if (isset($jsonBody['error']['code']) && $jsonBody['error']['code'] !== 'ok') {
                $errorCode = $jsonBody['error']['code'];
                $defaultMessage = $jsonBody['error']['message'] ?? $errorCode;
                $userFriendlyMessage = $this->getUserFriendlyError($errorCode, $defaultMessage);

                return [
                    'success' => false,
                    'error' => $userFriendlyMessage,
                    'error_code' => $errorCode,
                    'response' => $jsonBody,
                ];
            }

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => $jsonBody['error']['message'] ?? 'Request failed with status ' . $response->status(),
                    'response' => $jsonBody,
                    'status' => $response->status(),
                ];
            }

            return [
                'success' => true,
                'data' => $jsonBody['data'] ?? $jsonBody,
                'status' => $response->status(),
            ];

        } catch (\Exception $e) {
            Log::error('[TikTok] HTTP Exception', [
                'url' => $url,
                'exception' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ];
        }
    }

    /**
     * Validate video specifications before upload.
     *
     * TikTok Requirements:
     * - Resolution: Minimum 540x960 (9:16 recommended)
     * - Duration: 3 seconds to 10 minutes
     * - File size: Max 4GB
     * - Format: MP4 or MOV with H.264 codec
     * - Frame rate: 24-60 FPS
     *
     * @param string $videoPath Path to the video file
     * @return array ['valid' => bool, 'error' => string|null, 'specs' => array]
     */
    protected function validateVideoSpecs(string $videoPath): array
    {
        $specs = [];

        // Check file size first (max 4GB)
        $fileSize = filesize($videoPath);
        $maxSize = 4 * 1024 * 1024 * 1024; // 4GB
        $specs['file_size'] = $fileSize;
        $specs['file_size_mb'] = round($fileSize / 1024 / 1024, 2);

        if ($fileSize > $maxSize) {
            return [
                'valid' => false,
                'error' => 'Video file is too large for TikTok. Maximum size is 4GB. Your file is ' . round($fileSize / 1024 / 1024, 2) . 'MB.',
                'specs' => $specs,
            ];
        }

        // Use ffprobe to get video specs
        $ffprobePath = 'ffprobe';
        $cmd = "{$ffprobePath} -v quiet -print_format json -show_format -show_streams " . escapeshellarg($videoPath) . " 2>/dev/null";
        $output = shell_exec($cmd);

        if (!$output) {
            $this->logWarning('Could not analyze video with ffprobe, skipping validation', [
                'video_path' => $videoPath,
            ]);
            return [
                'valid' => true,
                'error' => null,
                'specs' => $specs,
                'warning' => 'Could not analyze video specs - proceeding with upload',
            ];
        }

        $probeData = json_decode($output, true);

        if (!$probeData || empty($probeData['streams'])) {
            return [
                'valid' => true,
                'error' => null,
                'specs' => $specs,
                'warning' => 'Could not parse video specs - proceeding with upload',
            ];
        }

        // Find video stream
        $videoStream = null;
        foreach ($probeData['streams'] as $stream) {
            if ($stream['codec_type'] === 'video') {
                $videoStream = $stream;
                break;
            }
        }

        if (!$videoStream) {
            return [
                'valid' => false,
                'error' => 'No video stream found in file. Please ensure the file is a valid video.',
                'specs' => $specs,
            ];
        }

        // Extract specs
        $width = $videoStream['width'] ?? 0;
        $height = $videoStream['height'] ?? 0;
        $codec = $videoStream['codec_name'] ?? 'unknown';
        $duration = (float) ($probeData['format']['duration'] ?? 0);

        $specs['width'] = $width;
        $specs['height'] = $height;
        $specs['codec'] = $codec;
        $specs['duration'] = $duration;

        // Validate resolution (minimum 540x960 for TikTok)
        // TikTok requires either width >= 540 OR height >= 540
        $minDimension = min($width, $height);
        if ($minDimension < 540) {
            return [
                'valid' => false,
                'can_fix' => true, // Can be fixed by upscaling
                'error' => "Video resolution ({$width}x{$height}) is too small for TikTok. Minimum dimension should be 540 pixels.",
                'specs' => $specs,
            ];
        }

        // Validate duration (3 seconds to 10 minutes)
        if ($duration < 3) {
            return [
                'valid' => false,
                'error' => "Video duration ({$duration}s) is too short for TikTok. Minimum duration is 3 seconds.",
                'specs' => $specs,
            ];
        }

        if ($duration > 600) { // 10 minutes
            return [
                'valid' => false,
                'error' => "Video duration (" . round($duration / 60, 1) . " minutes) exceeds TikTok's 10 minute limit.",
                'specs' => $specs,
            ];
        }

        // Validate codec (H.264 recommended)
        $validCodecs = ['h264', 'hevc', 'h265'];
        if (!in_array(strtolower($codec), $validCodecs)) {
            $this->logWarning('Video codec may not be optimal for TikTok', [
                'codec' => $codec,
                'recommended' => 'h264',
            ]);
            // Don't fail, just warn - TikTok might still accept it
        }

        return [
            'valid' => true,
            'error' => null,
            'specs' => $specs,
        ];
    }
}
