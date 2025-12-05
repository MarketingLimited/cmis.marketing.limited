<?php

namespace App\Services\Platform;

use App\Models\Platform\PlatformConnection;
use App\Repositories\Contracts\PlatformAssetRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for fetching LinkedIn platform assets with caching.
 *
 * Features:
 * - Redis caching with 1-hour TTL
 * - Database persistence for three-tier caching (Cache → DB → API)
 * - Token refresh handling
 *
 * Asset Types:
 * - Organizations (LinkedIn Pages/Companies)
 * - Ad Accounts (LinkedIn Campaign Manager accounts)
 */
class LinkedInAssetsService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const REQUEST_TIMEOUT = 30;
    private const API_VERSION = '202401'; // LinkedIn Marketing API version

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
        return "linkedin_assets:{$connectionId}:{$assetType}";
    }

    /**
     * Get a valid access token, refreshing if expired.
     */
    public function getValidAccessToken(PlatformConnection $connection): ?string
    {
        $accessToken = $connection->access_token;

        // Check if token is expired and we have a refresh token
        if ($connection->token_expires_at && $connection->token_expires_at->isPast() && $connection->refresh_token) {
            $config = config('social-platforms.linkedin');

            try {
                $response = Http::asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $connection->refresh_token,
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                ]);

                if ($response->successful()) {
                    $tokenData = $response->json();
                    $connection->update([
                        'access_token' => $tokenData['access_token'],
                        'refresh_token' => $tokenData['refresh_token'] ?? $connection->refresh_token,
                        'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
                    ]);
                    $accessToken = $tokenData['access_token'];
                } else {
                    Log::warning('Failed to refresh LinkedIn token', ['response' => $response->json()]);
                }
            } catch (\Exception $e) {
                Log::error('Exception refreshing LinkedIn token', ['error' => $e->getMessage()]);
            }
        }

        return $accessToken;
    }

    /**
     * Get LinkedIn organizations (pages/companies) the user administers.
     */
    public function getOrganizations(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'organizations');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken) {
            Log::info('Fetching LinkedIn organizations from API', ['connection_id' => $connectionId]);

            try {
                // First get organization access control (roles)
                $response = Http::withToken($accessToken)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->withHeaders([
                        'LinkedIn-Version' => self::API_VERSION,
                        'X-Restli-Protocol-Version' => '2.0.0',
                    ])
                    ->get('https://api.linkedin.com/v2/organizationAcls', [
                        'q' => 'roleAssignee',
                        'projection' => '(elements*(organization~(id,localizedName,vanityName,logoV2(original~:playableStreams))))',
                    ]);

                if (!$response->successful()) {
                    Log::warning('LinkedIn organizations API failed', [
                        'status' => $response->status(),
                        'body' => $response->json(),
                    ]);
                    return [];
                }

                $organizations = [];
                foreach ($response->json('elements', []) as $acl) {
                    $org = $acl['organization~'] ?? null;
                    if (!$org) {
                        continue;
                    }

                    $orgId = str_replace('urn:li:organization:', '', $acl['organization'] ?? '');
                    $logoUrl = null;
                    if (isset($org['logoV2']['original~']['elements'][0]['identifiers'][0]['identifier'])) {
                        $logoUrl = $org['logoV2']['original~']['elements'][0]['identifiers'][0]['identifier'];
                    }

                    $organizations[] = [
                        'id' => $orgId,
                        'urn' => $acl['organization'] ?? '',
                        'name' => $org['localizedName'] ?? 'Unknown Organization',
                        'vanity_name' => $org['vanityName'] ?? null,
                        'logo_url' => $logoUrl,
                        'role' => $acl['role'] ?? 'VIEWER',
                        'state' => $acl['state'] ?? 'APPROVED',
                    ];
                }

                Log::info('LinkedIn organizations fetched', ['count' => count($organizations)]);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'organization', $organizations);

                return $organizations;
            } catch (\Exception $e) {
                Log::error('Exception fetching LinkedIn organizations', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Get LinkedIn Ad Accounts (Campaign Manager accounts).
     */
    public function getAdAccounts(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'ad_accounts');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken) {
            Log::info('Fetching LinkedIn ad accounts from API', ['connection_id' => $connectionId]);

            try {
                $response = Http::withToken($accessToken)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->withHeaders([
                        'LinkedIn-Version' => self::API_VERSION,
                        'X-Restli-Protocol-Version' => '2.0.0',
                    ])
                    ->get('https://api.linkedin.com/v2/adAccountsV2', [
                        'q' => 'search',
                        'search' => '(status:(values:List(ACTIVE,DRAFT,CANCELED)))',
                    ]);

                if (!$response->successful()) {
                    Log::warning('LinkedIn ad accounts API failed', [
                        'status' => $response->status(),
                        'body' => $response->json(),
                    ]);
                    return [];
                }

                $accounts = [];
                foreach ($response->json('elements', []) as $account) {
                    $accountId = str_replace('urn:li:sponsoredAccount:', '', $account['id'] ?? '');

                    $accounts[] = [
                        'id' => $accountId,
                        'urn' => $account['id'] ?? '',
                        'name' => $account['name'] ?? "Ad Account {$accountId}",
                        'status' => $account['status'] ?? 'UNKNOWN',
                        'type' => $account['type'] ?? 'BUSINESS',
                        'currency' => $account['currency'] ?? 'USD',
                        'reference_organization' => $account['reference'] ?? null,
                        'created_at' => $account['created'] ?? null,
                    ];
                }

                Log::info('LinkedIn ad accounts fetched', ['count' => count($accounts)]);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'ad_account', $accounts);

                return $accounts;
            } catch (\Exception $e) {
                Log::error('Exception fetching LinkedIn ad accounts', ['error' => $e->getMessage()]);
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

        Log::info('LinkedIn assets cache cleared', ['connection_id' => $connectionId]);
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
            $count = $this->repository->bulkUpsert('linkedin', $assetType, $assets, $connectionId);

            // Record org access if org_id is set
            if ($this->orgId) {
                foreach ($assets as $assetData) {
                    $assetId = $this->extractAssetId($assetData, $assetType);
                    if (!$assetId) {
                        continue;
                    }

                    $asset = $this->repository->findOrCreate(
                        'linkedin',
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
                            'roles' => [$assetData['role'] ?? 'VIEWER'],
                        ]
                    );
                }
            }

            Log::debug("Persisted LinkedIn {$assetType} assets to database", [
                'connection_id' => $connectionId,
                'count' => $count,
                'org_id' => $this->orgId,
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to persist LinkedIn {$assetType} assets", [
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
        // LinkedIn uses URNs, prefer the numeric ID
        return $data['id'] ?? null;
    }

    /**
     * Infer access types from asset data.
     */
    protected function inferAccessTypes(array $data): array
    {
        $types = ['read'];

        // Check role for organizations
        $role = strtoupper($data['role'] ?? '');
        if (in_array($role, ['ADMINISTRATOR', 'DIRECT_SPONSORED_CONTENT_POSTER', 'CONTENT_ADMIN'])) {
            $types[] = 'write';
            $types[] = 'publish';
        }
        if ($role === 'ADMINISTRATOR') {
            $types[] = 'admin';
        }

        // Check status for ad accounts
        $status = strtoupper($data['status'] ?? '');
        if ($status === 'ACTIVE') {
            $types[] = 'write';
        }

        return array_unique($types);
    }
}
