<?php

namespace App\Services\Platform\Batchers;

use App\Models\Platform\PlatformConnection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TwitterBatcher
 *
 * Implements Twitter (X) Ads-specific batch optimizations:
 * - Batch user lookup: Up to 100 users per request
 * - Combined metrics: Multiple metrics in single request
 * - Async analytics: Request async reports for large data
 *
 * Expected reduction: 40-60% fewer API calls
 *
 * @see https://developer.twitter.com/en/docs/twitter-ads-api
 */
class TwitterBatcher implements PlatformBatcherInterface
{
    private const API_VERSION = '12';
    private const BASE_URL = 'https://ads-api.twitter.com';
    private const MAX_BATCH_SIZE = 100;
    private const FLUSH_INTERVAL = 300; // 5 minutes
    private const REQUEST_TIMEOUT = 60;

    /**
     * Request types this batcher can handle
     */
    private const SUPPORTED_REQUEST_TYPES = [
        // Account & Campaigns
        'get_accounts',
        'get_campaigns',
        'get_line_items',
        'get_promoted_tweets',

        // Creatives
        'get_media_creatives',
        'get_cards',

        // Targeting
        'get_targeting_criteria',
        'get_tailored_audiences',

        // Analytics
        'get_stats',
        'get_reach_frequency',
        'get_async_stats',

        // User lookup
        'get_users',
        'lookup_users',
    ];

    public function __construct()
    {
    }

    /**
     * Execute a batch of queued requests
     */
    public function executeBatch(string $connectionId, Collection $requests): array
    {
        $connection = PlatformConnection::find($connectionId);

        if (!$connection || !$connection->access_token) {
            Log::warning('TwitterBatcher: Invalid connection or missing token', [
                'connection_id' => $connectionId,
            ]);
            return $this->markAllFailed($requests, 'Invalid connection or missing token');
        }

        $accessToken = $connection->access_token;
        $accessTokenSecret = $connection->credentials['access_token_secret'] ?? null;
        $accountId = $connection->account_id;

        if (!$accountId) {
            Log::warning('TwitterBatcher: Missing account ID', [
                'connection_id' => $connectionId,
            ]);
            return $this->markAllFailed($requests, 'Missing Twitter Ads account ID');
        }

        $results = [];

        // Group requests by type for optimal batching strategy
        $grouped = $requests->groupBy('request_type');

        foreach ($grouped as $requestType => $typeRequests) {
            $typeResults = $this->executeByType(
                $requestType,
                $typeRequests,
                $accountId,
                $accessToken,
                $accessTokenSecret
            );
            $results = array_merge($results, $typeResults);
        }

        return $results;
    }

    /**
     * Execute requests by type using the most efficient method
     */
    protected function executeByType(
        string $requestType,
        Collection $requests,
        string $accountId,
        string $accessToken,
        ?string $accessTokenSecret
    ): array {
        return match ($requestType) {
            'get_users', 'lookup_users' => $this->batchUserLookup($requests, $accessToken),
            'get_stats' => $this->getCombinedStats($requests, $accountId, $accessToken, $accessTokenSecret),
            'get_async_stats' => $this->getAsyncStats($requests, $accountId, $accessToken, $accessTokenSecret),
            default => $this->executeStandardRequest($requestType, $requests, $accountId, $accessToken, $accessTokenSecret),
        };
    }

    /**
     * Batch user lookup - up to 100 users per request
     */
    protected function batchUserLookup(
        Collection $requests,
        string $accessToken
    ): array {
        Log::info('TwitterBatcher: Batch user lookup', [
            'request_count' => $requests->count(),
        ]);

        $results = [];

        // Collect all user IDs/usernames from requests
        $userIds = $requests
            ->flatMap(fn($r) => $r->request_params['user_ids'] ?? [])
            ->unique()
            ->values()
            ->toArray();

        if (empty($userIds)) {
            foreach ($requests as $request) {
                $results[$request->id] = ['error' => 'No user IDs provided'];
            }
            return $results;
        }

        try {
            // Chunk to respect 100 user limit
            $chunks = array_chunk($userIds, self::MAX_BATCH_SIZE);

            $allUsers = [];
            foreach ($chunks as $chunk) {
                $url = 'https://api.twitter.com/2/users';

                $response = Http::timeout(self::REQUEST_TIMEOUT)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                    ])
                    ->get($url, [
                        'ids' => implode(',', $chunk),
                        'user.fields' => 'id,name,username,description,profile_image_url,public_metrics,verified',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $users = $data['data'] ?? [];
                    foreach ($users as $user) {
                        $allUsers[$user['id']] = $user;
                    }
                }

                // Small delay between chunks
                if (count($chunks) > 1) {
                    usleep(100000); // 100ms
                }
            }

            // Map results back to requests
            foreach ($requests as $request) {
                $reqUserIds = $request->request_params['user_ids'] ?? [];
                $matchedUsers = array_filter(
                    $allUsers,
                    fn($user) => in_array($user['id'], $reqUserIds)
                );
                $results[$request->id] = ['users' => array_values($matchedUsers)];
            }

            Log::info('TwitterBatcher: User lookup completed', [
                'users_fetched' => count($allUsers),
            ]);

        } catch (\Exception $e) {
            Log::error('TwitterBatcher: User lookup failed', [
                'error' => $e->getMessage(),
            ]);

            foreach ($requests as $request) {
                $results[$request->id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Get combined stats - multiple entity stats in single request
     */
    protected function getCombinedStats(
        Collection $requests,
        string $accountId,
        string $accessToken,
        ?string $accessTokenSecret
    ): array {
        Log::info('TwitterBatcher: Getting combined stats', [
            'request_count' => $requests->count(),
        ]);

        $results = [];

        // Collect all entity IDs by type
        $entityMap = [
            'CAMPAIGN' => [],
            'LINE_ITEM' => [],
            'PROMOTED_TWEET' => [],
        ];

        foreach ($requests as $request) {
            $params = $request->request_params ?? [];
            $entityType = strtoupper($params['entity_type'] ?? 'CAMPAIGN');
            $entityIds = $params['entity_ids'] ?? [];

            if (isset($entityMap[$entityType])) {
                $entityMap[$entityType] = array_merge($entityMap[$entityType], $entityIds);
            }
        }

        try {
            $allStats = [];

            foreach ($entityMap as $entityType => $entityIds) {
                if (empty($entityIds)) {
                    continue;
                }

                $entityIds = array_unique($entityIds);

                // Chunk if needed
                $chunks = array_chunk($entityIds, 20); // Twitter limits to 20 per request

                foreach ($chunks as $chunk) {
                    $url = self::BASE_URL . '/' . self::API_VERSION
                        . '/stats/accounts/' . $accountId;

                    $params = $requests->first()?->request_params ?? [];
                    $startTime = $params['start_time'] ?? now()->subDays(7)->toIso8601String();
                    $endTime = $params['end_time'] ?? now()->toIso8601String();

                    $response = Http::timeout(self::REQUEST_TIMEOUT)
                        ->withHeaders($this->getAuthHeaders($accessToken))
                        ->get($url, [
                            'entity' => $entityType,
                            'entity_ids' => implode(',', $chunk),
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'granularity' => 'DAY',
                            'metric_groups' => 'ENGAGEMENT,BILLING',
                        ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $statsData = $data['data'] ?? [];
                        foreach ($statsData as $stat) {
                            $entityId = $stat['id'] ?? null;
                            if ($entityId) {
                                $allStats[$entityType][$entityId] = $stat;
                            }
                        }
                    }

                    usleep(100000); // 100ms between chunks
                }
            }

            // Map results back to requests
            foreach ($requests as $request) {
                $params = $request->request_params ?? [];
                $entityType = strtoupper($params['entity_type'] ?? 'CAMPAIGN');
                $entityIds = $params['entity_ids'] ?? [];

                $matchedStats = [];
                foreach ($entityIds as $entityId) {
                    if (isset($allStats[$entityType][$entityId])) {
                        $matchedStats[] = $allStats[$entityType][$entityId];
                    }
                }

                $results[$request->id] = ['stats' => $matchedStats];
            }

            Log::info('TwitterBatcher: Combined stats completed');

        } catch (\Exception $e) {
            Log::error('TwitterBatcher: Combined stats failed', [
                'error' => $e->getMessage(),
            ]);

            foreach ($requests as $request) {
                $results[$request->id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Get async stats - for large data requests
     */
    protected function getAsyncStats(
        Collection $requests,
        string $accountId,
        string $accessToken,
        ?string $accessTokenSecret
    ): array {
        Log::info('TwitterBatcher: Getting async stats', [
            'request_count' => $requests->count(),
        ]);

        $results = [];

        try {
            // Create async job
            $url = self::BASE_URL . '/' . self::API_VERSION
                . '/stats/jobs/accounts/' . $accountId;

            $params = $requests->first()?->request_params ?? [];

            $response = Http::timeout(self::REQUEST_TIMEOUT)
                ->withHeaders($this->getAuthHeaders($accessToken))
                ->post($url, [
                    'entity' => $params['entity_type'] ?? 'CAMPAIGN',
                    'start_time' => $params['start_time'] ?? now()->subDays(30)->toIso8601String(),
                    'end_time' => $params['end_time'] ?? now()->toIso8601String(),
                    'granularity' => $params['granularity'] ?? 'DAY',
                    'metric_groups' => $params['metric_groups'] ?? 'ENGAGEMENT,BILLING,VIDEO',
                    'placement' => $params['placement'] ?? 'ALL_ON_TWITTER',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $jobData = $data['data'] ?? null;

                if ($jobData) {
                    foreach ($requests as $request) {
                        $results[$request->id] = [
                            'job_id' => $jobData['id_str'] ?? $jobData['id'],
                            'status' => $jobData['status'] ?? 'pending',
                            'url' => $jobData['url'] ?? null,
                        ];
                    }
                } else {
                    foreach ($requests as $request) {
                        $results[$request->id] = ['error' => 'Failed to create async job'];
                    }
                }
            } else {
                foreach ($requests as $request) {
                    $results[$request->id] = [
                        'error' => 'Async stats request failed',
                        'code' => $response->status(),
                    ];
                }
            }

            Log::info('TwitterBatcher: Async stats job created');

        } catch (\Exception $e) {
            Log::error('TwitterBatcher: Async stats failed', [
                'error' => $e->getMessage(),
            ]);

            foreach ($requests as $request) {
                $results[$request->id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Execute standard Twitter Ads API request
     */
    protected function executeStandardRequest(
        string $requestType,
        Collection $requests,
        string $accountId,
        string $accessToken,
        ?string $accessTokenSecret
    ): array {
        Log::info('TwitterBatcher: Executing standard request', [
            'request_type' => $requestType,
            'request_count' => $requests->count(),
        ]);

        $results = [];
        $endpoint = $this->getEndpoint($requestType, $accountId);

        if (!$endpoint) {
            foreach ($requests as $request) {
                $results[$request->id] = ['error' => 'Unsupported request type'];
            }
            return $results;
        }

        try {
            $allData = [];
            $cursor = null;

            do {
                $params = ['count' => 100];
                if ($cursor) {
                    $params['cursor'] = $cursor;
                }

                $response = Http::timeout(self::REQUEST_TIMEOUT)
                    ->withHeaders($this->getAuthHeaders($accessToken))
                    ->get($endpoint, $params);

                if ($response->successful()) {
                    $data = $response->json();
                    $items = $data['data'] ?? [];
                    $allData = array_merge($allData, $items);

                    $cursor = $data['next_cursor'] ?? null;
                } else {
                    $cursor = null;
                }

                // Delay between pages
                if ($cursor) {
                    usleep(100000); // 100ms
                }

            } while ($cursor);

            // All requests get the same data
            foreach ($requests as $request) {
                $results[$request->id] = [$requestType => $allData];
            }

            Log::info('TwitterBatcher: Standard request completed', [
                'request_type' => $requestType,
                'results_count' => count($allData),
            ]);

        } catch (\Exception $e) {
            Log::error('TwitterBatcher: Standard request failed', [
                'error' => $e->getMessage(),
            ]);

            foreach ($requests as $request) {
                $results[$request->id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Get endpoint for request type
     */
    protected function getEndpoint(string $requestType, string $accountId): ?string
    {
        $base = self::BASE_URL . '/' . self::API_VERSION . '/accounts/' . $accountId;

        return match ($requestType) {
            'get_accounts' => self::BASE_URL . '/' . self::API_VERSION . '/accounts',
            'get_campaigns' => $base . '/campaigns',
            'get_line_items' => $base . '/line_items',
            'get_promoted_tweets' => $base . '/promoted_tweets',
            'get_media_creatives' => $base . '/media_creatives',
            'get_cards' => $base . '/cards',
            'get_targeting_criteria' => $base . '/targeting_criteria',
            'get_tailored_audiences' => $base . '/tailored_audiences',
            default => null,
        };
    }

    /**
     * Get authorization headers
     */
    protected function getAuthHeaders(string $accessToken): array
    {
        return [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Mark all requests as failed with error message
     */
    protected function markAllFailed(Collection $requests, string $error): array
    {
        $results = [];
        foreach ($requests as $request) {
            $results[$request->id] = ['error' => $error];
        }
        return $results;
    }

    // ===== Interface Implementation =====

    public function getBatchType(): string
    {
        return 'bulk';
    }

    public function getMaxBatchSize(): int
    {
        return self::MAX_BATCH_SIZE;
    }

    public function getFlushInterval(): int
    {
        return self::FLUSH_INTERVAL;
    }

    public function getPlatform(): string
    {
        return 'twitter';
    }

    public function canHandle(string $requestType): bool
    {
        return in_array($requestType, self::SUPPORTED_REQUEST_TYPES);
    }

    public function getSupportedRequestTypes(): array
    {
        return self::SUPPORTED_REQUEST_TYPES;
    }
}
