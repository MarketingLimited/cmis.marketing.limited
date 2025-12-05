<?php

namespace App\Services\Connectors;

use App\Services\Connectors\Contracts\ConnectorInterface;
use App\Models\Core\Integration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Abstract base class for all platform connectors.
 * Provides common functionality like rate limiting, token refresh, error handling, logging.
 */
abstract class AbstractConnector implements ConnectorInterface
{
    /**
     * Platform identifier (e.g., 'meta', 'google', 'tiktok')
     * @var string
     */
    protected string $platform;

    /**
     * API base URL for the platform
     * @var string
     */
    protected string $baseUrl;

    /**
     * API version
     * @var string
     */
    protected string $apiVersion;

    /**
     * Rate limit configuration
     * @var array
     */
    protected array $rateLimit = [
        'max_requests' => 200,
        'per_seconds' => 3600, // per hour
    ];

    /**
     * Check if rate limit allows this request
     *
     * @param Integration $integration
     * @param string $endpoint
     * @return bool
     */
    protected function checkRateLimit(Integration $integration, string $endpoint): bool
    {
        $key = "rate_limit:{$this->platform}:{$integration->integration_id}:{$endpoint}";
        $count = Cache::get($key, 0);

        if ($count >= $this->rateLimit['max_requests']) {
            Log::warning("Rate limit exceeded for {$this->platform} - {$endpoint}", [
                'integration_id' => $integration->integration_id,
                'count' => $count,
            ]);
            return false;
        }

        Cache::put($key, $count + 1, $this->rateLimit['per_seconds']);
        return true;
    }

    /**
     * Make an authenticated API request
     *
     * @param Integration $integration
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array
     * @throws \Exception
     */
    protected function makeRequest(Integration $integration, string $method, string $endpoint, array $data = []): array
    {
        // Check rate limit
        if (!$this->checkRateLimit($integration, $endpoint)) {
            throw new \Exception('Rate limit exceeded. Please try again later.');
        }

        // Check if token needs refresh
        if ($this->shouldRefreshToken($integration)) {
            $integration = $this->refreshToken($integration);
        }

        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $startTime = microtime(true);

        try {
            $response = Http::withToken($integration->access_token)
                ->acceptJson()
                ->{strtolower($method)}($url, $data);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $httpStatus = $response->status();

            if ($response->failed()) {
                $this->logApiCall($integration, $method, $endpoint, false, null, $durationMs, $httpStatus);
                $this->handleApiError($integration, $endpoint, $response);
            }

            $this->logApiCall($integration, $method, $endpoint, true, null, $durationMs, $httpStatus);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $this->logApiCall($integration, $method, $endpoint, false, $e->getMessage(), $durationMs, null);
            throw $e;
        }
    }

    /**
     * Check if token should be refreshed
     *
     * @param Integration $integration
     * @return bool
     */
    protected function shouldRefreshToken(Integration $integration): bool
    {
        if (!$integration->token_expires_at) {
            return false;
        }

        // Refresh if token expires in less than 5 minutes
        return now()->addMinutes(5)->isAfter($integration->token_expires_at);
    }

    /**
     * Handle API errors
     *
     * @param Integration $integration
     * @param string $endpoint
     * @param \Illuminate\Http\Client\Response $response
     * @throws \Exception
     */
    protected function handleApiError(Integration $integration, string $endpoint, $response): void
    {
        $error = $response->json();
        $statusCode = $response->status();

        Log::error("API Error for {$this->platform}", [
            'integration_id' => $integration->integration_id,
            'endpoint' => $endpoint,
            'status' => $statusCode,
            'error' => $error,
        ]);

        // Handle specific error codes
        if ($statusCode === 401) {
            // Token expired or invalid
            throw new \Exception('Authentication failed. Please reconnect your account.');
        } elseif ($statusCode === 403) {
            throw new \Exception('Permission denied. Please check your app permissions.');
        } elseif ($statusCode === 429) {
            throw new \Exception('Rate limit exceeded. Please try again later.');
        } else {
            $message = $error['error']['message'] ?? $error['message'] ?? 'API request failed';
            throw new \Exception($message);
        }
    }

    /**
     * Log API call to database for analytics tracking
     *
     * @param Integration $integration
     * @param string $method
     * @param string $endpoint
     * @param bool $success
     * @param string|null $errorMessage
     * @param int|null $durationMs
     * @param int|null $httpStatus
     */
    protected function logApiCall(Integration $integration, string $method, string $endpoint, bool $success, ?string $errorMessage = null, ?int $durationMs = null, ?int $httpStatus = null): void
    {
        try {
            DB::table('cmis.platform_api_calls')->insert([
                'call_id' => \Illuminate\Support\Str::uuid()->toString(),
                'org_id' => $integration->org_id,
                'connection_id' => $integration->integration_id,
                'platform' => $this->platform,
                'endpoint' => $endpoint,
                'method' => strtoupper($method),
                'action_type' => $this->getActionTypeFromEndpoint($endpoint),
                'http_status' => $httpStatus,
                'duration_ms' => $durationMs,
                'success' => $success,
                'error_message' => $errorMessage,
                'called_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Don't let logging failures break the main flow
            Log::warning('Failed to log API call', [
                'error' => $e->getMessage(),
                'platform' => $this->platform,
                'endpoint' => $endpoint,
            ]);
        }
    }

    /**
     * Determine action type from endpoint URL
     */
    protected function getActionTypeFromEndpoint(string $endpoint): string
    {
        $endpoint = strtolower($endpoint);

        if (str_contains($endpoint, 'campaign')) return 'campaigns';
        if (str_contains($endpoint, 'adset') || str_contains($endpoint, 'ad_set')) return 'adsets';
        if (str_contains($endpoint, 'creative')) return 'creatives';
        if (str_contains($endpoint, 'insight') || str_contains($endpoint, 'report')) return 'insights';
        if (str_contains($endpoint, 'account')) return 'accounts';
        if (str_contains($endpoint, 'audience')) return 'audiences';
        if (str_contains($endpoint, 'analytics')) return 'analytics';
        if (str_contains($endpoint, 'token') || str_contains($endpoint, 'oauth')) return 'auth';

        return 'other';
    }

    /**
     * Store synced data in database
     *
     * @param string $table
     * @param array $data
     * @param array $uniqueKeys
     * @return int
     */
    protected function storeData(string $table, array $data, array $uniqueKeys = []): int
    {
        if (empty($uniqueKeys)) {
            return DB::table($table)->insertGetId($data);
        }

        // Upsert: update if exists, insert if not
        $existing = DB::table($table)->where($uniqueKeys)->first();

        if ($existing) {
            DB::table($table)->where($uniqueKeys)->update($data);
            return $existing->{array_key_first((array)$existing)};
        }

        return DB::table($table)->insertGetId($data);
    }

    /**
     * Get OAuth authorization URL
     *
     * @param array $options
     * @return string
     */
    abstract public function getAuthUrl(array $options = []): string;

    /**
     * Build query string for pagination
     *
     * @param array $params
     * @param string|null $cursor
     * @return string
     */
    protected function buildQueryString(array $params, ?string $cursor = null): string
    {
        if ($cursor) {
            $params['cursor'] = $cursor;
        }

        return http_build_query($params);
    }

    /**
     * Extract pagination cursor from response
     *
     * @param array $response
     * @return string|null
     */
    protected function getNextCursor(array $response): ?string
    {
        // Override in child classes based on platform pagination style
        return $response['paging']['cursors']['after'] ??
               $response['next_cursor'] ??
               $response['pagination']['next_cursor'] ??
               null;
    }

    /**
     * Log sync operation
     *
     * @param Integration $integration
     * @param string $syncType
     * @param int $itemsSynced
     * @param array $additionalData
     */
    protected function logSync(Integration $integration, string $syncType, int $itemsSynced, array $additionalData = []): void
    {
        DB::table('cmis.sync_logs')->insert([
            'integration_id' => $integration->integration_id,
            'org_id' => $integration->org_id,
            'platform' => $this->platform,
            'sync_type' => $syncType,
            'status' => 'success',
            'items_synced' => $itemsSynced,
            'sync_data' => json_encode($additionalData),
            'started_at' => now(),
            'completed_at' => now(),
            'created_at' => now(),
        ]);
    }
}
