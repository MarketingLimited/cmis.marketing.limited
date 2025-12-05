<?php

namespace App\Services\Platform;

use App\Models\Platform\PlatformConnection;
use App\Repositories\Contracts\PlatformAssetRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for fetching Pinterest platform assets with caching.
 *
 * Features:
 * - Redis caching with 1-hour TTL
 * - Database persistence for three-tier caching (Cache → DB → API)
 * - OAuth 2.0 token handling
 *
 * Asset Types:
 * - Accounts (Pinterest user accounts)
 * - Boards (Pinterest boards for pin organization)
 * - Ad Accounts (Pinterest Business accounts for advertising)
 */
class PinterestAssetsService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const REQUEST_TIMEOUT = 30;
    private const API_BASE_URL = 'https://api.pinterest.com/v5';

    /**
     * Platform asset repository for database persistence.
     */
    protected ?PlatformAssetRepositoryInterface $repository;

    /**
     * Organization ID for recording asset access.
     */
    protected ?string $orgId = null;

    public function __construct(?PlatformAssetRepositoryInterface $repository = null)
    {
        $this->repository = $repository;
    }

    /**
     * Set the organization ID for asset access recording.
     */
    public function setOrgId(string $orgId): self
    {
        $this->orgId = $orgId;
        return $this;
    }

    /**
     * Get cache key for a specific asset type and connection.
     */
    private function getCacheKey(string $connectionId, string $assetType): string
    {
        return "pinterest_assets:{$connectionId}:{$assetType}";
    }

    /**
     * Get a valid access token, refreshing if expired.
     */
    public function getValidAccessToken(PlatformConnection $connection): ?string
    {
        $accessToken = $connection->access_token;

        // Check if token is expired and we have a refresh token
        if ($connection->token_expires_at && $connection->token_expires_at->isPast() && $connection->refresh_token) {
            $config = config('social-platforms.pinterest');

            try {
                $response = Http::asForm()
                    ->withBasicAuth($config['client_id'], $config['client_secret'])
                    ->post('https://api.pinterest.com/v5/oauth/token', [
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $connection->refresh_token,
                    ]);

                if ($response->successful()) {
                    $tokenData = $response->json();
                    $connection->update([
                        'access_token' => $tokenData['access_token'],
                        'refresh_token' => $tokenData['refresh_token'] ?? $connection->refresh_token,
                        'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 2592000), // 30 days default
                    ]);
                    $accessToken = $tokenData['access_token'];
                } else {
                    Log::warning('Failed to refresh Pinterest token', ['response' => $response->json()]);
                }
            } catch (\Exception $e) {
                Log::error('Exception refreshing Pinterest token', ['error' => $e->getMessage()]);
            }
        }

        return $accessToken;
    }

    /**
     * Get Pinterest user account information.
     */
    public function getAccount(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'account');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken) {
            Log::info('Fetching Pinterest account from API', ['connection_id' => $connectionId]);

            try {
                $response = Http::withToken($accessToken)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->get(self::API_BASE_URL . '/user_account');

                if (!$response->successful()) {
                    Log::warning('Pinterest user_account API failed', [
                        'status' => $response->status(),
                        'body' => $response->json(),
                    ]);
                    return [];
                }

                $userData = $response->json();
                if (empty($userData)) {
                    return [];
                }

                $accounts = [[
                    'id' => $userData['id'] ?? '',
                    'username' => $userData['username'] ?? '',
                    'account_type' => $userData['account_type'] ?? 'PERSONAL',
                    'profile_image' => $userData['profile_image'] ?? null,
                    'website_url' => $userData['website_url'] ?? null,
                    'follower_count' => $userData['follower_count'] ?? 0,
                    'following_count' => $userData['following_count'] ?? 0,
                    'pin_count' => $userData['pin_count'] ?? 0,
                    'board_count' => $userData['board_count'] ?? 0,
                    'monthly_views' => $userData['monthly_views'] ?? 0,
                ]];

                Log::info('Pinterest account fetched', ['user_id' => $userData['id'] ?? 'unknown']);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'account', $accounts);

                return $accounts;
            } catch (\Exception $e) {
                Log::error('Exception fetching Pinterest account', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Get Pinterest boards.
     */
    public function getBoards(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'boards');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken) {
            Log::info('Fetching Pinterest boards from API', ['connection_id' => $connectionId]);

            try {
                $boards = [];
                $bookmark = null;

                // Paginate through all boards
                do {
                    $params = ['page_size' => 100];
                    if ($bookmark) {
                        $params['bookmark'] = $bookmark;
                    }

                    $response = Http::withToken($accessToken)
                        ->timeout(self::REQUEST_TIMEOUT)
                        ->get(self::API_BASE_URL . '/boards', $params);

                    if (!$response->successful()) {
                        Log::warning('Pinterest boards API failed', [
                            'status' => $response->status(),
                            'body' => $response->json(),
                        ]);
                        break;
                    }

                    $data = $response->json();
                    foreach ($data['items'] ?? [] as $board) {
                        $boards[] = [
                            'id' => $board['id'] ?? '',
                            'name' => $board['name'] ?? 'Unknown Board',
                            'description' => $board['description'] ?? '',
                            'privacy' => $board['privacy'] ?? 'PUBLIC',
                            'pin_count' => $board['pin_count'] ?? 0,
                            'follower_count' => $board['follower_count'] ?? 0,
                            'collaborator_count' => $board['collaborator_count'] ?? 0,
                            'media' => $board['media'] ?? null,
                            'created_at' => $board['created_at'] ?? null,
                        ];
                    }

                    $bookmark = $data['bookmark'] ?? null;
                } while ($bookmark);

                Log::info('Pinterest boards fetched', ['count' => count($boards)]);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'board', $boards);

                return $boards;
            } catch (\Exception $e) {
                Log::error('Exception fetching Pinterest boards', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Get Pinterest Ad Accounts.
     */
    public function getAdAccounts(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'ad_accounts');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken) {
            Log::info('Fetching Pinterest ad accounts from API', ['connection_id' => $connectionId]);

            try {
                $accounts = [];
                $bookmark = null;

                // Paginate through all ad accounts
                do {
                    $params = ['page_size' => 100];
                    if ($bookmark) {
                        $params['bookmark'] = $bookmark;
                    }

                    $response = Http::withToken($accessToken)
                        ->timeout(self::REQUEST_TIMEOUT)
                        ->get(self::API_BASE_URL . '/ad_accounts', $params);

                    if (!$response->successful()) {
                        $error = $response->json();
                        Log::warning('Pinterest ad accounts API failed', [
                            'status' => $response->status(),
                            'body' => $error,
                        ]);

                        // Check for no ads access
                        if ($response->status() === 403) {
                            return ['accounts' => [], 'error' => [
                                'type' => 'no_ads_access',
                                'message' => 'Pinterest Ads access not available. Business account required.',
                            ]];
                        }

                        return ['accounts' => [], 'error' => null];
                    }

                    $data = $response->json();
                    foreach ($data['items'] ?? [] as $account) {
                        $accounts[] = [
                            'id' => $account['id'] ?? '',
                            'name' => $account['name'] ?? "Ad Account {$account['id']}",
                            'owner' => $account['owner'] ?? null,
                            'country' => $account['country'] ?? null,
                            'currency' => $account['currency'] ?? 'USD',
                            'permissions' => $account['permissions'] ?? [],
                            'created_time' => $account['created_time'] ?? null,
                            'updated_time' => $account['updated_time'] ?? null,
                        ];
                    }

                    $bookmark = $data['bookmark'] ?? null;
                } while ($bookmark);

                Log::info('Pinterest ad accounts fetched', ['count' => count($accounts)]);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'ad_account', $accounts);

                return ['accounts' => $accounts, 'error' => null];
            } catch (\Exception $e) {
                Log::error('Exception fetching Pinterest ad accounts', ['error' => $e->getMessage()]);
                return ['accounts' => [], 'error' => ['type' => 'exception', 'message' => $e->getMessage()]];
            }
        });
    }

    /**
     * Clear all cached assets for a connection.
     */
    public function clearCache(string $connectionId): void
    {
        $assetTypes = ['account', 'boards', 'ad_accounts'];

        foreach ($assetTypes as $type) {
            Cache::forget($this->getCacheKey($connectionId, $type));
        }

        Log::info('Pinterest assets cache cleared', ['connection_id' => $connectionId]);
    }

    /**
     * Get cache status for all asset types.
     */
    public function getCacheStatus(string $connectionId): array
    {
        $assetTypes = ['account', 'boards', 'ad_accounts'];
        $status = [];

        foreach ($assetTypes as $type) {
            $cacheKey = $this->getCacheKey($connectionId, $type);
            $status[$type] = [
                'cached' => Cache::has($cacheKey),
                'key' => $cacheKey,
            ];
        }

        return $status;
    }

    /**
     * Persist assets to database for three-tier caching.
     *
     * @param string $connectionId Connection UUID
     * @param string $assetType Asset type (account, board, ad_account)
     * @param array $assets Array of asset data
     */
    protected function persistAssets(string $connectionId, string $assetType, array $assets): void
    {
        if (!$this->repository || empty($assets)) {
            return;
        }

        try {
            $count = $this->repository->bulkUpsert('pinterest', $assetType, $assets, $connectionId);

            // Record org access if org_id is set
            if ($this->orgId) {
                foreach ($assets as $assetData) {
                    $assetId = $this->extractAssetId($assetData, $assetType);
                    if (!$assetId) {
                        continue;
                    }

                    $asset = $this->repository->findOrCreate(
                        'pinterest',
                        $assetId,
                        $assetType,
                        $assetData
                    );

                    $this->repository->recordOrgAccess(
                        $this->orgId,
                        $asset->asset_id,
                        $connectionId,
                        [
                            'access_types' => $this->inferAccessTypes($assetData),
                            'permissions' => $assetData['permissions'] ?? [],
                            'roles' => [],
                        ]
                    );
                }
            }

            Log::debug("Persisted Pinterest {$assetType} assets to database", [
                'connection_id' => $connectionId,
                'count' => $count,
                'org_id' => $this->orgId,
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to persist Pinterest {$assetType} assets", [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Extract asset ID from asset data based on asset type.
     */
    protected function extractAssetId(array $data, string $assetType): ?string
    {
        return $data['id'] ?? null;
    }

    /**
     * Infer access types from asset data.
     */
    protected function inferAccessTypes(array $data): array
    {
        $types = ['read'];

        // User accounts have full access
        if (isset($data['username'])) {
            $types = array_merge($types, ['write', 'publish', 'admin']);
        }

        // Boards - check privacy
        $privacy = strtoupper($data['privacy'] ?? '');
        if (isset($data['pin_count'])) {  // Board indicator
            $types[] = 'write';
            $types[] = 'publish';
        }

        // Ad accounts - check permissions
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $permissions = array_map('strtoupper', $data['permissions']);
            if (in_array('ADMIN', $permissions)) {
                $types[] = 'admin';
            }
            if (in_array('ANALYST', $permissions) || in_array('ADMIN', $permissions)) {
                $types[] = 'analyze';
            }
            if (in_array('CAMPAIGN_MANAGER', $permissions) || in_array('ADMIN', $permissions)) {
                $types[] = 'write';
            }
        }

        // Account type
        $accountType = strtoupper($data['account_type'] ?? '');
        if ($accountType === 'BUSINESS') {
            $types[] = 'admin';
        }

        return array_unique($types);
    }
}
