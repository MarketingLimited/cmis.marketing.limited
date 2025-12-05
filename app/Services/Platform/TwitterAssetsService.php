<?php

namespace App\Services\Platform;

use App\Models\Platform\PlatformConnection;
use App\Repositories\Contracts\PlatformAssetRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for fetching Twitter/X platform assets with caching.
 *
 * Features:
 * - Redis caching with 1-hour TTL
 * - Database persistence for three-tier caching (Cache → DB → API)
 * - OAuth 2.0 token handling
 *
 * Asset Types:
 * - Accounts (Twitter user accounts for publishing)
 * - Ad Accounts (Twitter Ads accounts)
 */
class TwitterAssetsService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const REQUEST_TIMEOUT = 30;

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
        return "twitter_assets:{$connectionId}:{$assetType}";
    }

    /**
     * Get a valid access token, refreshing if expired.
     */
    public function getValidAccessToken(PlatformConnection $connection): ?string
    {
        $accessToken = $connection->access_token;

        // Check if token is expired and we have a refresh token
        if ($connection->token_expires_at && $connection->token_expires_at->isPast() && $connection->refresh_token) {
            $config = config('social-platforms.twitter');

            try {
                $response = Http::asForm()
                    ->withBasicAuth($config['client_id'], $config['client_secret'])
                    ->post('https://api.twitter.com/2/oauth2/token', [
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $connection->refresh_token,
                    ]);

                if ($response->successful()) {
                    $tokenData = $response->json();
                    $connection->update([
                        'access_token' => $tokenData['access_token'],
                        'refresh_token' => $tokenData['refresh_token'] ?? $connection->refresh_token,
                        'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 7200),
                    ]);
                    $accessToken = $tokenData['access_token'];
                } else {
                    Log::warning('Failed to refresh Twitter token', ['response' => $response->json()]);
                }
            } catch (\Exception $e) {
                Log::error('Exception refreshing Twitter token', ['error' => $e->getMessage()]);
            }
        }

        return $accessToken;
    }

    /**
     * Get Twitter user account information.
     */
    public function getAccount(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'account');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken) {
            Log::info('Fetching Twitter account from API', ['connection_id' => $connectionId]);

            try {
                $response = Http::withToken($accessToken)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->get('https://api.twitter.com/2/users/me', [
                        'user.fields' => 'id,name,username,profile_image_url,description,public_metrics,verified,created_at',
                    ]);

                if (!$response->successful()) {
                    Log::warning('Twitter users/me API failed', [
                        'status' => $response->status(),
                        'body' => $response->json(),
                    ]);
                    return [];
                }

                $userData = $response->json('data', []);
                if (empty($userData)) {
                    return [];
                }

                $accounts = [[
                    'id' => $userData['id'] ?? '',
                    'name' => $userData['name'] ?? 'Unknown User',
                    'username' => $userData['username'] ?? '',
                    'profile_image_url' => $userData['profile_image_url'] ?? null,
                    'description' => $userData['description'] ?? '',
                    'followers_count' => $userData['public_metrics']['followers_count'] ?? 0,
                    'following_count' => $userData['public_metrics']['following_count'] ?? 0,
                    'tweet_count' => $userData['public_metrics']['tweet_count'] ?? 0,
                    'verified' => $userData['verified'] ?? false,
                    'created_at' => $userData['created_at'] ?? null,
                ]];

                Log::info('Twitter account fetched', ['user_id' => $userData['id'] ?? 'unknown']);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'account', $accounts);

                return $accounts;
            } catch (\Exception $e) {
                Log::error('Exception fetching Twitter account', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Get Twitter Ads accounts.
     *
     * Note: Requires Twitter Ads API access which is separate from v2 API.
     */
    public function getAdAccounts(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'ad_accounts');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken) {
            Log::info('Fetching Twitter ad accounts from API', ['connection_id' => $connectionId]);

            try {
                // Twitter Ads API v12
                $response = Http::withToken($accessToken)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->get('https://ads-api.twitter.com/12/accounts', [
                        'with_deleted' => 'false',
                    ]);

                if (!$response->successful()) {
                    $error = $response->json('errors.0', []);
                    Log::warning('Twitter ad accounts API failed', [
                        'status' => $response->status(),
                        'error' => $error,
                    ]);

                    // Check for access denied (no ads access)
                    if ($response->status() === 403) {
                        return ['accounts' => [], 'error' => [
                            'type' => 'no_ads_access',
                            'message' => 'Twitter Ads API access not available for this account.',
                        ]];
                    }

                    return ['accounts' => [], 'error' => null];
                }

                $accounts = [];
                foreach ($response->json('data', []) as $account) {
                    $accounts[] = [
                        'id' => $account['id'] ?? '',
                        'name' => $account['name'] ?? "Ad Account {$account['id']}",
                        'business_name' => $account['business_name'] ?? null,
                        'timezone' => $account['timezone'] ?? null,
                        'timezone_switch_at' => $account['timezone_switch_at'] ?? null,
                        'created_at' => $account['created_at'] ?? null,
                        'approval_status' => $account['approval_status'] ?? 'UNKNOWN',
                        'deleted' => $account['deleted'] ?? false,
                        'currency' => $account['currency'] ?? 'USD',
                    ];
                }

                Log::info('Twitter ad accounts fetched', ['count' => count($accounts)]);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'ad_account', $accounts);

                return ['accounts' => $accounts, 'error' => null];
            } catch (\Exception $e) {
                Log::error('Exception fetching Twitter ad accounts', ['error' => $e->getMessage()]);
                return ['accounts' => [], 'error' => ['type' => 'exception', 'message' => $e->getMessage()]];
            }
        });
    }

    /**
     * Clear all cached assets for a connection.
     */
    public function clearCache(string $connectionId): void
    {
        $assetTypes = ['account', 'ad_accounts'];

        foreach ($assetTypes as $type) {
            Cache::forget($this->getCacheKey($connectionId, $type));
        }

        Log::info('Twitter assets cache cleared', ['connection_id' => $connectionId]);
    }

    /**
     * Get cache status for all asset types.
     */
    public function getCacheStatus(string $connectionId): array
    {
        $assetTypes = ['account', 'ad_accounts'];
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
     * @param string $assetType Asset type (account, ad_account)
     * @param array $assets Array of asset data
     */
    protected function persistAssets(string $connectionId, string $assetType, array $assets): void
    {
        if (!$this->repository || empty($assets)) {
            return;
        }

        try {
            $count = $this->repository->bulkUpsert('twitter', $assetType, $assets, $connectionId);

            // Record org access if org_id is set
            if ($this->orgId) {
                foreach ($assets as $assetData) {
                    $assetId = $this->extractAssetId($assetData, $assetType);
                    if (!$assetId) {
                        continue;
                    }

                    $asset = $this->repository->findOrCreate(
                        'twitter',
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
                            'permissions' => [],
                            'roles' => [],
                        ]
                    );
                }
            }

            Log::debug("Persisted Twitter {$assetType} assets to database", [
                'connection_id' => $connectionId,
                'count' => $count,
                'org_id' => $this->orgId,
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to persist Twitter {$assetType} assets", [
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

        // Ad accounts check approval status
        $approvalStatus = strtoupper($data['approval_status'] ?? '');
        if ($approvalStatus === 'ACCEPTED') {
            $types[] = 'write';
            $types[] = 'admin';
        }

        // Check if deleted
        if ($data['deleted'] ?? false) {
            return ['read']; // Only read for deleted accounts
        }

        return array_unique($types);
    }
}
