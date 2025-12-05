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
     * Get Facebook Pages (unlimited, with pagination).
     */
    public function getPages(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'pages');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken) {
            Log::info('Fetching Facebook Pages from Meta API (with pagination)');

            $rawPages = $this->fetchAllPages(
                self::BASE_URL . '/' . self::API_VERSION . '/me/accounts',
                $accessToken,
                [
                    'fields' => 'id,name,category,picture{url},access_token,instagram_business_account',
                ]
            );

            $pages = array_map(function ($page) {
                return [
                    'id' => $page['id'],
                    'name' => $page['name'] ?? 'Unknown Page',
                    'category' => $page['category'] ?? null,
                    'picture' => $page['picture']['data']['url'] ?? null,
                    'has_instagram' => isset($page['instagram_business_account']),
                    'instagram_id' => $page['instagram_business_account']['id'] ?? null,
                ];
            }, $rawPages);

            Log::info('Facebook Pages fetched', ['count' => count($pages)]);
            return $pages;
        });
    }

    /**
     * Get Instagram Business accounts (unlimited, with pagination).
     */
    public function getInstagramAccounts(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'instagram');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken) {
            Log::info('Fetching Instagram accounts from Meta API');

            $instagramAccounts = [];

            // First get pages with Instagram
            $pages = $this->getPages($connectionId, $accessToken, false);

            // Fetch Instagram details for each page with connected IG
            foreach ($pages as $page) {
                if (!empty($page['instagram_id'])) {
                    try {
                        $response = Http::timeout(15)->get(
                            self::BASE_URL . '/' . self::API_VERSION . '/' . $page['instagram_id'],
                            [
                                'access_token' => $accessToken,
                                'fields' => 'id,username,name,profile_picture_url,followers_count,media_count',
                            ]
                        );

                        if ($response->successful()) {
                            $igData = $response->json();
                            $instagramAccounts[] = [
                                'id' => $igData['id'],
                                'username' => $igData['username'] ?? null,
                                'name' => $igData['name'] ?? $igData['username'] ?? 'Unknown',
                                'profile_picture' => $igData['profile_picture_url'] ?? null,
                                'followers_count' => $igData['followers_count'] ?? 0,
                                'media_count' => $igData['media_count'] ?? 0,
                                'connected_page_id' => $page['id'],
                                'connected_page_name' => $page['name'],
                            ];
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to fetch Instagram account details', [
                            'instagram_id' => $page['instagram_id'],
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Also fetch direct Instagram accounts via pagination
            $directAccounts = $this->fetchAllPages(
                self::BASE_URL . '/' . self::API_VERSION . '/me/instagram_accounts',
                $accessToken,
                [
                    'fields' => 'id,username,name,profile_picture_url,followers_count',
                ]
            );

            foreach ($directAccounts as $igAccount) {
                // Avoid duplicates
                $existingIds = array_column($instagramAccounts, 'id');
                if (!in_array($igAccount['id'], $existingIds)) {
                    $instagramAccounts[] = [
                        'id' => $igAccount['id'],
                        'username' => $igAccount['username'] ?? null,
                        'name' => $igAccount['name'] ?? $igAccount['username'] ?? 'Unknown',
                        'profile_picture' => $igAccount['profile_picture_url'] ?? null,
                        'followers_count' => $igAccount['followers_count'] ?? 0,
                        'media_count' => 0,
                        'connected_page_id' => null,
                        'connected_page_name' => null,
                    ];
                }
            }

            Log::info('Instagram accounts fetched', ['count' => count($instagramAccounts)]);
            return $instagramAccounts;
        });
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
     * Get Meta Ad Accounts (unlimited, with pagination).
     */
    public function getAdAccounts(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'ad_accounts');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken) {
            Log::info('Fetching Ad Accounts from Meta API (with pagination)');

            $fields = implode(',', [
                'id', 'name', 'account_id', 'account_status', 'disable_reason',
                'currency', 'timezone_name', 'timezone_id', 'business_name', 'business',
                'spend_cap', 'amount_spent', 'balance', 'owner', 'funding_source',
                'funding_source_details', 'created_time', 'capabilities',
                'is_prepay_account', 'min_campaign_group_spend_cap', 'min_daily_budget',
            ]);

            $rawAccounts = $this->fetchAllPages(
                self::BASE_URL . '/' . self::API_VERSION . '/me/adaccounts',
                $accessToken,
                ['fields' => $fields]
            );

            $accounts = array_map(function ($account) {
                $statusCode = $account['account_status'] ?? 0;
                $disableReason = $account['disable_reason'] ?? null;

                return [
                    'id' => $account['id'],
                    'account_id' => $account['account_id'] ?? str_replace('act_', '', $account['id']),
                    'name' => $account['name'] ?? 'Unknown',
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
            }, $rawAccounts);

            Log::info('Ad Accounts fetched', ['count' => count($accounts)]);
            return $accounts;
        });
    }

    /**
     * Get Meta Pixels (fetches from all ad accounts with pagination).
     */
    public function getPixels(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'pixels');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken) {
            Log::info('Fetching Meta Pixels from all Ad Accounts');

            $pixels = [];
            $adAccounts = $this->getAdAccounts($connectionId, $accessToken, false);

            foreach ($adAccounts as $account) {
                $accountId = $account['id'] ?? null;
                if (!$accountId) continue;

                $formattedAccountId = str_starts_with($accountId, 'act_') ? $accountId : 'act_' . $accountId;

                try {
                    $pixelData = $this->fetchAllPages(
                        self::BASE_URL . '/' . self::API_VERSION . "/{$formattedAccountId}/adspixels",
                        $accessToken,
                        ['fields' => 'id,name,creation_time,last_fired_time']
                    );

                    foreach ($pixelData as $pixel) {
                        $existingIds = array_column($pixels, 'id');
                        if (!in_array($pixel['id'], $existingIds)) {
                            $pixels[] = [
                                'id' => $pixel['id'],
                                'name' => $pixel['name'] ?? 'Unnamed Pixel',
                                'ad_account_id' => $account['id'],
                                'ad_account_name' => $account['name'] ?? 'Unknown',
                                'creation_time' => $pixel['creation_time'] ?? null,
                                'last_fired_time' => $pixel['last_fired_time'] ?? null,
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch pixels for ad account', [
                        'account_id' => $formattedAccountId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Meta Pixels fetched', ['count' => count($pixels)]);
            return $pixels;
        });
    }

    /**
     * Get Product Catalogs (from all businesses with pagination).
     */
    public function getCatalogs(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'catalogs');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken) {
            Log::info('Fetching Product Catalogs from Meta API');

            $catalogs = [];
            $allBusinesses = $this->getBusinesses($accessToken, $connectionId);

            // Limit businesses to prevent API exhaustion
            $businesses = array_slice($allBusinesses, 0, self::MAX_BUSINESSES);
            if (count($allBusinesses) > self::MAX_BUSINESSES) {
                Log::info('Limited businesses for catalog fetch', [
                    'total' => count($allBusinesses),
                    'limited_to' => self::MAX_BUSINESSES,
                ]);
            }

            foreach ($businesses as $business) {
                $businessId = $business['id'] ?? null;
                $businessName = $business['name'] ?? 'Unknown Business';
                if (!$businessId) continue;

                // Fetch owned catalogs
                try {
                    $ownedCatalogs = $this->fetchAllPages(
                        self::BASE_URL . '/' . self::API_VERSION . "/{$businessId}/owned_product_catalogs",
                        $accessToken,
                        ['fields' => 'id,name,product_count,vertical']
                    );

                    foreach ($ownedCatalogs as $catalog) {
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
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch owned catalogs', [
                        'business_id' => $businessId,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Fetch client catalogs (shared with you)
                try {
                    $clientCatalogs = $this->fetchAllPages(
                        self::BASE_URL . '/' . self::API_VERSION . "/{$businessId}/client_product_catalogs",
                        $accessToken,
                        ['fields' => 'id,name,product_count,vertical']
                    );

                    foreach ($clientCatalogs as $catalog) {
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
                } catch (\Exception $e) {
                    Log::debug('Failed to fetch client catalogs', [
                        'business_id' => $businessId,
                    ]);
                }
            }

            Log::info('Product Catalogs fetched', ['count' => count($catalogs)]);
            return $catalogs;
        });
    }

    /**
     * Get WhatsApp Business Accounts (from all businesses with pagination).
     */
    public function getWhatsappAccounts(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'whatsapp');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken) {
            Log::info('Fetching WhatsApp Business Accounts from Meta API');

            $whatsappAccounts = [];
            $allBusinesses = $this->getBusinesses($accessToken, $connectionId);

            // Limit businesses to prevent API exhaustion
            $businesses = array_slice($allBusinesses, 0, self::MAX_BUSINESSES);
            if (count($allBusinesses) > self::MAX_BUSINESSES) {
                Log::info('Limited businesses for WhatsApp fetch', [
                    'total' => count($allBusinesses),
                    'limited_to' => self::MAX_BUSINESSES,
                ]);
            }

            foreach ($businesses as $business) {
                $businessId = $business['id'] ?? null;
                $businessName = $business['name'] ?? 'Unknown Business';
                if (!$businessId) continue;

                try {
                    $wabaData = $this->fetchAllPages(
                        self::BASE_URL . '/' . self::API_VERSION . "/{$businessId}/owned_whatsapp_business_accounts",
                        $accessToken,
                        ['fields' => 'id,name,phone_numbers{id,display_phone_number,verified_name,quality_rating,code_verification_status}']
                    );

                    foreach ($wabaData as $waba) {
                        $wabaId = $waba['id'] ?? null;
                        $wabaName = $waba['name'] ?? 'Unnamed WABA';
                        $phoneNumbers = $waba['phone_numbers']['data'] ?? [];

                        foreach ($phoneNumbers as $phone) {
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
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch WhatsApp accounts for business', [
                        'business_id' => $businessId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('WhatsApp accounts fetched', ['count' => count($whatsappAccounts)]);
            return $whatsappAccounts;
        });
    }

    /**
     * Get businesses (tries /me/businesses first, then extracts from ad accounts).
     */
    private function getBusinesses(string $accessToken, string $connectionId): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'businesses');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($accessToken, $connectionId) {
            Log::info('Fetching businesses from Meta API');

            // Try /me/businesses first
            $businesses = $this->fetchAllPages(
                self::BASE_URL . '/' . self::API_VERSION . '/me/businesses',
                $accessToken,
                ['fields' => 'id,name']
            );

            if (!empty($businesses)) {
                Log::info('Businesses fetched from /me/businesses', ['count' => count($businesses)]);
                return $businesses;
            }

            // Fallback: Extract from ad accounts
            Log::info('No businesses from /me/businesses, extracting from ad accounts');

            $adAccounts = $this->getAdAccounts($connectionId, $accessToken, false);
            $seenBusinessIds = [];
            $businesses = [];

            foreach ($adAccounts as $account) {
                $businessId = $account['business_id'] ?? null;
                if ($businessId && !in_array($businessId, $seenBusinessIds)) {
                    $businesses[] = [
                        'id' => $businessId,
                        'name' => $account['business_name'] ?? 'Unknown Business',
                    ];
                    $seenBusinessIds[] = $businessId;
                }
            }

            Log::info('Businesses extracted from ad accounts', ['count' => count($businesses)]);
            return $businesses;
        });
    }

    /**
     * Get Custom Conversions (from all ad accounts with pagination).
     */
    public function getCustomConversions(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'custom_conversions');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken) {
            Log::info('Fetching Custom Conversions from Meta API');

            $customConversions = [];
            $adAccounts = $this->getAdAccounts($connectionId, $accessToken, false);

            foreach ($adAccounts as $account) {
                $accountId = $account['id'] ?? null;
                if (!$accountId) continue;

                $formattedAccountId = str_starts_with($accountId, 'act_') ? $accountId : 'act_' . $accountId;

                try {
                    $conversionData = $this->fetchAllPages(
                        self::BASE_URL . '/' . self::API_VERSION . "/{$formattedAccountId}/customconversions",
                        $accessToken,
                        ['fields' => 'id,name,description,custom_event_type,rule,pixel,creation_time,last_fired_time,is_archived']
                    );

                    foreach ($conversionData as $conversion) {
                        $existingIds = array_column($customConversions, 'id');
                        if (!in_array($conversion['id'], $existingIds)) {
                            $customConversions[] = [
                                'id' => $conversion['id'],
                                'name' => $conversion['name'] ?? 'Unnamed Conversion',
                                'description' => $conversion['description'] ?? null,
                                'custom_event_type' => $conversion['custom_event_type'] ?? null,
                                'rule' => $conversion['rule'] ?? null,
                                'pixel_id' => $conversion['pixel']['id'] ?? null,
                                'ad_account_id' => $account['id'],
                                'ad_account_name' => $account['name'] ?? 'Unknown',
                                'creation_time' => $conversion['creation_time'] ?? null,
                                'last_fired_time' => $conversion['last_fired_time'] ?? null,
                                'is_archived' => $conversion['is_archived'] ?? false,
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch custom conversions for ad account', [
                        'account_id' => $formattedAccountId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Custom Conversions fetched', ['count' => count($customConversions)]);
            return $customConversions;
        });
    }

    /**
     * Get Offline Event Sets (from all businesses).
     */
    public function getOfflineEventSets(string $connectionId, string $accessToken, bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey($connectionId, 'offline_event_sets');

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($connectionId, $accessToken) {
            Log::info('Fetching Offline Event Sets from Meta API');

            $offlineEventSets = [];
            $businesses = $this->getBusinesses($accessToken, $connectionId);

            foreach ($businesses as $business) {
                $businessId = $business['id'] ?? null;
                $businessName = $business['name'] ?? 'Unknown Business';
                if (!$businessId) continue;

                try {
                    $eventSetData = $this->fetchAllPages(
                        self::BASE_URL . '/' . self::API_VERSION . "/{$businessId}/offline_conversion_data_sets",
                        $accessToken,
                        ['fields' => 'id,name,description,upload_rate,duplicate_entries,match_rate_approx,event_stats,data_origin']
                    );

                    foreach ($eventSetData as $eventSet) {
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
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch offline event sets for business', [
                        'business_id' => $businessId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Offline Event Sets fetched', ['count' => count($offlineEventSets)]);
            return $offlineEventSets;
        });
    }

    /**
     * Clear all cached assets for a connection.
     */
    public function refreshAll(string $connectionId): void
    {
        $assetTypes = [
            'pages', 'instagram', 'threads', 'ad_accounts', 'pixels', 'catalogs', 'whatsapp', 'businesses',
            'custom_conversions', 'offline_event_sets'
        ];

        foreach ($assetTypes as $type) {
            Cache::forget($this->getCacheKey($connectionId, $type));
        }

        Log::info('Cache cleared for all Meta assets', ['connection_id' => $connectionId]);
    }

    /**
     * Get cache status for all asset types.
     */
    public function getCacheStatus(string $connectionId): array
    {
        $assetTypes = [
            'pages', 'instagram', 'threads', 'ad_accounts', 'pixels', 'catalogs', 'whatsapp',
            'custom_conversions', 'creative_folders', 'domains', 'offline_event_sets', 'apps'
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
