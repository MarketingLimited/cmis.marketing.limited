<?php

namespace App\Services\Platform;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for fetching Meta (Facebook/Instagram) Business Manager assets.
 *
 * Features:
 * - Cursor-based pagination to fetch unlimited assets
 * - Redis caching with 1-hour TTL
 * - Parallel-friendly design for AJAX loading
 *
 * @see https://developers.facebook.com/docs/graph-api/using-graph-api/#paging
 */
class MetaAssetsService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const API_VERSION = 'v21.0';
    private const BASE_URL = 'https://graph.facebook.com';
    private const THREADS_BASE_URL = 'https://graph.threads.net';
    private const MAX_PAGES = 3; // Safety limit: 3 pages x 100 items = 300 max (reduced from 50 to prevent rate limiting)
    private const ITEMS_PER_PAGE = 100;
    private const REQUEST_TIMEOUT = 30;
    private const MAX_BUSINESSES = 10; // Limit businesses to prevent API exhaustion
    private const DELAY_BETWEEN_REQUESTS_MS = 100; // 100ms delay between API calls

    /**
     * Core pagination helper - fetches ALL pages from Meta API.
     * Follows paging.next cursor until all data is retrieved.
     */
    private function fetchAllPages(string $url, string $accessToken, array $params = []): array
    {
        $allData = [];
        $params['access_token'] = $accessToken;
        $params['limit'] = self::ITEMS_PER_PAGE;

        $nextUrl = $url . '?' . http_build_query($params);
        $pageCount = 0;

        while ($nextUrl && $pageCount < self::MAX_PAGES) {
            // Add delay between requests to prevent rate limiting
            if ($pageCount > 0) {
                usleep(self::DELAY_BETWEEN_REQUESTS_MS * 1000);
            }

            $response = Http::timeout(self::REQUEST_TIMEOUT)->get($nextUrl);

            if (!$response->successful()) {
                $error = $response->json('error', []);

                // Check for rate limit error
                if (($error['code'] ?? 0) === 4 || ($error['code'] ?? 0) === 17) {
                    Log::warning('Meta API rate limit hit, stopping pagination', [
                        'url' => preg_replace('/access_token=[^&]+/', 'access_token=***', $nextUrl),
                        'page' => $pageCount + 1,
                        'items_collected' => count($allData),
                    ]);
                    break;
                }

                Log::warning('Meta API pagination failed', [
                    'url' => preg_replace('/access_token=[^&]+/', 'access_token=***', $nextUrl),
                    'page' => $pageCount + 1,
                    'error' => $error,
                ]);
                break;
            }

            $data = $response->json();
            $newItems = $data['data'] ?? [];
            $allData = array_merge($allData, $newItems);

            Log::debug('Meta API pagination progress', [
                'page' => $pageCount + 1,
                'items_this_page' => count($newItems),
                'total_items' => count($allData),
            ]);

            // Follow pagination cursor
            $nextUrl = $data['paging']['next'] ?? null;
            $pageCount++;
        }

        if ($pageCount >= self::MAX_PAGES) {
            Log::info('Meta API pagination reached limit', [
                'max_pages' => self::MAX_PAGES,
                'total_items' => count($allData),
            ]);
        }

        return $allData;
    }

    /**
     * Get cache key for a specific asset type and connection.
     */
    private function getCacheKey(string $connectionId, string $assetType): string
    {
        return "meta_assets:{$connectionId}:{$assetType}";
    }

    /**
     * Fetch ALL user assets in ONE API call (pages + instagram).
     * Uses field expansion to get Instagram details embedded in the pages response.
     */
    private function getAllUserAssets(string $accessToken, string $connectionId): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'all_user_assets');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken) {
            Log::info('Fetching ALL user assets from Meta API (single request with field expansion)');

            try {
                // ONE API call to get pages with embedded Instagram accounts
                $response = Http::timeout(self::REQUEST_TIMEOUT)->get(
                    self::BASE_URL . '/' . self::API_VERSION . '/me/accounts',
                    [
                        'access_token' => $accessToken,
                        'fields' => implode(',', [
                            'id',
                            'name',
                            'category',
                            'picture{url}',
                            'access_token',
                            'instagram_business_account{id,username,name,profile_picture_url,followers_count,media_count}',
                        ]),
                        'limit' => 100,
                    ]
                );

                if (!$response->successful()) {
                    Log::warning('Failed to fetch user assets', [
                        'error' => $response->json('error', []),
                    ]);
                    return ['pages' => [], 'instagram' => []];
                }

                $rawPages = $response->json('data', []);

                // Parse pages and extract Instagram accounts
                $pages = [];
                $instagramAccounts = [];

                foreach ($rawPages as $page) {
                    // Store page info
                    $pages[] = [
                        'id' => $page['id'],
                        'name' => $page['name'] ?? 'Unknown Page',
                        'category' => $page['category'] ?? null,
                        'picture' => $page['picture']['data']['url'] ?? null,
                        'has_instagram' => isset($page['instagram_business_account']),
                        'instagram_id' => $page['instagram_business_account']['id'] ?? null,
                    ];

                    // Extract Instagram account if present
                    if (isset($page['instagram_business_account'])) {
                        $ig = $page['instagram_business_account'];
                        $existingIds = array_column($instagramAccounts, 'id');
                        if (!in_array($ig['id'], $existingIds)) {
                            $instagramAccounts[] = [
                                'id' => $ig['id'],
                                'username' => $ig['username'] ?? null,
                                'name' => $ig['name'] ?? $ig['username'] ?? 'Unknown',
                                'profile_picture' => $ig['profile_picture_url'] ?? null,
                                'followers_count' => $ig['followers_count'] ?? 0,
                                'media_count' => $ig['media_count'] ?? 0,
                                'connected_page_id' => $page['id'],
                                'connected_page_name' => $page['name'] ?? 'Unknown Page',
                            ];
                        }
                    }
                }

                Log::info('ALL user assets fetched in single API call', [
                    'pages' => count($pages),
                    'instagram_accounts' => count($instagramAccounts),
                    'api_calls' => 1,
                ]);

                return [
                    'pages' => $pages,
                    'instagram' => $instagramAccounts,
                ];
            } catch (\Exception $e) {
                Log::error('Failed to fetch user assets', ['error' => $e->getMessage()]);
                return ['pages' => [], 'instagram' => []];
            }
        });
    }

    /**
     * Get Facebook Pages (uses shared cache from getAllUserAssets - 0 extra API calls).
     */
    public function getPages(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget($this->getCacheKey($connectionId, 'all_user_assets'));
        }

        $allAssets = $this->getAllUserAssets($accessToken, $connectionId);
        return $allAssets['pages'] ?? [];
    }

    /**
     * Get Instagram Business accounts (uses shared cache from getAllUserAssets - 0 extra API calls).
     */
    public function getInstagramAccounts(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget($this->getCacheKey($connectionId, 'all_user_assets'));
        }

        $allAssets = $this->getAllUserAssets($accessToken, $connectionId);
        return $allAssets['instagram'] ?? [];
    }

    /**
     * Get Threads accounts.
     * Note: Threads API requires separate OAuth with threads_* scopes.
     * This method performs a quick test first to fail fast if scopes aren't available.
     */
    public function getThreadsAccounts(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'threads');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken) {
            // Quick test if Threads API is accessible with this token (5s timeout)
            // Threads requires separate OAuth with threads_* scopes
            try {
                $testResponse = Http::timeout(5)->get(
                    self::THREADS_BASE_URL . '/v1.0/me',
                    ['access_token' => $accessToken]
                );

                if (!$testResponse->successful()) {
                    Log::debug('Threads API not accessible - requires separate OAuth', [
                        'status' => $testResponse->status(),
                    ]);
                    return [];
                }
            } catch (\Exception $e) {
                Log::debug('Threads API unavailable - fast fail', [
                    'error' => $e->getMessage(),
                ]);
                return [];
            }

            // Test passed - token has threads_* scopes, proceed with fetching accounts
            Log::info('Threads API accessible, fetching accounts');

            $threadsAccounts = [];
            $instagramAccounts = $this->getInstagramAccounts($connectionId, $accessToken, false);

            foreach ($instagramAccounts as $ig) {
                $igId = $ig['id'] ?? null;
                if (!$igId) continue;

                try {
                    $response = Http::timeout(10)->get(
                        self::THREADS_BASE_URL . "/v1.0/{$igId}",
                        [
                            'access_token' => $accessToken,
                            'fields' => 'id,username,name,threads_profile_picture_url,threads_biography',
                        ]
                    );

                    if ($response->successful()) {
                        $threadsData = $response->json();
                        if (!empty($threadsData['id'])) {
                            $threadsAccounts[] = [
                                'id' => $threadsData['id'],
                                'username' => $threadsData['username'] ?? $ig['username'] ?? null,
                                'name' => $threadsData['name'] ?? $ig['name'] ?? 'Threads Account',
                                'profile_picture' => $threadsData['threads_profile_picture_url'] ?? $ig['profile_picture'] ?? null,
                                'biography' => $threadsData['threads_biography'] ?? null,
                                'connected_instagram' => $ig['username'] ?? null,
                                'instagram_id' => $igId,
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug('Failed to fetch Threads account', [
                        'instagram_id' => $igId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Threads accounts fetched', ['count' => count($threadsAccounts)]);
            return $threadsAccounts;
        });
    }

    /**
     * Fetch ALL ad account assets in ONE API call (ad accounts + pixels + custom conversions).
     * Uses field expansion to get nested data in a single request.
     */
    private function getAllAdAccountAssets(string $accessToken, string $connectionId): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'all_adaccount_assets');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken) {
            Log::info('Fetching ALL ad account assets from Meta API (single request with field expansion)');

            try {
                // ONE API call to get ad accounts with embedded pixels and custom conversions
                $response = Http::timeout(self::REQUEST_TIMEOUT)->get(
                    self::BASE_URL . '/' . self::API_VERSION . '/me/adaccounts',
                    [
                        'access_token' => $accessToken,
                        'fields' => implode(',', [
                            'id',
                            'name',
                            'account_id',
                            'account_status',
                            'disable_reason',
                            'currency',
                            'timezone_name',
                            'timezone_id',
                            'business_name',
                            'business',
                            'spend_cap',
                            'amount_spent',
                            'balance',
                            'funding_source_details',
                            'created_time',
                            'capabilities',
                            'is_prepay_account',
                            'min_daily_budget',
                            'adspixels.limit(50){id,name,creation_time,last_fired_time}',
                            'customconversions.limit(50){id,name,description,custom_event_type,rule,pixel,creation_time,last_fired_time,is_archived}',
                        ]),
                        'limit' => 100,
                    ]
                );

                if (!$response->successful()) {
                    Log::warning('Failed to fetch ad account assets', [
                        'error' => $response->json('error', []),
                    ]);
                    return ['ad_accounts' => [], 'pixels' => [], 'custom_conversions' => []];
                }

                $rawAccounts = $response->json('data', []);

                // Parse ad accounts and extract pixels + custom conversions
                $adAccounts = [];
                $pixels = [];
                $customConversions = [];

                foreach ($rawAccounts as $account) {
                    $statusCode = $account['account_status'] ?? 0;
                    $disableReason = $account['disable_reason'] ?? null;
                    $accountId = $account['id'];
                    $accountName = $account['name'] ?? 'Unknown';

                    // Store ad account info
                    $adAccounts[] = [
                        'id' => $accountId,
                        'account_id' => $account['account_id'] ?? str_replace('act_', '', $accountId),
                        'name' => $accountName,
                        'business_name' => $account['business_name'] ?? null,
                        'business_id' => $account['business']['id'] ?? null,
                        'currency' => $account['currency'] ?? 'USD',
                        'timezone' => $account['timezone_name'] ?? 'UTC',
                        'timezone_id' => $account['timezone_id'] ?? null,
                        'status' => $this->getAccountStatusLabel($statusCode),
                        'status_code' => $statusCode,
                        'disable_reason' => $disableReason ? $this->getDisableReasonLabel($disableReason) : null,
                        'spend_cap' => $account['spend_cap'] ?? null,
                        'amount_spent' => $account['amount_spent'] ?? '0',
                        'balance' => $account['balance'] ?? '0',
                        'is_prepay' => $account['is_prepay_account'] ?? false,
                        'min_daily_budget' => $account['min_daily_budget'] ?? null,
                        'capabilities' => $account['capabilities'] ?? [],
                        'funding_source' => $account['funding_source_details']['display_string'] ?? null,
                        'created_at' => $account['created_time'] ?? null,
                        'can_create_ads' => $statusCode === 1,
                    ];

                    // Extract pixels
                    foreach ($account['adspixels']['data'] ?? [] as $pixel) {
                        $existingIds = array_column($pixels, 'id');
                        if (!in_array($pixel['id'], $existingIds)) {
                            $pixels[] = [
                                'id' => $pixel['id'],
                                'name' => $pixel['name'] ?? 'Unnamed Pixel',
                                'ad_account_id' => $accountId,
                                'ad_account_name' => $accountName,
                                'creation_time' => $pixel['creation_time'] ?? null,
                                'last_fired_time' => $pixel['last_fired_time'] ?? null,
                            ];
                        }
                    }

                    // Extract custom conversions
                    foreach ($account['customconversions']['data'] ?? [] as $conversion) {
                        $existingIds = array_column($customConversions, 'id');
                        if (!in_array($conversion['id'], $existingIds)) {
                            $customConversions[] = [
                                'id' => $conversion['id'],
                                'name' => $conversion['name'] ?? 'Unnamed Conversion',
                                'description' => $conversion['description'] ?? null,
                                'custom_event_type' => $conversion['custom_event_type'] ?? null,
                                'rule' => $conversion['rule'] ?? null,
                                'pixel_id' => $conversion['pixel']['id'] ?? null,
                                'ad_account_id' => $accountId,
                                'ad_account_name' => $accountName,
                                'creation_time' => $conversion['creation_time'] ?? null,
                                'last_fired_time' => $conversion['last_fired_time'] ?? null,
                                'is_archived' => $conversion['is_archived'] ?? false,
                            ];
                        }
                    }
                }

                Log::info('ALL ad account assets fetched in single API call', [
                    'ad_accounts' => count($adAccounts),
                    'pixels' => count($pixels),
                    'custom_conversions' => count($customConversions),
                    'api_calls' => 1,
                ]);

                return [
                    'ad_accounts' => $adAccounts,
                    'pixels' => $pixels,
                    'custom_conversions' => $customConversions,
                ];
            } catch (\Exception $e) {
                Log::error('Failed to fetch ad account assets', ['error' => $e->getMessage()]);
                return ['ad_accounts' => [], 'pixels' => [], 'custom_conversions' => []];
            }
        });
    }

    /**
     * Get Meta Ad Accounts (uses shared cache from getAllAdAccountAssets - 0 extra API calls).
     */
    public function getAdAccounts(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget($this->getCacheKey($connectionId, 'all_adaccount_assets'));
        }

        $allAssets = $this->getAllAdAccountAssets($accessToken, $connectionId);
        return $allAssets['ad_accounts'] ?? [];
    }

    /**
     * Get Meta Pixels (uses shared cache from getAllAdAccountAssets - 0 extra API calls).
     */
    public function getPixels(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget($this->getCacheKey($connectionId, 'all_adaccount_assets'));
        }

        $allAssets = $this->getAllAdAccountAssets($accessToken, $connectionId);
        return $allAssets['pixels'] ?? [];
    }

    /**
     * Get Product Catalogs (uses shared cache from getAllBusinessAssets - 0 extra API calls).
     */
    public function getCatalogs(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget($this->getCacheKey($connectionId, 'all_business_assets'));
        }

        $allAssets = $this->getAllBusinessAssets($accessToken, $connectionId);
        return $allAssets['catalogs'] ?? [];
    }

    /**
     * Get WhatsApp Business Accounts (uses shared cache from getAllBusinessAssets - 0 extra API calls).
     */
    public function getWhatsappAccounts(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget($this->getCacheKey($connectionId, 'all_business_assets'));
        }

        $allAssets = $this->getAllBusinessAssets($accessToken, $connectionId);
        return $allAssets['whatsapp'] ?? [];
    }

    /**
     * Fetch ALL business assets in ONE API call (businesses + catalogs + whatsapp).
     * This is the core method that minimizes API calls by using field expansion.
     * All business-related data is fetched together and cached.
     */
    private function getAllBusinessAssets(string $accessToken, string $connectionId): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'all_business_assets');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken) {
            Log::info('Fetching ALL business assets from Meta API (single request with full field expansion)');

            try {
                // ONE API call to get everything: businesses + catalogs + whatsapp
                $response = Http::timeout(self::REQUEST_TIMEOUT)->get(
                    self::BASE_URL . '/' . self::API_VERSION . '/me/businesses',
                    [
                        'access_token' => $accessToken,
                        'fields' => implode(',', [
                            'id',
                            'name',
                            'verification_status',
                            'owned_product_catalogs.limit(100){id,name,product_count,vertical}',
                            'client_product_catalogs.limit(100){id,name,product_count,vertical}',
                            'owned_whatsapp_business_accounts.limit(100){id,name,phone_numbers{id,display_phone_number,verified_name,quality_rating,code_verification_status}}',
                            'offline_conversion_data_sets.limit(100){id,name,description,upload_rate,duplicate_entries,match_rate_approx,event_stats,data_origin}',
                        ]),
                        'limit' => 100,
                    ]
                );

                if (!$response->successful()) {
                    Log::warning('Failed to fetch business assets', [
                        'error' => $response->json('error', []),
                    ]);
                    return ['businesses' => [], 'catalogs' => [], 'whatsapp' => [], 'offline_event_sets' => []];
                }

                $rawBusinesses = $response->json('data', []);

                // Parse and organize all data
                $businesses = [];
                $catalogs = [];
                $whatsappAccounts = [];
                $offlineEventSets = [];

                foreach ($rawBusinesses as $business) {
                    $businessId = $business['id'] ?? null;
                    $businessName = $business['name'] ?? 'Unknown Business';

                    // Store business info
                    $businesses[] = [
                        'id' => $businessId,
                        'name' => $businessName,
                        'verification_status' => $business['verification_status'] ?? null,
                    ];

                    // Extract owned catalogs
                    foreach ($business['owned_product_catalogs']['data'] ?? [] as $catalog) {
                        $existingIds = array_column($catalogs, 'id');
                        if (!in_array($catalog['id'], $existingIds)) {
                            $catalogs[] = [
                                'id' => $catalog['id'],
                                'name' => $catalog['name'] ?? 'Unnamed Catalog',
                                'product_count' => $catalog['product_count'] ?? 0,
                                'vertical' => $catalog['vertical'] ?? 'commerce',
                                'business_id' => $businessId,
                                'business_name' => $businessName,
                            ];
                        }
                    }

                    // Extract client catalogs
                    foreach ($business['client_product_catalogs']['data'] ?? [] as $catalog) {
                        $existingIds = array_column($catalogs, 'id');
                        if (!in_array($catalog['id'], $existingIds)) {
                            $catalogs[] = [
                                'id' => $catalog['id'],
                                'name' => $catalog['name'] ?? 'Unnamed Catalog',
                                'product_count' => $catalog['product_count'] ?? 0,
                                'vertical' => $catalog['vertical'] ?? 'commerce',
                                'business_id' => $businessId,
                                'business_name' => $businessName,
                                'is_client_catalog' => true,
                            ];
                        }
                    }

                    // Extract WhatsApp accounts
                    foreach ($business['owned_whatsapp_business_accounts']['data'] ?? [] as $waba) {
                        $wabaId = $waba['id'] ?? null;
                        $wabaName = $waba['name'] ?? 'Unnamed WABA';

                        foreach ($waba['phone_numbers']['data'] ?? [] as $phone) {
                            $existingIds = array_column($whatsappAccounts, 'id');
                            if (!in_array($phone['id'], $existingIds)) {
                                $whatsappAccounts[] = [
                                    'id' => $phone['id'],
                                    'display_phone_number' => $phone['display_phone_number'] ?? '',
                                    'verified_name' => $phone['verified_name'] ?? '',
                                    'quality_rating' => $phone['quality_rating'] ?? null,
                                    'code_verification_status' => $phone['code_verification_status'] ?? null,
                                    'waba_id' => $wabaId,
                                    'waba_name' => $wabaName,
                                    'business_id' => $businessId,
                                    'business_name' => $businessName,
                                ];
                            }
                        }
                    }

                    // Extract Offline Event Sets
                    foreach ($business['offline_conversion_data_sets']['data'] ?? [] as $eventSet) {
                        $existingIds = array_column($offlineEventSets, 'id');
                        if (!in_array($eventSet['id'], $existingIds)) {
                            $offlineEventSets[] = [
                                'id' => $eventSet['id'],
                                'name' => $eventSet['name'] ?? 'Unnamed Event Set',
                                'description' => $eventSet['description'] ?? null,
                                'upload_rate' => $eventSet['upload_rate'] ?? null,
                                'duplicate_entries' => $eventSet['duplicate_entries'] ?? 0,
                                'match_rate_approx' => $eventSet['match_rate_approx'] ?? null,
                                'event_stats' => $eventSet['event_stats'] ?? null,
                                'data_origin' => $eventSet['data_origin'] ?? null,
                                'business_id' => $businessId,
                                'business_name' => $businessName,
                            ];
                        }
                    }
                }

                Log::info('ALL business assets fetched in single API call', [
                    'businesses' => count($businesses),
                    'catalogs' => count($catalogs),
                    'whatsapp_accounts' => count($whatsappAccounts),
                    'offline_event_sets' => count($offlineEventSets),
                    'api_calls' => 1,
                ]);

                return [
                    'businesses' => $businesses,
                    'catalogs' => $catalogs,
                    'whatsapp' => $whatsappAccounts,
                    'offline_event_sets' => $offlineEventSets,
                ];
            } catch (\Exception $e) {
                Log::error('Failed to fetch business assets', ['error' => $e->getMessage()]);
                return ['businesses' => [], 'catalogs' => [], 'whatsapp' => [], 'offline_event_sets' => []];
            }
        });
    }

    /**
     * Get businesses (uses shared cache from getAllBusinessAssets).
     */
    private function getBusinesses(string $accessToken, string $connectionId): array
    {
        $allAssets = $this->getAllBusinessAssets($accessToken, $connectionId);
        return $allAssets['businesses'] ?? [];
    }

    /**
     * Get Custom Conversions (uses shared cache from getAllAdAccountAssets - 0 extra API calls).
     */
    public function getCustomConversions(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget($this->getCacheKey($connectionId, 'all_adaccount_assets'));
        }

        $allAssets = $this->getAllAdAccountAssets($accessToken, $connectionId);
        return $allAssets['custom_conversions'] ?? [];
    }

    /**
     * Get Offline Event Sets (uses shared cache from getAllBusinessAssets - 0 extra API calls).
     */
    public function getOfflineEventSets(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget($this->getCacheKey($connectionId, 'all_business_assets'));
        }

        $allAssets = $this->getAllBusinessAssets($accessToken, $connectionId);
        return $allAssets['offline_event_sets'] ?? [];
    }

    /**
     * Clear all cached assets for a connection.
     */
    public function refreshAll(string $connectionId): void
    {
        // Clear the 3 super-query caches (primary optimization)
        $superCaches = [
            'all_user_assets',      // Pages + Instagram
            'all_adaccount_assets', // Ad Accounts + Pixels + Custom Conversions
            'all_business_assets',  // Businesses + Catalogs + WhatsApp + Offline Event Sets
        ];

        // Also clear legacy individual caches (for backwards compatibility)
        $legacyCaches = [
            'pages', 'instagram', 'threads', 'ad_accounts', 'pixels', 'catalogs', 'whatsapp', 'businesses',
            'custom_conversions', 'offline_event_sets'
        ];

        $allCaches = array_merge($superCaches, $legacyCaches);

        foreach ($allCaches as $type) {
            Cache::forget($this->getCacheKey($connectionId, $type));
        }

        Log::info('Cache cleared for all Meta assets', ['connection_id' => $connectionId]);
    }

    /**
     * Get cache status for all asset types.
     */
    public function getCacheStatus(string $connectionId): array
    {
        // Super-query caches (optimized - 3 API calls total)
        $superCaches = [
            'all_user_assets',      // Pages + Instagram (1 API call)
            'all_adaccount_assets', // Ad Accounts + Pixels + Custom Conversions (1 API call)
            'all_business_assets',  // Businesses + Catalogs + WhatsApp + Offline Event Sets (1 API call)
        ];

        // Individual asset type caches (derived from super-caches)
        $assetTypes = [
            'pages', 'instagram', 'threads', 'ad_accounts', 'pixels', 'catalogs', 'whatsapp',
            'custom_conversions', 'offline_event_sets'
        ];

        $allCacheTypes = array_merge($superCaches, $assetTypes);
        $status = [];

        foreach ($allCacheTypes as $type) {
            $cacheKey = $this->getCacheKey($connectionId, $type);
            $status[$type] = [
                'cached' => Cache::has($cacheKey),
                'key' => $cacheKey,
            ];
        }

        return $status;
    }

    /**
     * Get label for Meta ad account status.
     */
    private function getAccountStatusLabel(int $status): string
    {
        return match ($status) {
            1 => 'Active',
            2 => 'Disabled',
            3 => 'Unsettled',
            7 => 'Pending Risk Review',
            8 => 'Pending Settlement',
            9 => 'In Grace Period',
            100 => 'Pending Closure',
            101 => 'Closed',
            201 => 'Any Active',
            202 => 'Any Closed',
            default => 'Unknown',
        };
    }

    /**
     * Get label for Meta ad account disable reason.
     */
    private function getDisableReasonLabel(int $reason): string
    {
        return match ($reason) {
            0 => 'None',
            1 => 'Ads violate policy',
            2 => 'Ads and business violate policy',
            3 => 'Unusual activity',
            4 => 'Business information incomplete',
            5 => 'Chargeback',
            6 => 'Overdue balance',
            7 => 'Gray area and abuse',
            8 => 'Risky payment pattern',
            9 => 'Risk assessment',
            10 => 'Business verification issues',
            default => 'Unknown',
        };
    }
}
