<?php

namespace App\Services\Social\Publishers;

use App\Models\Platform\PlatformConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Abstract base class for platform publishers.
 */
abstract class AbstractPublisher
{
    protected ?PlatformConnection $connection;
    protected string $platform;

    public function __construct(?PlatformConnection $connection, string $platform)
    {
        $this->connection = $connection;
        $this->platform = $platform;
    }

    /**
     * Check if an active connection exists.
     */
    public function hasActiveConnection(): bool
    {
        return $this->connection !== null;
    }

    /**
     * Get the access token.
     */
    protected function getAccessToken(): ?string
    {
        return $this->connection?->access_token;
    }

    /**
     * Get account metadata.
     */
    protected function getMetadata(): array
    {
        return $this->connection?->account_metadata ?? [];
    }

    /**
     * Get selected assets from metadata.
     */
    protected function getSelectedAssets(): array
    {
        return $this->getMetadata()['selected_assets'] ?? [];
    }

    /**
     * Publish content to the platform.
     *
     * @param string $content Text content
     * @param array $media Media attachments
     * @param array $options Platform-specific options
     * @return array Result with success status and message
     */
    abstract public function publish(string $content, array $media, array $options = []): array;

    /**
     * Create a success result.
     */
    protected function success(string $postId, ?string $permalink = null): array
    {
        return [
            'success' => true,
            'post_id' => $postId,
            'permalink' => $permalink,
        ];
    }

    /**
     * Create a failure result.
     */
    protected function failure(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
        ];
    }

    /**
     * Log an error.
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error("[{$this->platform}] {$message}", $context);
    }

    /**
     * Log info.
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info("[{$this->platform}] {$message}", $context);
    }

    /**
     * Make an HTTP request with error handling.
     */
    protected function httpGet(string $url, array $params = [], int $timeout = 30): array
    {
        try {
            $response = Http::timeout($timeout)->get($url, $params);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => $response->json('error.message', 'Request failed'),
                    'response' => $response->json(),
                ];
            }

            return [
                'success' => true,
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Make a POST HTTP request with error handling.
     */
    protected function httpPost(string $url, array $data = [], int $timeout = 60): array
    {
        try {
            Log::debug("[{$this->platform}] httpPost request", [
                'url' => $url,
                'timeout' => $timeout,
            ]);

            $response = Http::timeout($timeout)->post($url, $data);
            $jsonBody = $response->json();

            Log::debug("[{$this->platform}] httpPost response", [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body_preview' => substr($response->body(), 0, 500),
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => $jsonBody['error']['message'] ?? $response->json('error.message', 'Request failed'),
                    'response' => $jsonBody,
                    'status' => $response->status(),
                ];
            }

            return [
                'success' => true,
                'data' => $jsonBody,
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error("[{$this->platform}] httpPost exception", [
                'url' => $url,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ];
        }
    }
}
