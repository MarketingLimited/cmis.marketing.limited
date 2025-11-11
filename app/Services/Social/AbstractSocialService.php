<?php

namespace App\Services\Social;

use App\Models\Integration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractSocialService
{
    protected Integration $integration;
    protected array $config;

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
        $this->config = $this->getConfiguration();
    }

    /**
     * Get platform-specific configuration
     */
    abstract protected function getConfiguration(): array;

    /**
     * Sync account data
     */
    abstract public function syncAccount(): array;

    /**
     * Sync posts with date range
     */
    abstract public function syncPosts($from, $to, $limit = 25): array;

    /**
     * Sync metrics for posts
     */
    abstract public function syncMetrics(array $postIds): array;

    /**
     * Make authenticated API request
     */
    protected function makeRequest(string $method, string $endpoint, array $params = []): array
    {
        try {
            $response = Http::withToken($this->integration->access_token)
                ->timeout(30)
                ->{$method}($endpoint, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("API request failed", [
                'platform' => $this->integration->platform,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [];

        } catch (\Exception $e) {
            Log::error("API request exception", [
                'platform' => $this->integration->platform,
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Check if access token is valid
     */
    public function validateToken(): bool
    {
        if (!$this->integration->access_token) {
            return false;
        }

        if ($this->integration->expires_at && $this->integration->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Refresh access token if needed
     */
    abstract public function refreshToken(): bool;
}
