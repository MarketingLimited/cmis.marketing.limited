<?php

namespace App\Services\AdPlatforms;

use App\Models\Core\Integration;
use App\Services\AdPlatforms\Contracts\AdPlatformInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Abstract base class for Ad Platform Services
 *
 * Provides common functionality for all platform implementations:
 * - HTTP request handling with retry logic
 * - Rate limiting
 * - Error handling
 * - Response caching
 *
 * @package App\Services\AdPlatforms
 */
abstract class AbstractAdPlatform implements AdPlatformInterface
{
    protected Integration $integration;
    protected array $config;
    protected string $platformName;

    /**
     * Maximum number of retries for failed requests
     */
    protected int $maxRetries = 3;

    /**
     * Delay between retries in milliseconds
     */
    protected int $retryDelay = 1000;

    /**
     * Rate limit: requests per minute
     */
    protected int $rateLimit = 200;

    /**
     * Initialize platform service
     */
    public function __construct(Integration $integration)
    {
        $this->integration = $integration;
        $this->config = $this->getConfig();
        $this->platformName = $this->getPlatformName();
    }

    /**
     * Get platform-specific configuration
     *
     * @return array
     */
    abstract protected function getConfig(): array;

    /**
     * Get platform name
     *
     * @return string
     */
    abstract protected function getPlatformName(): string;

    /**
     * Make HTTP request with retry logic and rate limiting
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $url Full URL to request
     * @param array $data Request data
     * @param array $headers Additional headers
     * @return array Response data
     * @throws \Exception
     */
    protected function makeRequest(
        string $method,
        string $url,
        array $data = [],
        array $headers = []
    ): array {
        $attempt = 0;
        $lastException = null;

        // Check rate limit
        $this->checkRateLimit();

        while ($attempt < $this->maxRetries) {
            try {
                $response = $this->executeRequest($method, $url, $data, $headers);

                if ($response->successful()) {
                    // Record successful request
                    $this->recordRequest();

                    return $response->json() ?? [];
                }

                // Handle rate limiting from platform
                if ($response->status() === 429) {
                    $retryAfter = $response->header('Retry-After', $this->retryDelay / 1000);
                    sleep((int) $retryAfter);
                    $attempt++;
                    continue;
                }

                // Handle other errors
                if ($response->clientError() || $response->serverError()) {
                    throw new \Exception(
                        "HTTP {$response->status()}: " . ($response->body() ?? 'Unknown error')
                    );
                }
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt < $this->maxRetries) {
                    // Exponential backoff
                    usleep($this->retryDelay * pow(2, $attempt - 1) * 1000);
                    continue;
                }

                break;
            }
        }

        // All retries failed
        Log::error("Platform API request failed after {$this->maxRetries} attempts", [
            'platform' => $this->platformName,
            'method' => $method,
            'url' => $url,
            'error' => $lastException?->getMessage(),
        ]);

        throw new \Exception(
            "Failed to connect to {$this->platformName} API: " . ($lastException?->getMessage() ?? 'Unknown error')
        );
    }

    /**
     * Execute HTTP request
     */
    protected function executeRequest(
        string $method,
        string $url,
        array $data,
        array $headers
    ): \Illuminate\Http\Client\Response {
        $request = Http::withHeaders(array_merge(
            $this->getDefaultHeaders(),
            $headers
        ));

        // Add timeout
        $request = $request->timeout(30);

        return match (strtoupper($method)) {
            'GET' => $request->get($url, $data),
            'POST' => $request->post($url, $data),
            'PUT' => $request->put($url, $data),
            'DELETE' => $request->delete($url, $data),
            'PATCH' => $request->patch($url, $data),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }

    /**
     * Get default headers for requests
     */
    protected function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'CMIS-AdManager/1.0',
        ];
    }

    /**
     * Check and enforce rate limit
     */
    protected function checkRateLimit(): void
    {
        $key = "rate_limit:{$this->platformName}:{$this->integration->integration_id}";
        $requests = Cache::get($key, 0);

        if ($requests >= $this->rateLimit) {
            $ttl = 60 - now()->second;
            Log::warning("Rate limit reached for {$this->platformName}, waiting {$ttl}s");
            sleep($ttl);
            Cache::forget($key);
        }
    }

    /**
     * Record a successful request for rate limiting
     */
    protected function recordRequest(): void
    {
        $key = "rate_limit:{$this->platformName}:{$this->integration->integration_id}";
        Cache::increment($key);
        Cache::put($key, Cache::get($key, 1), now()->addMinutes(1));
    }

    /**
     * Test connection to platform
     */
    public function testConnection(): array
    {
        try {
            $result = $this->syncAccount();
            return [
                'success' => $result['success'] ?? false,
                'message' => $result['success'] ? 'Connection successful' : 'Connection failed',
                'platform' => $this->platformName,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'platform' => $this->platformName,
            ];
        }
    }

    /**
     * Default validation - can be overridden by specific platforms
     */
    public function validateCampaignData(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Campaign name is required';
        }

        if (empty($data['objective'])) {
            $errors[] = 'Campaign objective is required';
        }

        if (!in_array($data['objective'] ?? '', $this->getAvailableObjectives())) {
            $errors[] = 'Invalid campaign objective for ' . $this->platformName;
        }

        if (empty($data['budget']) && empty($data['daily_budget']) && empty($data['lifetime_budget'])) {
            $errors[] = 'Budget is required';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Map internal status to platform-specific status
     */
    protected function mapStatus(string $internalStatus): string
    {
        // Override in specific platform implementations
        return $internalStatus;
    }

    /**
     * Map platform-specific status to internal status
     */
    protected function mapStatusFromPlatform(string $platformStatus): string
    {
        // Override in specific platform implementations
        return strtolower($platformStatus);
    }
}
