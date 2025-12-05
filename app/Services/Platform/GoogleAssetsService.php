<?php

namespace App\Services\Platform;

use App\Models\Platform\PlatformConnection;
use App\Repositories\Contracts\PlatformAssetRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for fetching Google platform assets with caching.
 *
 * Features:
 * - Redis caching with 1-hour TTL
 * - Database persistence for three-tier caching (Cache → DB → API)
 * - Parallel-friendly design for AJAX loading
 * - Token refresh handling
 *
 * Asset Types:
 * - YouTube Channels (personal + brand accounts)
 * - Google Ads Accounts
 * - Google Analytics Properties (GA4)
 * - Google Business Profiles
 * - Google Tag Manager Containers
 * - Google Merchant Center Accounts
 * - Google Search Console Sites
 * - Google Calendar
 * - Google Drive Folders
 */
class GoogleAssetsService
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
        return "google_assets:{$connectionId}:{$assetType}";
    }

    /**
     * Get a valid access token, refreshing if expired.
     */
    public function getValidAccessToken(PlatformConnection $connection): ?string
    {
        $accessToken = $connection->access_token;

        // Check if token is expired and we have a refresh token
        if ($connection->token_expires_at && $connection->token_expires_at->isPast() && $connection->refresh_token) {
            $config = config('social-platforms.google');

            try {
                $response = Http::asForm()->post($config['token_url'], [
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'refresh_token' => $connection->refresh_token,
                    'grant_type' => 'refresh_token',
                ]);

                if ($response->successful()) {
                    $tokenData = $response->json();
                    $connection->update([
                        'access_token' => $tokenData['access_token'],
                        'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
                    ]);
                    $accessToken = $tokenData['access_token'];
                } else {
                    Log::warning('Failed to refresh Google token', ['response' => $response->json()]);
                }
            } catch (\Exception $e) {
                Log::error('Exception refreshing Google token', ['error' => $e->getMessage()]);
            }
        }

        return $accessToken;
    }

    /**
     * Get YouTube channels from stored metadata.
     *
     * Channels are stored in account_metadata['youtube_channels'] when connected via OAuth.
     * This allows multiple Brand Account channels to persist across different OAuth sessions.
     *
     * @return array{channels?: array, needs_auth?: bool, scope_insufficient?: bool}|array
     */
    public function getYouTubeChannels(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'youtube_channels');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken) {
            Log::info('Fetching YouTube channels', ['connection_id' => $connectionId]);

            try {
                $channels = [];
                $channelIds = [];

                // 1. PRIMARY SOURCE: Get stored channels from connection metadata
                $connection = PlatformConnection::where('connection_id', $connectionId)->first();
                $storedChannels = $connection->account_metadata['youtube_channels'] ?? [];

                Log::info('YouTube channels from metadata', [
                    'connection_id' => $connectionId,
                    'stored_count' => count($storedChannels),
                ]);

                foreach ($storedChannels as $storedChannel) {
                    $channelId = $storedChannel['id'] ?? null;
                    if ($channelId && !in_array($channelId, $channelIds)) {
                        $channelIds[] = $channelId;
                        $channels[] = [
                            'id' => $channelId,
                            'title' => $storedChannel['title'] ?? 'Unknown Channel',
                            'description' => $storedChannel['description'] ?? '',
                            'thumbnail' => $storedChannel['thumbnail'] ?? null,
                            'subscriber_count' => $storedChannel['subscriber_count'] ?? 0,
                            'video_count' => $storedChannel['video_count'] ?? 0,
                            'view_count' => $storedChannel['view_count'] ?? 0,
                            'custom_url' => $storedChannel['custom_url'] ?? null,
                            'type' => $storedChannel['type'] ?? 'brand',
                        ];
                    }
                }

                // 2. If we have stored channels, return them (no need to hit API)
                if (!empty($channels)) {
                    Log::info('YouTube channels returned from metadata', ['count' => count($channels)]);
                    return $channels;
                }

                // 3. FALLBACK: If no stored channels, try API (for initial connection)
                // Use Brand Account token if available, otherwise use main token
                $youtubeToken = $connection->account_metadata['youtube_brand_account']['access_token'] ?? $accessToken;

                $mineResponse = Http::withToken($youtubeToken)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->get('https://www.googleapis.com/youtube/v3/channels', [
                        'part' => 'snippet,statistics,contentDetails,brandingSettings',
                        'mine' => 'true',
                    ]);

                // Check for scope-related errors (403 with insufficientPermissions)
                if ($mineResponse->status() === 403) {
                    $error = $mineResponse->json('error', []);
                    $reason = $error['errors'][0]['reason'] ?? '';
                    $message = $error['message'] ?? '';

                    if ($reason === 'insufficientPermissions' ||
                        str_contains($message, 'scope') ||
                        str_contains($message, 'permission')) {
                        Log::info('YouTube API returned scope-insufficient error', [
                            'reason' => $reason,
                            'message' => $message,
                        ]);
                        return [
                            'channels' => [],
                            'needs_auth' => true,
                            'scope_insufficient' => true,
                        ];
                    }
                }

                if ($mineResponse->successful()) {
                    foreach ($mineResponse->json('items', []) as $channel) {
                        $channelId = $channel['id'];
                        if (!in_array($channelId, $channelIds)) {
                            $channelIds[] = $channelId;
                            $channels[] = $this->formatYouTubeChannel($channel, 'personal');
                        }
                    }
                }

                Log::info('YouTube channels fetched from API', ['count' => count($channels)]);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'youtube_channel', $channels);

                return $channels;
            } catch (\Exception $e) {
                Log::error('Exception fetching YouTube channels', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Format a YouTube channel response.
     */
    private function formatYouTubeChannel(array $channel, string $type = 'personal'): array
    {
        return [
            'id' => $channel['id'],
            'title' => $channel['snippet']['title'] ?? 'Unknown Channel',
            'description' => Str::limit($channel['snippet']['description'] ?? '', 100),
            'thumbnail' => $channel['snippet']['thumbnails']['default']['url'] ?? null,
            'subscriber_count' => $channel['statistics']['subscriberCount'] ?? 0,
            'video_count' => $channel['statistics']['videoCount'] ?? 0,
            'view_count' => $channel['statistics']['viewCount'] ?? 0,
            'custom_url' => $channel['snippet']['customUrl'] ?? null,
            'type' => $type,
        ];
    }

    /**
     * Search for YouTube channels by name.
     * This helps users find their Brand Account channels.
     *
     * @return array{channels: array, error: string|null}
     */
    public function searchYouTubeChannels(string $accessToken, string $query): array
    {
        try {
            Log::info('Searching YouTube channels', ['query' => $query]);

            $response = Http::withToken($accessToken)
                ->timeout(self::REQUEST_TIMEOUT)
                ->get('https://www.googleapis.com/youtube/v3/search', [
                    'part' => 'snippet',
                    'q' => $query,
                    'type' => 'channel',
                    'maxResults' => 10,
                ]);

            if (!$response->successful()) {
                Log::error('YouTube search failed', ['status' => $response->status()]);
                return [
                    'channels' => [],
                    'error' => 'Search failed: ' . ($response->json('error.message') ?? 'Unknown error'),
                ];
            }

            $channels = [];
            foreach ($response->json('items', []) as $item) {
                $channelId = $item['id']['channelId'] ?? $item['snippet']['channelId'] ?? null;
                if ($channelId) {
                    $channels[] = [
                        'id' => $channelId,
                        'title' => $item['snippet']['title'] ?? 'Unknown Channel',
                        'description' => Str::limit($item['snippet']['description'] ?? '', 100),
                        'thumbnail' => $item['snippet']['thumbnails']['default']['url'] ?? null,
                    ];
                }
            }

            Log::info('YouTube search completed', ['count' => count($channels)]);
            return ['channels' => $channels, 'error' => null];
        } catch (\Exception $e) {
            Log::error('Exception searching YouTube channels', ['error' => $e->getMessage()]);
            return ['channels' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get YouTube channel by ID.
     * Used to fetch details for manually added channels.
     *
     * @return array|null
     */
    public function getYouTubeChannelById(string $accessToken, string $channelId): ?array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(self::REQUEST_TIMEOUT)
                ->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'snippet,statistics,contentDetails,brandingSettings',
                    'id' => $channelId,
                ]);

            if ($response->successful()) {
                $items = $response->json('items', []);
                if (!empty($items)) {
                    return $this->formatYouTubeChannel($items[0], 'brand');
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Exception fetching YouTube channel by ID', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get Google Ads accounts.
     *
     * @return array{accounts: array, error: array|null}
     */
    public function getAdsAccounts(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'ads_accounts');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken) {
            Log::info('Fetching Google Ads accounts from API');

            try {
                $developerToken = config('services.google_ads.developer_token');
                if (!$developerToken) {
                    return [
                        'accounts' => [],
                        'error' => [
                            'type' => 'missing_developer_token',
                            'message' => __('google_assets.errors.missing_developer_token'),
                        ],
                    ];
                }

                // List accessible customers
                $response = Http::withToken($accessToken)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->withHeaders(['developer-token' => $developerToken])
                    ->get('https://googleads.googleapis.com/v18/customers:listAccessibleCustomers');

                if (!$response->successful()) {
                    $body = $response->json();
                    $error = $body['error'] ?? [];

                    Log::warning('Google Ads listAccessibleCustomers API failed', [
                        'status' => $response->status(),
                        'body' => $body,
                    ]);

                    // Check for developer token issues
                    if ($response->status() === 401 || $response->status() === 403) {
                        $reason = $error['details'][0]['errors'][0]['errorCode']['authenticationError'] ?? null;
                        if ($reason === 'DEVELOPER_TOKEN_NOT_APPROVED') {
                            return [
                                'accounts' => [],
                                'error' => [
                                    'type' => 'developer_token_not_approved',
                                    'message' => __('google_assets.errors.developer_token_not_approved'),
                                ],
                            ];
                        }
                        if (str_contains($error['message'] ?? '', 'developer token')) {
                            return [
                                'accounts' => [],
                                'error' => [
                                    'type' => 'developer_token_invalid',
                                    'message' => $error['message'] ?? __('google_assets.errors.developer_token_invalid'),
                                ],
                            ];
                        }
                    }

                    if ($response->status() === 501) {
                        return [
                            'accounts' => [],
                            'error' => [
                                'type' => 'api_not_enabled',
                                'message' => $error['message'] ?? __('google_assets.errors.api_not_enabled'),
                            ],
                        ];
                    }

                    return ['accounts' => [], 'error' => null];
                }

                $resourceNames = $response->json('resourceNames', []);
                if (empty($resourceNames)) {
                    return ['accounts' => [], 'error' => null];
                }

                // Fetch details for each customer using parallel requests
                $customerIds = array_map(fn($r) => str_replace('customers/', '', $r), $resourceNames);
                $accounts = [];

                // Use Http::pool for parallel requests (fix N+1)
                $responses = Http::pool(fn ($pool) =>
                    array_map(fn ($customerId) =>
                        $pool->as($customerId)
                            ->withToken($accessToken)
                            ->withHeaders(['developer-token' => $developerToken])
                            ->timeout(self::REQUEST_TIMEOUT)
                            ->post("https://googleads.googleapis.com/v18/customers/{$customerId}/googleAds:searchStream", [
                                'query' => "SELECT customer.id, customer.descriptive_name, customer.currency_code, customer.status FROM customer LIMIT 1",
                            ]),
                        $customerIds
                    )
                );

                foreach ($customerIds as $customerId) {
                    $detailResponse = $responses[$customerId] ?? null;

                    if ($detailResponse && $detailResponse->successful()) {
                        $results = $detailResponse->json();
                        $customer = $results[0]['results'][0]['customer'] ?? null;
                        if ($customer) {
                            $accounts[] = [
                                'id' => $customer['id'] ?? $customerId,
                                'name' => $customer['descriptiveName'] ?? "Account {$customerId}",
                                'descriptive_name' => $customer['descriptiveName'] ?? '',
                                'currency' => $customer['currencyCode'] ?? null,
                                'status' => $customer['status'] ?? 'UNKNOWN',
                            ];
                            continue;
                        }
                    }

                    // If we can't get details, still include the customer ID
                    $accounts[] = [
                        'id' => $customerId,
                        'name' => "Account {$customerId}",
                        'descriptive_name' => '',
                        'currency' => null,
                        'status' => 'UNKNOWN',
                    ];
                }

                Log::info('Google Ads accounts fetched', ['count' => count($accounts)]);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'ad_account', $accounts);

                return ['accounts' => $accounts, 'error' => null];
            } catch (\Exception $e) {
                Log::error('Exception fetching Google Ads accounts', ['error' => $e->getMessage()]);
                return ['accounts' => [], 'error' => ['type' => 'exception', 'message' => $e->getMessage()]];
            }
        });
    }

    /**
     * Get Google Analytics properties (GA4).
     */
    public function getAnalyticsProperties(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'analytics_properties');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken) {
            Log::info('Fetching Google Analytics properties from API');

            try {
                $response = Http::withToken($accessToken)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->get('https://analyticsadmin.googleapis.com/v1beta/accountSummaries');

                if (!$response->successful()) {
                    Log::warning('Analytics accountSummaries API failed', [
                        'status' => $response->status(),
                        'body' => $response->json(),
                    ]);
                    return [];
                }

                $properties = [];
                foreach ($response->json('accountSummaries', []) as $account) {
                    foreach ($account['propertySummaries'] ?? [] as $property) {
                        $properties[] = [
                            'id' => $property['property'] ?? '',
                            'displayName' => $property['displayName'] ?? 'Unknown Property',
                            'accountName' => $account['displayName'] ?? '',
                            'propertyType' => $property['propertyType'] ?? 'PROPERTY_TYPE_ORDINARY',
                        ];
                    }
                }

                Log::info('Analytics properties fetched', ['count' => count($properties)]);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'analytics_property', $properties);

                return $properties;
            } catch (\Exception $e) {
                Log::error('Exception fetching Analytics properties', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Fetch all locations for a Business Profile account with pagination.
     *
     * @return array<int, array> List of location data
     */
    private function fetchAllLocationsForAccount(string $accessToken, string $accountName): array
    {
        $allLocations = [];
        $pageToken = null;
        $maxPages = 10; // Safety limit to prevent infinite loops
        $pageCount = 0;

        do {
            $params = [
                'readMask' => 'name,title,storefrontAddress,categories',
                'pageSize' => 100,
            ];
            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $response = Http::withToken($accessToken)
                ->timeout(self::REQUEST_TIMEOUT)
                ->get("https://mybusinessbusinessinformation.googleapis.com/v1/{$accountName}/locations", $params);

            if (!$response->successful()) {
                Log::warning('Business Profile locations API failed for account', [
                    'account_name' => $accountName,
                    'status' => $response->status(),
                    'error' => $response->json('error.message', 'Unknown'),
                ]);
                break;
            }

            $data = $response->json();
            $locations = $data['locations'] ?? [];
            $allLocations = array_merge($allLocations, $locations);
            $pageToken = $data['nextPageToken'] ?? null;
            $pageCount++;

            if ($pageToken) {
                Log::info('Fetching next page of Business Profile locations', [
                    'account_name' => $accountName,
                    'page' => $pageCount + 1,
                ]);
            }

        } while ($pageToken && $pageCount < $maxPages);

        return $allLocations;
    }

    /**
     * Get Google Business Profile locations.
     *
     * @return array{profiles: array, error: array|null}
     */
    public function getBusinessProfiles(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'business_profiles');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken) {
            Log::info('Fetching Google Business Profiles from API');

            try {
                // First get accounts
                $accountsResponse = Http::withToken($accessToken)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->get('https://mybusinessaccountmanagement.googleapis.com/v1/accounts');

                if (!$accountsResponse->successful()) {
                    $body = $accountsResponse->json();
                    $error = $body['error'] ?? [];

                    Log::warning('Business Profile accounts API failed', [
                        'status' => $accountsResponse->status(),
                        'body' => $body,
                    ]);

                    // Check for quota errors (429)
                    if ($accountsResponse->status() === 429) {
                        $projectNumber = null;
                        foreach ($error['details'] ?? [] as $detail) {
                            if (($detail['@type'] ?? '') === 'type.googleapis.com/google.rpc.ErrorInfo') {
                                $projectNumber = $detail['metadata']['consumer'] ?? null;
                                if ($projectNumber) {
                                    $projectNumber = str_replace('projects/', '', $projectNumber);
                                }
                                break;
                            }
                        }

                        return [
                            'profiles' => [],
                            'error' => [
                                'type' => 'quota_exceeded',
                                'message' => __('google_assets.errors.quota_exceeded'),
                                'project' => $projectNumber,
                            ],
                        ];
                    }

                    return ['profiles' => [], 'error' => null];
                }

                $accounts = $accountsResponse->json('accounts', []);
                if (empty($accounts)) {
                    return ['profiles' => [], 'error' => null];
                }

                // Fetch locations for each account with pagination support
                // Note: Using sequential calls instead of Http::pool to support pagination
                // This ensures ALL locations are fetched, not just the first page
                $profiles = [];
                foreach ($accounts as $account) {
                    $accountName = $account['name'] ?? '';
                    $locations = $this->fetchAllLocationsForAccount($accessToken, $accountName);

                    foreach ($locations as $location) {
                        $address = $location['storefrontAddress'] ?? [];
                        $primaryCategory = $location['categories']['primaryCategory']['displayName'] ?? '';
                        $profiles[] = [
                            'id' => $location['name'] ?? '',
                            'name' => $location['name'] ?? '',  // Resource name for API/checkbox
                            'title' => $location['title'] ?? 'Unknown Location',  // Display name
                            'address' => implode(', ', array_filter([
                                $address['addressLines'][0] ?? '',
                                $address['locality'] ?? '',
                                $address['administrativeArea'] ?? '',
                            ])),
                            'primaryCategory' => $primaryCategory,
                        ];
                    }
                }

                Log::info('Business Profiles fetched', ['count' => count($profiles)]);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'business_profile', $profiles);

                return ['profiles' => $profiles, 'error' => null];
            } catch (\Exception $e) {
                Log::error('Exception fetching Business Profiles', ['error' => $e->getMessage()]);
                return ['profiles' => [], 'error' => ['type' => 'exception', 'message' => $e->getMessage()]];
            }
        });
    }

    /**
     * Get Google Tag Manager containers.
     */
    public function getTagManagerContainers(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'tag_manager');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken) {
            Log::info('Fetching Google Tag Manager containers from API');

            try {
                // First get accounts
                $accountsResponse = Http::withToken($accessToken)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->get('https://www.googleapis.com/tagmanager/v2/accounts');

                if (!$accountsResponse->successful()) {
                    Log::warning('Tag Manager accounts API failed', [
                        'status' => $accountsResponse->status(),
                        'body' => $accountsResponse->json(),
                    ]);
                    return [];
                }

                $accounts = $accountsResponse->json('account', []);
                if (empty($accounts)) {
                    return [];
                }

                // Use Http::pool for parallel container requests (fix N+1)
                $containerResponses = Http::pool(fn ($pool) =>
                    array_map(fn ($account) =>
                        $pool->as($account['path'] ?? 'unknown')
                            ->withToken($accessToken)
                            ->timeout(self::REQUEST_TIMEOUT)
                            ->get("https://www.googleapis.com/tagmanager/v2/{$account['path']}/containers"),
                        $accounts
                    )
                );

                $containers = [];
                foreach ($accounts as $account) {
                    $accountPath = $account['path'] ?? '';
                    $containersResponse = $containerResponses[$accountPath] ?? null;

                    if ($containersResponse && $containersResponse->successful()) {
                        foreach ($containersResponse->json('container', []) as $container) {
                            $containers[] = [
                                'containerId' => $container['containerId'] ?? '',
                                'name' => $container['name'] ?? 'Unknown Container',
                                'publicId' => $container['publicId'] ?? '',
                                'domainName' => $container['domainName'] ?? [],
                                'accountId' => $account['accountId'] ?? '',
                            ];
                        }
                    }
                }

                Log::info('Tag Manager containers fetched', ['count' => count($containers)]);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'tag_manager', $containers);

                return $containers;
            } catch (\Exception $e) {
                Log::error('Exception fetching Tag Manager containers', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Get Google Merchant Center accounts using the NEW Merchant API.
     *
     * The old Content API for Shopping (shoppingcontent.googleapis.com) is deprecated
     * and will be sunset on August 2026. This method uses the new Merchant API
     * (merchantapi.googleapis.com) which is now generally available.
     *
     * @see https://developers.google.com/merchant/api/reference/rest/accounts_v1beta/accounts/list
     * @return array{accounts: array, error: array|null}
     */
    public function getMerchantCenterAccounts(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'merchant_center');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken) {
            Log::info('Fetching Google Merchant Center accounts from NEW Merchant API');

            try {
                // Use NEW Merchant API v1beta endpoint (replaces deprecated Content API for Shopping)
                $response = Http::withToken($accessToken)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->get('https://merchantapi.googleapis.com/accounts/v1beta/accounts');

                if (!$response->successful()) {
                    $body = $response->json();
                    $error = $body['error'] ?? [];

                    Log::warning('Merchant API accounts list failed', [
                        'status' => $response->status(),
                        'body' => $body,
                    ]);

                    // Check for scope insufficient errors (403)
                    if ($response->status() === 403) {
                        $reason = $error['details'][0]['reason'] ?? ($error['status'] ?? '');

                        if ($reason === 'ACCESS_TOKEN_SCOPE_INSUFFICIENT' ||
                            str_contains($error['message'] ?? '', 'scope')) {
                            return [
                                'accounts' => [],
                                'error' => [
                                    'type' => 'scope_insufficient',
                                    'message' => __('google_assets.errors.scope_insufficient'),
                                ],
                            ];
                        }

                        // Check for API not enabled
                        if ($reason === 'SERVICE_DISABLED' ||
                            str_contains($error['message'] ?? '', 'not been used') ||
                            str_contains($error['message'] ?? '', 'disabled')) {
                            $activationUrl = null;
                            foreach ($error['details'] ?? [] as $detail) {
                                if (isset($detail['metadata']['activationUrl'])) {
                                    $activationUrl = $detail['metadata']['activationUrl'];
                                    break;
                                }
                            }
                            return [
                                'accounts' => [],
                                'error' => [
                                    'type' => 'api_not_enabled',
                                    'message' => __('google_assets.errors.api_not_enabled_merchant'),
                                    'activation_url' => $activationUrl,
                                ],
                            ];
                        }
                    }

                    // Check for 401 Unauthorized
                    if ($response->status() === 401) {
                        return [
                            'accounts' => [],
                            'error' => [
                                'type' => 'unauthorized',
                                'message' => __('google_assets.errors.token_expired'),
                            ],
                        ];
                    }

                    return ['accounts' => [], 'error' => null];
                }

                // Parse NEW Merchant API response format
                $accountsData = $response->json('accounts', []);

                if (empty($accountsData)) {
                    Log::info('No Merchant Center accounts found for user');
                    return ['accounts' => [], 'error' => null];
                }

                $accounts = [];
                foreach ($accountsData as $account) {
                    // Extract account ID from resource name (e.g., "accounts/123456789")
                    $resourceName = $account['name'] ?? '';
                    $accountId = str_replace('accounts/', '', $resourceName);

                    $accounts[] = [
                        'id' => $accountId,
                        'name' => $account['accountName'] ?? "Merchant {$accountId}",
                        'websiteUrl' => $account['websiteUri'] ?? '',
                        'accountId' => $accountId,
                        'resourceName' => $resourceName,
                        'languageCode' => $account['languageCode'] ?? '',
                        'timeZone' => $account['timeZone']['id'] ?? '',
                    ];
                }

                Log::info('Merchant Center accounts fetched via NEW Merchant API', ['count' => count($accounts)]);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'merchant_center', $accounts);

                return ['accounts' => $accounts, 'error' => null];
            } catch (\Exception $e) {
                Log::error('Exception fetching Merchant Center accounts', ['error' => $e->getMessage()]);
                return ['accounts' => [], 'error' => ['type' => 'exception', 'message' => $e->getMessage()]];
            }
        });
    }

    /**
     * Get Google Search Console sites.
     */
    public function getSearchConsoleSites(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'search_console');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken) {
            Log::info('Fetching Google Search Console sites from API');

            try {
                $response = Http::withToken($accessToken)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->get('https://www.googleapis.com/webmasters/v3/sites');

                if (!$response->successful()) {
                    Log::warning('Search Console sites API failed', [
                        'status' => $response->status(),
                        'body' => $response->json(),
                    ]);
                    return [];
                }

                $sites = [];
                foreach ($response->json('siteEntry', []) as $site) {
                    $sites[] = [
                        'siteUrl' => $site['siteUrl'] ?? '',
                        'permissionLevel' => $site['permissionLevel'] ?? 'siteUnverifiedUser',
                    ];
                }

                Log::info('Search Console sites fetched', ['count' => count($sites)]);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'search_console', $sites);

                return $sites;
            } catch (\Exception $e) {
                Log::error('Exception fetching Search Console sites', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Get Google Calendars.
     */
    public function getCalendars(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'calendars');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken) {
            Log::info('Fetching Google Calendars from API');

            try {
                $response = Http::withToken($accessToken)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->get('https://www.googleapis.com/calendar/v3/users/me/calendarList', [
                        'minAccessRole' => 'writer',
                    ]);

                if (!$response->successful()) {
                    Log::warning('Calendar list API failed', [
                        'status' => $response->status(),
                        'body' => $response->json(),
                    ]);
                    return [];
                }

                $calendars = [];
                foreach ($response->json('items', []) as $calendar) {
                    $calendars[] = [
                        'id' => $calendar['id'] ?? '',
                        'summary' => $calendar['summary'] ?? 'Unknown Calendar',
                        'description' => $calendar['description'] ?? '',
                        'backgroundColor' => $calendar['backgroundColor'] ?? '#4285f4',
                        'primary' => $calendar['primary'] ?? false,
                        'accessRole' => $calendar['accessRole'] ?? 'reader',
                    ];
                }

                Log::info('Calendars fetched', ['count' => count($calendars)]);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'calendar', $calendars);

                return $calendars;
            } catch (\Exception $e) {
                Log::error('Exception fetching Google Calendars', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Get Google Drive shared drives/folders.
     */
    public function getDriveFolders(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'drive');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken) {
            Log::info('Fetching Google Drive folders from API');

            try {
                $drives = [];

                // Get shared drives
                $drivesResponse = Http::withToken($accessToken)
                    ->timeout(self::REQUEST_TIMEOUT)
                    ->get('https://www.googleapis.com/drive/v3/drives', [
                        'pageSize' => 100,
                    ]);

                if ($drivesResponse->successful()) {
                    foreach ($drivesResponse->json('drives', []) as $drive) {
                        $drives[] = [
                            'id' => $drive['id'] ?? '',
                            'name' => $drive['name'] ?? 'Unknown Drive',
                            'kind' => 'drive#drive',
                        ];
                    }
                }

                // Also get root folders from My Drive if no shared drives
                if (empty($drives)) {
                    $foldersResponse = Http::withToken($accessToken)
                        ->timeout(self::REQUEST_TIMEOUT)
                        ->get('https://www.googleapis.com/drive/v3/files', [
                            'q' => "mimeType='application/vnd.google-apps.folder' and 'root' in parents",
                            'pageSize' => 20,
                            'fields' => 'files(id,name,mimeType)',
                        ]);

                    if ($foldersResponse->successful()) {
                        foreach ($foldersResponse->json('files', []) as $folder) {
                            $drives[] = [
                                'id' => $folder['id'] ?? '',
                                'name' => $folder['name'] ?? 'Unknown Folder',
                                'kind' => 'drive#folder',
                            ];
                        }
                    }
                }

                Log::info('Drive folders fetched', ['count' => count($drives)]);

                // Persist to database for three-tier caching
                $this->persistAssets($connectionId, 'drive', $drives);

                return $drives;
            } catch (\Exception $e) {
                Log::error('Exception fetching Google Drive folders', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Clear all cached assets for a connection.
     */
    public function refreshAll(string $connectionId): void
    {
        $assetTypes = [
            'youtube_channels',
            'ads_accounts',
            'analytics_properties',
            'business_profiles',
            'tag_manager',
            'merchant_center',
            'search_console',
            'calendars',
            'drive',
        ];

        foreach ($assetTypes as $type) {
            Cache::forget($this->getCacheKey($connectionId, $type));
        }

        Log::info('Cache cleared for all Google assets', ['connection_id' => $connectionId]);
    }

    /**
     * Get cache status for all asset types.
     */
    public function getCacheStatus(string $connectionId): array
    {
        $assetTypes = [
            'youtube_channels',
            'ads_accounts',
            'analytics_properties',
            'business_profiles',
            'tag_manager',
            'merchant_center',
            'search_console',
            'calendars',
            'drive',
        ];

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
     * @param string $assetType Asset type (youtube_channel, ad_account, etc.)
     * @param array $assets Array of asset data
     */
    protected function persistAssets(string $connectionId, string $assetType, array $assets): void
    {
        if (!$this->repository || empty($assets)) {
            return;
        }

        try {
            $count = $this->repository->bulkUpsert('google', $assetType, $assets, $connectionId);

            // Record org access if org_id is set
            if ($this->orgId) {
                foreach ($assets as $assetData) {
                    $assetId = $this->extractAssetId($assetData, $assetType);
                    if (!$assetId) {
                        continue;
                    }

                    $asset = $this->repository->findOrCreate(
                        'google',
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
                            'roles' => $assetData['roles'] ?? [],
                        ]
                    );
                }
            }

            Log::debug("Persisted Google {$assetType} assets to database", [
                'connection_id' => $connectionId,
                'count' => $count,
                'org_id' => $this->orgId,
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to persist Google {$assetType} assets", [
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
        // Common ID field
        if (isset($data['id'])) {
            return (string) $data['id'];
        }

        // Asset-type specific extractions
        return match ($assetType) {
            'youtube_channel' => $data['channelId'] ?? null,
            'ad_account' => $data['customerId'] ?? $data['account_id'] ?? null,
            'analytics_property' => $data['property'] ?? null,
            'business_profile' => $data['name'] ?? null,
            'tag_manager' => $data['containerId'] ?? null,
            'merchant_center' => $data['accountId'] ?? null,
            'search_console' => $data['siteUrl'] ?? null,
            'calendar' => $data['calendarId'] ?? null,
            'drive' => $data['driveId'] ?? null,
            default => null,
        };
    }

    /**
     * Infer access types from asset data.
     */
    protected function inferAccessTypes(array $data): array
    {
        $types = ['read'];

        // Check permission level (Search Console)
        if (isset($data['permissionLevel'])) {
            $level = $data['permissionLevel'];
            if (in_array($level, ['siteOwner', 'siteFullUser'])) {
                $types[] = 'write';
                $types[] = 'admin';
            }
        }

        // Check access role (Calendar, Drive)
        if (isset($data['accessRole'])) {
            $role = strtolower($data['accessRole']);
            if ($role === 'owner') {
                $types = array_merge($types, ['write', 'admin']);
            } elseif ($role === 'writer') {
                $types[] = 'write';
            }
        }

        // Check status (Ads accounts)
        if (isset($data['status'])) {
            $status = $data['status'];
            if ($status === 'ENABLED') {
                $types[] = 'write';
            }
        }

        // Check for primary calendar
        if (isset($data['primary']) && $data['primary']) {
            $types[] = 'admin';
        }

        return array_unique($types);
    }
}
