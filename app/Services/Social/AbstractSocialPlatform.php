<?php

namespace App\Services\Social;

use App\Models\Social\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Abstract base class for all social media platform publishing services
 *
 * Provides common functionality and enforces consistent interface
 * across all platform integrations
 */
abstract class AbstractSocialPlatform
{
    protected string $platform;
    protected array $config;
    protected ?string $accessToken = null;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->platform = $this->getPlatformName();
    }

    /**
     * Get platform name identifier
     */
    abstract protected function getPlatformName(): string;

    /**
     * Publish content to the platform immediately
     *
     * @param array $content Content data including text, media, metadata
     * @return array Response with external_id, url, and platform-specific data
     * @throws \Exception on failure
     */
    abstract public function publish(array $content): array;

    /**
     * Schedule content for future publication
     *
     * @param array $content Content data
     * @param \DateTime $scheduledTime When to publish
     * @return array Response with scheduling confirmation
     * @throws \Exception on failure
     */
    abstract public function schedule(array $content, \DateTime $scheduledTime): array;

    /**
     * Validate content meets platform requirements
     *
     * @param array $content Content to validate
     * @return bool True if valid
     * @throws \InvalidArgumentException with specific validation errors
     */
    abstract public function validateContent(array $content): bool;

    /**
     * Get supported post types for this platform
     *
     * @return array Array of post type definitions
     */
    abstract public function getPostTypes(): array;

    /**
     * Get media requirements and limitations
     *
     * @return array Media specs (formats, sizes, dimensions, duration)
     */
    abstract public function getMediaRequirements(): array;

    /**
     * Get character/text length limits
     *
     * @return array Min/max character limits by post type
     */
    abstract public function getTextLimits(): array;

    /**
     * Set access token for API calls
     */
    public function setAccessToken(string $token): self
    {
        $this->accessToken = $token;
        return $this;
    }

    /**
     * Get access token
     */
    protected function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    /**
     * Upload media file to platform
     *
     * @param string $filePath Local file path or URL
     * @param string $mediaType Type: image, video, gif
     * @return string Media ID or URL on platform
     */
    abstract protected function uploadMedia(string $filePath, string $mediaType): string;

    /**
     * Delete a published post
     *
     * @param string $externalPostId Platform's post ID
     * @return bool Success status
     */
    public function delete(string $externalPostId): bool
    {
        // Default implementation - override if platform supports deletion
        Log::warning("Delete not implemented for {$this->platform}");
        return false;
    }

    /**
     * Update/edit a published post
     *
     * @param string $externalPostId Platform's post ID
     * @param array $content New content
     * @return array Updated post data
     */
    public function update(string $externalPostId, array $content): array
    {
        // Default implementation - override if platform supports editing
        throw new \Exception("Update not supported for {$this->platform}");
    }

    /**
     * Fetch post analytics/insights
     *
     * @param string $externalPostId Platform's post ID
     * @return array Analytics data
     */
    public function getAnalytics(string $externalPostId): array
    {
        // Default implementation - override to fetch platform analytics
        return [];
    }

    /**
     * Log platform-specific operations
     */
    protected function logOperation(string $operation, array $context = []): void
    {
        Log::info("Social Platform Operation", [
            'platform' => $this->platform,
            'operation' => $operation,
            'context' => $context,
        ]);
    }

    /**
     * Log platform-specific errors
     */
    protected function logError(string $operation, \Exception $e, array $context = []): void
    {
        Log::error("Social Platform Error", [
            'platform' => $this->platform,
            'operation' => $operation,
            'error' => $e->getMessage(),
            'context' => $context,
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Make HTTP request with common error handling
     */
    protected function makeRequest(string $method, string $url, array $data = [], array $headers = []): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(array_merge([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ], $headers))
                ->$method($url, $data);

            if (!$response->successful()) {
                throw new \Exception(
                    "HTTP {$response->status()}: " . ($response->json('error.message') ?? $response->body())
                );
            }

            return $response->json() ?? [];
        } catch (\Exception $e) {
            $this->logError('http_request', $e, [
                'method' => $method,
                'url' => $url,
            ]);
            throw $e;
        }
    }

    /**
     * Validate required fields in content
     */
    protected function validateRequiredFields(array $content, array $requiredFields): void
    {
        $missing = [];
        foreach ($requiredFields as $field) {
            if (!isset($content[$field]) || empty($content[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                "Missing required fields for {$this->platform}: " . implode(', ', $missing)
            );
        }
    }

    /**
     * Validate text length
     */
    protected function validateTextLength(string $text, int $maxLength, string $fieldName = 'text'): void
    {
        $length = mb_strlen($text);
        if ($length > $maxLength) {
            throw new \InvalidArgumentException(
                "{$fieldName} exceeds maximum length of {$maxLength} characters (got {$length})"
            );
        }
    }

    /**
     * Validate media file
     */
    protected function validateMediaFile(string $filePath, array $allowedTypes, int $maxSizeMB): void
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Media file not found: {$filePath}");
        }

        $fileSize = filesize($filePath) / (1024 * 1024); // Convert to MB
        if ($fileSize > $maxSizeMB) {
            throw new \InvalidArgumentException(
                "Media file too large: {$fileSize}MB (max: {$maxSizeMB}MB)"
            );
        }

        $mimeType = mime_content_type($filePath);
        if (!in_array($mimeType, $allowedTypes)) {
            throw new \InvalidArgumentException(
                "Invalid media type: {$mimeType} (allowed: " . implode(', ', $allowedTypes) . ")"
            );
        }
    }
}
