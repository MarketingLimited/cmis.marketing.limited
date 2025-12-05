<?php

namespace App\Services\Platform;

use App\Models\Platform\PlatformConnection;
use App\Repositories\Contracts\PlatformAssetRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for fetching Snapchat platform assets with caching.
 *
 * Features:
 * - Redis caching with 1-hour TTL
 * - Database persistence for three-tier caching (Cache → DB → API)
 * - OAuth 2.0 token handling
 *
 * Asset Types:
 * - Organizations (Snapchat Business accounts)
 * - Ad Accounts (Snapchat Ads Manager accounts)
 */
class SnapchatAssetsService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const REQUEST_TIMEOUT = 30;
    private const API_BASE_URL = 'https://adsapi.snapchat.com/v1';

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
        return "snapchat_assets:{$connectionId}:{$assetType}";
    }

    /**
     * Get a valid access token, refreshing if expired.
     */
    public function getValidAccessToken(PlatformConnection $connection): ?string
    {
        $accessToken = $connection->access_token;

        // Check if token is expired and we have a refresh token
        if ($connection->token_expires_at && $connection->token_expires_at->isPast() && $connection->refresh_token) {
            $config = config('social-platforms.snapchat');

            try {
                $response = Http::asForm()
                    ->withBasicAuth($config['client_id'], $config['client_secret'])
                    ->post('https://accounts.snapchat.com/login/oauth2/access_token', [
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $connection->refresh_token,
                    ]);

                if ($response->successful()) {
                    $tokenData = $response->json();
                    $connection->update([
                        'access_token' => $tokenData['access_token'],
                        'refresh_token' => $tokenData['refresh_token'] ?? $connection->refresh_token,
                        'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 1800),
                    ]);
                    $accessToken = $tokenData['access_token'];
                } else {
                    Log::warning('Failed to refresh Snapchat token', ['response' => $response->json()]);
                }
            } catch (\Exception $e) {
                Log::error('Exception refreshing Snapchat token', ['error' => $e->getMessage()]);
            }
        }

        return $accessToken;
    }

    /**
     * Get Snapchat organizations the user has access to.
     */
    public function getOrganizations(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'organizations');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken) {
            Log::info('Fetching Snapchat organizations from API', ['connection_id' => $connectionId]);

            try {
                $response = Http::withToken($accessToken)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->get(self::API_BASE_URL . '/me/organizations');

                if (!$response->successful()) {
                    Log::warning('Snapchat organizations API failed', [
                        'status' => $response->status(),
                        'body' => $response->json(),
                    ]);
                    return [];
                }

                $organizations = [];
                foreach ($response->json('organizations', []) as $org) {
                    $orgData = $org['organization'] ?? $org;

                    $organizations[] = [
                        'id' => $orgData['id'] ?? '',
                        'name' => $orgData['name'] ?? 'Unknown Organization',
                        'address_line_1' => $orgData['address_line_1'] ?? null,
                        'city' => $orgData['city'] ?? null,
                        'country' => $orgData['country'] ?? null,
                        'postal_code' => $orgData['postal_code'] ?? null,
                        'type' => $orgData['type'] ?? 'ENTERPRISE',
                        'state' => $orgData['state'] ?? null,
                        'created_at' => $orgData['created_at'] ?? null,
                        'updated_at' => $orgData['updated_at'] ?? null,
                    ];
                }

                Log::info('Snapchat organizations fetched', ['count' => count($organizations)]);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'organization', $organizations);

                return $organizations;
            } catch (\Exception $e) {
                Log::error('Exception fetching Snapchat organizations', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Get Snapchat Ad Accounts.
     */
    public function getAdAccounts(string $connectionId, string $accessToken, ?string $organizationId = null, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'ad_accounts');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken, $organizationId) {
            Log::info('Fetching Snapchat ad accounts from API', [
                'connection_id' => $connectionId,
                'organization_id' => $organizationId,
            ]);

            try {
                // If no organization ID provided, first get organizations
                if (!$organizationId) {
                    $orgs = $this->getOrganizations($connectionId, $accessToken, true);
                    if (empty($orgs)) {
                        Log::info('No organizations found, cannot fetch ad accounts');
                        return [];
                    }
                    $organizationId = $orgs[0]['id'] ?? null;
                }

                if (!$organizationId) {
                    return [];
                }

                $response = Http::withToken($accessToken)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->get(self::API_BASE_URL . "/organizations/{$organizationId}/adaccounts");

                if (!$response->successful()) {
                    Log::warning('Snapchat ad accounts API failed', [
                        'status' => $response->status(),
                        'body' => $response->json(),
                    ]);
                    return [];
                }

                $accounts = [];
                foreach ($response->json('adaccounts', []) as $account) {
                    $accountData = $account['adaccount'] ?? $account;

                    $accounts[] = [
                        'id' => $accountData['id'] ?? '',
                        'name' => $accountData['name'] ?? "Ad Account {$accountData['id']}",
                        'type' => $accountData['type'] ?? 'PARTNER',
                        'status' => $accountData['status'] ?? 'ACTIVE',
                        'currency' => $accountData['currency'] ?? 'USD',
                        'timezone' => $accountData['timezone'] ?? 'America/Los_Angeles',
                        'organization_id' => $organizationId,
                        'advertiser' => $accountData['advertiser'] ?? null,
                        'advertiser_organization_id' => $accountData['advertiser_organization_id'] ?? null,
                        'billing_type' => $accountData['billing_type'] ?? null,
                        'created_at' => $accountData['created_at'] ?? null,
                        'updated_at' => $accountData['updated_at'] ?? null,
                    ];
                }

                Log::info('Snapchat ad accounts fetched', ['count' => count($accounts)]);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'ad_account', $accounts);

                return $accounts;
            } catch (\Exception $e) {
                Log::error('Exception fetching Snapchat ad accounts', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Clear all cached assets for a connection.
     */
    public function clearCache(string $connectionId): void
    {
        $assetTypes = ['organizations', 'ad_accounts'];

        foreach ($assetTypes as $type) {
            Cache::forget($this->getCacheKey($connectionId, $type));
        }

        Log::info('Snapchat assets cache cleared', ['connection_id' => $connectionId]);
    }

    /**
     * Get cache status for all asset types.
     */
    public function getCacheStatus(string $connectionId): array
    {
        $assetTypes = ['organizations', 'ad_accounts'];
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
     * @param string $assetType Asset type (organization, ad_account)
     * @param array $assets Array of asset data
     */
    protected function persistAssets(string $connectionId, string $assetType, array $assets): void
    {
        if (!$this->repository || empty($assets)) {
            return;
        }

        try {
            $count = $this->repository->bulkUpsert('snapchat', $assetType, $assets, $connectionId);

            // Record org access if org_id is set
            if ($this->orgId) {
                foreach ($assets as $assetData) {
                    $assetId = $this->extractAssetId($assetData, $assetType);
                    if (!$assetId) {
                        continue;
                    }

                    $asset = $this->repository->findOrCreate(
                        'snapchat',
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

            Log::debug("Persisted Snapchat {$assetType} assets to database", [
                'connection_id' => $connectionId,
                'count' => $count,
                'org_id' => $this->orgId,
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to persist Snapchat {$assetType} assets", [
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

        // Check status for ad accounts
        $status = strtoupper($data['status'] ?? '');
        if ($status === 'ACTIVE') {
            $types[] = 'write';
        }

        // Check type
        $type = strtoupper($data['type'] ?? '');
        if (in_array($type, ['DIRECT', 'ENTERPRISE'])) {
            $types[] = 'admin';
        }

        return array_unique($types);
    }
}
