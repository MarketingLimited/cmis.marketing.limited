<?php

namespace App\Integrations\Base;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Base API Client
 *
 * Abstract base class for all platform API clients
 * Provides common HTTP request handling, rate limiting, error handling, and logging
 */
abstract class BaseApiClient
{
    protected string $baseUrl;
    protected string $platform;
    protected ?string $accessToken = null;
    protected array $credentials = [];
    protected array $defaultHeaders = [];
    protected int $timeout = 30;
    protected int $retryAttempts = 3;
    protected int $retryDelay = 1000; // milliseconds

    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
        $this->accessToken = $credentials['access_token'] ?? null;
        $this->initialize();
    }

    /**
     * Initialize client (override in subclasses if needed)
     */
    protected function initialize(): void
    {
        // Can be overridden by platform-specific clients
    }

    /**
     * Make HTTP request to platform API
     *
     * @param string $method HTTP method (get, post, put, delete, patch)
     * @param string $endpoint API endpoint (without base URL)
     * @param array $params Request parameters (query for GET, body for POST/PUT)
     * @param array $headers Additional headers
     * @return array Response data
     * @throws ApiException If request fails
     */
    protected function request(
        string $method,
        string $endpoint,
        array $params = [],
        array $headers = []
    ): array {
        $url = $this->buildUrl($endpoint);
        $headers = array_merge($this->defaultHeaders, $headers);

        Log::debug("API Request: $method $url", [
            'platform' => $this->platform,
            'endpoint' => $endpoint,
            'params_count' => count($params),
        ]);

        try {
            $http = Http::timeout($this->timeout)
                ->withHeaders($headers);

            // Add authentication
            if ($this->accessToken) {
                $http = $http->withToken($this->accessToken);
            }

            // Make request
            $response = $http->$method($url, $params);

            if (!$response->successful()) {
                $this->handleApiError($response);
            }

            Log::debug("API Response: $method $url", [
                'platform' => $this->platform,
                'status' => $response->status(),
            ]);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            if ($e instanceof ApiException) {
                throw $e;
            }

            Log::error("API Request failed: $method $url", [
                'platform' => $this->platform,
                'error' => $e->getMessage(),
            ]);

            throw new ApiException(
                "API request failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Make rate-limited API request
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @param array $headers Additional headers
     * @return array Response data
     */
    protected function rateLimitedRequest(
        string $method,
        string $endpoint,
        array $params = [],
        array $headers = []
    ): array {
        $key = "api_rate_limit:{$this->platform}:" . md5($endpoint);

        return Cache::lock($key, 5)->block(10, function () use ($method, $endpoint, $params, $headers) {
            return $this->request($method, $endpoint, $params, $headers);
        });
    }

    /**
     * Make retryable API request with exponential backoff
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @param array $headers Additional headers
     * @return array Response data
     */
    protected function retryableRequest(
        string $method,
        string $endpoint,
        array $params = [],
        array $headers = []
    ): array {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->retryAttempts) {
            try {
                return $this->request($method, $endpoint, $params, $headers);
            } catch (ApiException $e) {
                $lastException = $e;
                $attempt++;

                // Don't retry client errors (4xx)
                if ($e->getCode() >= 400 && $e->getCode() < 500) {
                    throw $e;
                }

                if ($attempt >= $this->retryAttempts) {
                    break;
                }

                // Exponential backoff
                $delay = $this->retryDelay * (2 ** ($attempt - 1));
                usleep($delay * 1000);

                Log::warning("Retrying API request", [
                    'platform' => $this->platform,
                    'endpoint' => $endpoint,
                    'attempt' => $attempt,
                    'max_attempts' => $this->retryAttempts,
                ]);
            }
        }

        throw $lastException;
    }

    /**
     * Build full URL from endpoint
     *
     * @param string $endpoint Endpoint path
     * @return string Full URL
     */
    protected function buildUrl(string $endpoint): string
    {
        $endpoint = ltrim($endpoint, '/');
        return rtrim($this->baseUrl, '/') . '/' . $endpoint;
    }

    /**
     * Handle API error response
     *
     * @param \Illuminate\Http\Client\Response $response Error response
     * @throws ApiException
     */
    protected function handleApiError($response): void
    {
        $statusCode = $response->status();
        $body = $response->body();

        try {
            $data = $response->json();
            $message = $this->extractErrorMessage($data);
        } catch (\Exception $e) {
            $message = $body;
            $data = [];
        }

        Log::error("API Error Response", [
            'platform' => $this->platform,
            'status' => $statusCode,
            'message' => $message,
            'response' => $data,
        ]);

        throw new ApiException(
            "API Error ($statusCode): $message",
            $statusCode,
            null,
            $data
        );
    }

    /**
     * Extract error message from response data
     *
     * @param array $data Response data
     * @return string Error message
     */
    protected function extractErrorMessage(array $data): string
    {
        // Common error message fields
        return $data['error']['message'] ??
               $data['error_description'] ??
               $data['message'] ??
               $data['error'] ??
               'Unknown API error';
    }

    /**
     * Get platform name
     *
     * @return string Platform name
     */
    public function getPlatform(): string
    {
        return $this->platform;
    }

    /**
     * Set access token
     *
     * @param string $token Access token
     * @return self
     */
    public function setAccessToken(string $token): self
    {
        $this->accessToken = $token;
        return $this;
    }

    /**
     * Get access token
     *
     * @return string|null Access token
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }
}
