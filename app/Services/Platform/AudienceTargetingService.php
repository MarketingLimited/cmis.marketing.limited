<?php

namespace App\Services\Platform;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for fetching audience targeting options from ad platforms
 * Supports: Meta, Google, TikTok, Snapchat, X (Twitter), LinkedIn
 */
class AudienceTargetingService
{
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get all audience targeting options for a platform
     */
    public function getTargetingOptions(string $platform, string $accessToken, array $options = []): array
    {
        $method = 'get' . ucfirst($platform) . 'TargetingOptions';

        if (!method_exists($this, $method)) {
            throw new \Exception("Platform {$platform} is not supported");
        }

        return $this->$method($accessToken, $options);
    }

    /**
     * Get custom audiences for a platform
     */
    public function getCustomAudiences(string $platform, string $accessToken, string $adAccountId): array
    {
        $cacheKey = "audiences_custom_{$platform}_{$adAccountId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($platform, $accessToken, $adAccountId) {
            return match ($platform) {
                'meta' => $this->getMetaCustomAudiences($accessToken, $adAccountId),
                'google' => $this->getGoogleCustomAudiences($accessToken, $adAccountId),
                'tiktok' => $this->getTikTokCustomAudiences($accessToken, $adAccountId),
                'snapchat' => $this->getSnapchatCustomAudiences($accessToken, $adAccountId),
                'twitter' => $this->getTwitterCustomAudiences($accessToken, $adAccountId),
                'linkedin' => $this->getLinkedInCustomAudiences($accessToken, $adAccountId),
                default => [],
            };
        });
    }

    /**
     * Get lookalike audiences for a platform
     */
    public function getLookalikeAudiences(string $platform, string $accessToken, string $adAccountId): array
    {
        $cacheKey = "audiences_lookalike_{$platform}_{$adAccountId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($platform, $accessToken, $adAccountId) {
            return match ($platform) {
                'meta' => $this->getMetaLookalikeAudiences($accessToken, $adAccountId),
                'google' => $this->getGoogleSimilarAudiences($accessToken, $adAccountId),
                'tiktok' => $this->getTikTokLookalikeAudiences($accessToken, $adAccountId),
                'snapchat' => $this->getSnapchatLookalikeAudiences($accessToken, $adAccountId),
                'twitter' => $this->getTwitterTailoredAudiences($accessToken, $adAccountId),
                'linkedin' => $this->getLinkedInMatchedAudiences($accessToken, $adAccountId),
                default => [],
            };
        });
    }

    /**
     * Get interest targeting options for a platform
     */
    public function getInterests(string $platform, string $accessToken, ?string $query = null): array
    {
        $cacheKey = "targeting_interests_{$platform}_" . md5($query ?? 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($platform, $accessToken, $query) {
            return match ($platform) {
                'meta' => $this->getMetaInterests($accessToken, $query),
                'google' => $this->getGoogleAffinity($accessToken, $query),
                'tiktok' => $this->getTikTokInterests($accessToken, $query),
                'snapchat' => $this->getSnapchatInterests($accessToken, $query),
                'twitter' => $this->getTwitterInterests($accessToken, $query),
                'linkedin' => $this->getLinkedInInterests($accessToken, $query),
                default => [],
            };
        });
    }

    /**
     * Get behavioral targeting options
     */
    public function getBehaviors(string $platform, string $accessToken): array
    {
        $cacheKey = "targeting_behaviors_{$platform}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($platform, $accessToken) {
            return match ($platform) {
                'meta' => $this->getMetaBehaviors($accessToken),
                'google' => $this->getGoogleInMarketAudiences($accessToken),
                'tiktok' => $this->getTikTokBehaviors($accessToken),
                'snapchat' => $this->getSnapchatLifestyles($accessToken),
                default => [],
            };
        });
    }

    /**
     * Get demographic targeting options
     */
    public function getDemographics(string $platform, string $accessToken): array
    {
        $cacheKey = "targeting_demographics_{$platform}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($platform, $accessToken) {
            return match ($platform) {
                'meta' => $this->getMetaDemographics($accessToken),
                'google' => $this->getGoogleDemographics($accessToken),
                'tiktok' => $this->getTikTokDemographics($accessToken),
                'snapchat' => $this->getSnapchatDemographics($accessToken),
                'twitter' => $this->getTwitterDemographics($accessToken),
                'linkedin' => $this->getLinkedInDemographics($accessToken),
                default => [],
            };
        });
    }

    // ===================== META (Facebook/Instagram) =====================

    /**
     * Get Meta custom audiences
     */
    private function getMetaCustomAudiences(string $accessToken, string $adAccountId): array
    {
        try {
            $response = Http::get("https://graph.facebook.com/v21.0/act_{$adAccountId}/customaudiences", [
                'access_token' => $accessToken,
                // Note: approximate_count requires special permissions, using approximate_count_lower_bound instead
                'fields' => 'id,name,description,subtype,approximate_count_lower_bound,approximate_count_upper_bound,data_source,delivery_status,operation_status',
                'limit' => 100,
            ]);

            if ($response->successful()) {
                return array_map(fn($audience) => [
                    'id' => $audience['id'],
                    'name' => $audience['name'],
                    'description' => $audience['description'] ?? null,
                    'type' => $audience['subtype'] ?? 'CUSTOM',
                    'size' => $audience['approximate_count_lower_bound'] ?? $audience['approximate_count_upper_bound'] ?? null,
                    'size_range' => isset($audience['approximate_count_lower_bound']) ?
                        "{$audience['approximate_count_lower_bound']} - {$audience['approximate_count_upper_bound']}" : null,
                    'status' => $audience['delivery_status']['code'] ?? null,
                    'source' => $audience['data_source']['type'] ?? null,
                ], $response->json('data', []));
            }

            Log::warning('Meta custom audiences API error', [
                'ad_account_id' => $adAccountId,
                'status' => $response->status(),
                'error' => $response->json('error.message', 'Unknown error'),
            ]);
        } catch (\Exception $e) {
            Log::error('Meta custom audiences fetch error', [
                'ad_account_id' => $adAccountId,
                'error' => $e->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * Get Meta lookalike audiences
     */
    private function getMetaLookalikeAudiences(string $accessToken, string $adAccountId): array
    {
        try {
            $response = Http::get("https://graph.facebook.com/v21.0/act_{$adAccountId}/customaudiences", [
                'access_token' => $accessToken,
                'fields' => 'id,name,description,subtype,approximate_count_lower_bound,approximate_count_upper_bound,lookalike_spec',
                'filtering' => json_encode([['field' => 'subtype', 'operator' => 'EQUAL', 'value' => 'LOOKALIKE']]),
                'limit' => 100,
            ]);

            if ($response->successful()) {
                return array_map(fn($audience) => [
                    'id' => $audience['id'],
                    'name' => $audience['name'],
                    'type' => 'LOOKALIKE',
                    'size' => $audience['approximate_count_lower_bound'] ?? $audience['approximate_count_upper_bound'] ?? null,
                    'ratio' => $audience['lookalike_spec']['ratio'] ?? null,
                    'country' => $audience['lookalike_spec']['country'] ?? null,
                    'source_audience' => $audience['lookalike_spec']['origin'][0]['id'] ?? null,
                ], $response->json('data', []));
            }

            Log::warning('Meta lookalike audiences API error', [
                'status' => $response->status(),
                'error' => $response->json('error.message', 'Unknown error'),
            ]);
        } catch (\Exception $e) {
            Log::error('Meta lookalike audiences fetch error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Get Meta interest targeting options
     */
    private function getMetaInterests(string $accessToken, ?string $query = null): array
    {
        try {
            $params = [
                'access_token' => $accessToken,
                'type' => 'adinterest',
                'limit' => 100,
            ];

            if ($query) {
                $params['q'] = $query;
            }

            $response = Http::get("https://graph.facebook.com/v21.0/search", $params);

            if ($response->successful()) {
                return array_map(fn($interest) => [
                    'id' => $interest['id'],
                    'name' => $interest['name'],
                    'type' => 'interest',
                    'audience_size_lower_bound' => $interest['audience_size_lower_bound'] ?? null,
                    'audience_size_upper_bound' => $interest['audience_size_upper_bound'] ?? null,
                    'path' => $interest['path'] ?? [],
                    'topic' => $interest['topic'] ?? null,
                ], $response->json('data', []));
            }
        } catch (\Exception $e) {
            Log::error('Meta interests fetch error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Get Meta behaviors
     */
    private function getMetaBehaviors(string $accessToken): array
    {
        try {
            $response = Http::get("https://graph.facebook.com/v21.0/search", [
                'access_token' => $accessToken,
                'type' => 'adbehavior',
                'limit' => 500,
            ]);

            if ($response->successful()) {
                return array_map(fn($behavior) => [
                    'id' => $behavior['id'],
                    'name' => $behavior['name'],
                    'type' => 'behavior',
                    'description' => $behavior['description'] ?? null,
                    'audience_size_lower_bound' => $behavior['audience_size_lower_bound'] ?? null,
                    'audience_size_upper_bound' => $behavior['audience_size_upper_bound'] ?? null,
                ], $response->json('data', []));
            }
        } catch (\Exception $e) {
            Log::error('Meta behaviors fetch error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Get Meta demographics
     */
    private function getMetaDemographics(string $accessToken): array
    {
        return [
            'age_ranges' => [
                ['min' => 13, 'max' => 17, 'label' => '13-17'],
                ['min' => 18, 'max' => 24, 'label' => '18-24'],
                ['min' => 25, 'max' => 34, 'label' => '25-34'],
                ['min' => 35, 'max' => 44, 'label' => '35-44'],
                ['min' => 45, 'max' => 54, 'label' => '45-54'],
                ['min' => 55, 'max' => 64, 'label' => '55-64'],
                ['min' => 65, 'max' => null, 'label' => '65+'],
            ],
            'genders' => [
                ['id' => 1, 'name' => 'Male'],
                ['id' => 2, 'name' => 'Female'],
            ],
            'locales' => $this->getMetaLocales($accessToken),
            'relationship_statuses' => [
                ['id' => 1, 'name' => 'Single'],
                ['id' => 2, 'name' => 'In a relationship'],
                ['id' => 3, 'name' => 'Married'],
                ['id' => 4, 'name' => 'Engaged'],
            ],
            'education_levels' => [
                ['id' => 1, 'name' => 'High school'],
                ['id' => 2, 'name' => 'Some college'],
                ['id' => 3, 'name' => 'Associate degree'],
                ['id' => 4, 'name' => 'In college'],
                ['id' => 5, 'name' => 'College grad'],
                ['id' => 6, 'name' => 'Some graduate school'],
                ['id' => 7, 'name' => 'Master degree'],
                ['id' => 8, 'name' => 'Professional degree'],
                ['id' => 9, 'name' => 'Doctorate degree'],
            ],
        ];
    }

    /**
     * Get Meta locales
     */
    private function getMetaLocales(string $accessToken): array
    {
        try {
            $response = Http::get("https://graph.facebook.com/v21.0/search", [
                'access_token' => $accessToken,
                'type' => 'adlocale',
                'limit' => 100,
            ]);

            if ($response->successful()) {
                return $response->json('data', []);
            }
        } catch (\Exception $e) {
            Log::error('Meta locales fetch error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Search Meta locations (cities, regions, countries, zip codes)
     */
    public function searchMetaLocations(string $accessToken, string $query, array $locationTypes = ['city', 'region', 'country']): array
    {
        $cacheKey = "meta_locations_" . md5($query . implode('_', $locationTypes));

        return Cache::remember($cacheKey, 300, function () use ($accessToken, $query, $locationTypes) {
            try {
                $response = Http::get("https://graph.facebook.com/v21.0/search", [
                    'access_token' => $accessToken,
                    'type' => 'adgeolocation',
                    'location_types' => json_encode($locationTypes),
                    'q' => $query,
                    'limit' => 50,
                ]);

                if ($response->successful()) {
                    return array_map(fn($loc) => [
                        'key' => $loc['key'],
                        'name' => $loc['name'],
                        'type' => $loc['type'],
                        'country_code' => $loc['country_code'] ?? null,
                        'country_name' => $loc['country_name'] ?? null,
                        'region' => $loc['region'] ?? null,
                        'region_id' => $loc['region_id'] ?? null,
                        'supports_city' => $loc['supports_city'] ?? false,
                        'supports_region' => $loc['supports_region'] ?? false,
                    ], $response->json('data', []));
                }
            } catch (\Exception $e) {
                Log::error('Meta locations search error', ['error' => $e->getMessage()]);
            }

            return [];
        });
    }

    /**
     * Search Meta work positions (job titles)
     */
    public function searchMetaWorkPositions(string $accessToken, string $query): array
    {
        $cacheKey = "meta_work_positions_" . md5($query);

        return Cache::remember($cacheKey, 3600, function () use ($accessToken, $query) {
            try {
                $response = Http::get("https://graph.facebook.com/v21.0/search", [
                    'access_token' => $accessToken,
                    'type' => 'adworkposition',
                    'q' => $query,
                    'limit' => 50,
                ]);

                if ($response->successful()) {
                    return array_map(fn($pos) => [
                        'id' => $pos['id'],
                        'name' => $pos['name'],
                    ], $response->json('data', []));
                }
            } catch (\Exception $e) {
                Log::error('Meta work positions search error', ['error' => $e->getMessage()]);
            }

            return [];
        });
    }

    /**
     * Get interest suggestions based on selected interests
     */
    public function getMetaInterestSuggestions(string $accessToken, array $interestIds): array
    {
        try {
            $response = Http::get("https://graph.facebook.com/v21.0/search", [
                'access_token' => $accessToken,
                'type' => 'adinterestsuggestion',
                'interest_list' => json_encode($interestIds),
                'limit' => 20,
            ]);

            if ($response->successful()) {
                return array_map(fn($interest) => [
                    'id' => $interest['id'],
                    'name' => $interest['name'],
                    'audience_size_lower_bound' => $interest['audience_size_lower_bound'] ?? null,
                    'audience_size_upper_bound' => $interest['audience_size_upper_bound'] ?? null,
                ], $response->json('data', []));
            }
        } catch (\Exception $e) {
            Log::error('Meta interest suggestions error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    // ===================== GOOGLE ADS =====================

    /**
     * Get Google custom audiences (Remarketing lists)
     */
    private function getGoogleCustomAudiences(string $accessToken, string $customerId): array
    {
        // Google Ads API requires Google Ads Query Language
        $query = "
            SELECT
                user_list.id,
                user_list.name,
                user_list.description,
                user_list.membership_status,
                user_list.membership_life_span,
                user_list.size_for_search,
                user_list.size_for_display,
                user_list.type
            FROM user_list
            WHERE user_list.type IN ('CRM_BASED', 'RULE_BASED', 'SIMILAR')
        ";

        // Note: This would need the actual Google Ads API implementation
        return $this->executeGoogleAdsQuery($accessToken, $customerId, $query) ?? [];
    }

    /**
     * Get Google similar audiences
     */
    private function getGoogleSimilarAudiences(string $accessToken, string $customerId): array
    {
        $query = "
            SELECT
                user_list.id,
                user_list.name,
                user_list.description,
                user_list.size_for_search,
                user_list.size_for_display
            FROM user_list
            WHERE user_list.type = 'SIMILAR'
        ";

        return $this->executeGoogleAdsQuery($accessToken, $customerId, $query) ?? [];
    }

    /**
     * Get Google affinity audiences
     */
    private function getGoogleAffinity(string $accessToken, ?string $query = null): array
    {
        // Predefined affinity audiences
        return [
            ['id' => 'affinity_auto', 'name' => 'Auto Enthusiasts', 'category' => 'Affinity'],
            ['id' => 'affinity_beauty', 'name' => 'Beauty Mavens', 'category' => 'Affinity'],
            ['id' => 'affinity_business', 'name' => 'Business Professionals', 'category' => 'Affinity'],
            ['id' => 'affinity_cooking', 'name' => 'Cooking Enthusiasts', 'category' => 'Affinity'],
            ['id' => 'affinity_diy', 'name' => 'DIY Enthusiasts', 'category' => 'Affinity'],
            ['id' => 'affinity_fashion', 'name' => 'Fashionistas', 'category' => 'Affinity'],
            ['id' => 'affinity_fitness', 'name' => 'Health & Fitness Buffs', 'category' => 'Affinity'],
            ['id' => 'affinity_foodies', 'name' => 'Foodies', 'category' => 'Affinity'],
            ['id' => 'affinity_gamers', 'name' => 'Gamers', 'category' => 'Affinity'],
            ['id' => 'affinity_music', 'name' => 'Music Lovers', 'category' => 'Affinity'],
            ['id' => 'affinity_news', 'name' => 'News Junkies', 'category' => 'Affinity'],
            ['id' => 'affinity_outdoor', 'name' => 'Outdoor Enthusiasts', 'category' => 'Affinity'],
            ['id' => 'affinity_pet', 'name' => 'Pet Lovers', 'category' => 'Affinity'],
            ['id' => 'affinity_sports', 'name' => 'Sports Fans', 'category' => 'Affinity'],
            ['id' => 'affinity_tech', 'name' => 'Technophiles', 'category' => 'Affinity'],
            ['id' => 'affinity_travel', 'name' => 'Travel Buffs', 'category' => 'Affinity'],
        ];
    }

    /**
     * Get Google in-market audiences
     */
    private function getGoogleInMarketAudiences(string $accessToken): array
    {
        return [
            ['id' => 'inmarket_auto', 'name' => 'Autos & Vehicles', 'category' => 'In-Market'],
            ['id' => 'inmarket_baby', 'name' => 'Baby & Children Products', 'category' => 'In-Market'],
            ['id' => 'inmarket_beauty', 'name' => 'Beauty Products & Services', 'category' => 'In-Market'],
            ['id' => 'inmarket_business', 'name' => 'Business Services', 'category' => 'In-Market'],
            ['id' => 'inmarket_computers', 'name' => 'Computers & Peripherals', 'category' => 'In-Market'],
            ['id' => 'inmarket_consumer', 'name' => 'Consumer Electronics', 'category' => 'In-Market'],
            ['id' => 'inmarket_dating', 'name' => 'Dating Services', 'category' => 'In-Market'],
            ['id' => 'inmarket_education', 'name' => 'Education', 'category' => 'In-Market'],
            ['id' => 'inmarket_employment', 'name' => 'Employment', 'category' => 'In-Market'],
            ['id' => 'inmarket_finance', 'name' => 'Financial Services', 'category' => 'In-Market'],
            ['id' => 'inmarket_gifts', 'name' => 'Gifts & Occasions', 'category' => 'In-Market'],
            ['id' => 'inmarket_home', 'name' => 'Home & Garden', 'category' => 'In-Market'],
            ['id' => 'inmarket_realestate', 'name' => 'Real Estate', 'category' => 'In-Market'],
            ['id' => 'inmarket_software', 'name' => 'Software', 'category' => 'In-Market'],
            ['id' => 'inmarket_sports', 'name' => 'Sports & Fitness', 'category' => 'In-Market'],
            ['id' => 'inmarket_travel', 'name' => 'Travel', 'category' => 'In-Market'],
        ];
    }

    /**
     * Get Google demographics
     */
    private function getGoogleDemographics(string $accessToken): array
    {
        return [
            'age_ranges' => [
                ['id' => 'AGE_RANGE_18_24', 'label' => '18-24'],
                ['id' => 'AGE_RANGE_25_34', 'label' => '25-34'],
                ['id' => 'AGE_RANGE_35_44', 'label' => '35-44'],
                ['id' => 'AGE_RANGE_45_54', 'label' => '45-54'],
                ['id' => 'AGE_RANGE_55_64', 'label' => '55-64'],
                ['id' => 'AGE_RANGE_65_UP', 'label' => '65+'],
                ['id' => 'AGE_RANGE_UNDETERMINED', 'label' => 'Unknown'],
            ],
            'genders' => [
                ['id' => 'MALE', 'name' => 'Male'],
                ['id' => 'FEMALE', 'name' => 'Female'],
                ['id' => 'UNDETERMINED', 'name' => 'Unknown'],
            ],
            'parental_status' => [
                ['id' => 'PARENT', 'name' => 'Parent'],
                ['id' => 'NOT_A_PARENT', 'name' => 'Not a Parent'],
                ['id' => 'UNDETERMINED', 'name' => 'Unknown'],
            ],
            'household_income' => [
                ['id' => 'INCOME_RANGE_TOP_10_PERCENT', 'label' => 'Top 10%'],
                ['id' => 'INCOME_RANGE_11_TO_20_PERCENT', 'label' => '11-20%'],
                ['id' => 'INCOME_RANGE_21_TO_30_PERCENT', 'label' => '21-30%'],
                ['id' => 'INCOME_RANGE_31_TO_40_PERCENT', 'label' => '31-40%'],
                ['id' => 'INCOME_RANGE_41_TO_50_PERCENT', 'label' => '41-50%'],
                ['id' => 'INCOME_RANGE_LOWER_50_PERCENT', 'label' => 'Lower 50%'],
            ],
        ];
    }

    /**
     * Execute Google Ads query (placeholder)
     */
    private function executeGoogleAdsQuery(string $accessToken, string $customerId, string $query): ?array
    {
        // This would be implemented using Google Ads API client library
        // For now, return empty as placeholder
        Log::info('Google Ads query would be executed', ['customer_id' => $customerId]);
        return [];
    }

    // ===================== TIKTOK =====================

    /**
     * Get TikTok custom audiences
     */
    private function getTikTokCustomAudiences(string $accessToken, string $advertiserId): array
    {
        try {
            $response = Http::withHeaders([
                'Access-Token' => $accessToken,
            ])->get("https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/list/", [
                'advertiser_id' => $advertiserId,
                'page_size' => 100,
            ]);

            if ($response->successful() && $response->json('code') === 0) {
                return array_map(fn($audience) => [
                    'id' => $audience['audience_id'],
                    'name' => $audience['name'],
                    'type' => $audience['audience_type'],
                    'size' => $audience['audience_size'] ?? null,
                    'status' => $audience['status'] ?? null,
                ], $response->json('data.list', []));
            }
        } catch (\Exception $e) {
            Log::error('TikTok custom audiences fetch error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Get TikTok lookalike audiences
     */
    private function getTikTokLookalikeAudiences(string $accessToken, string $advertiserId): array
    {
        try {
            $response = Http::withHeaders([
                'Access-Token' => $accessToken,
            ])->get("https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/lookalike/list/", [
                'advertiser_id' => $advertiserId,
                'page_size' => 100,
            ]);

            if ($response->successful() && $response->json('code') === 0) {
                return array_map(fn($audience) => [
                    'id' => $audience['lookalike_id'],
                    'name' => $audience['name'],
                    'type' => 'LOOKALIKE',
                    'size' => $audience['size'] ?? null,
                    'source_audience' => $audience['source_audience_id'] ?? null,
                ], $response->json('data.list', []));
            }
        } catch (\Exception $e) {
            Log::error('TikTok lookalike audiences fetch error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Get TikTok interests
     */
    private function getTikTokInterests(string $accessToken, ?string $query = null): array
    {
        try {
            $response = Http::withHeaders([
                'Access-Token' => $accessToken,
            ])->get("https://business-api.tiktok.com/open_api/v1.3/tool/interest_category/", [
                'advertiser_id' => config('services.tiktok.default_advertiser_id'),
            ]);

            if ($response->successful() && $response->json('code') === 0) {
                return $this->flattenTikTokInterests($response->json('data.interest_categories', []));
            }
        } catch (\Exception $e) {
            Log::error('TikTok interests fetch error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Flatten TikTok interest categories
     */
    private function flattenTikTokInterests(array $categories, array $path = []): array
    {
        $interests = [];
        foreach ($categories as $category) {
            $currentPath = array_merge($path, [$category['name']]);
            $interests[] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'path' => implode(' > ', $currentPath),
            ];
            if (!empty($category['children'])) {
                $interests = array_merge($interests, $this->flattenTikTokInterests($category['children'], $currentPath));
            }
        }
        return $interests;
    }

    /**
     * Get TikTok behaviors
     */
    private function getTikTokBehaviors(string $accessToken): array
    {
        return [
            ['id' => 'behavior_video_watchers', 'name' => 'Video Watchers', 'category' => 'Engagement'],
            ['id' => 'behavior_commenters', 'name' => 'Commenters', 'category' => 'Engagement'],
            ['id' => 'behavior_sharers', 'name' => 'Sharers', 'category' => 'Engagement'],
            ['id' => 'behavior_creators', 'name' => 'Content Creators', 'category' => 'Activity'],
            ['id' => 'behavior_live_viewers', 'name' => 'Live Stream Viewers', 'category' => 'Engagement'],
            ['id' => 'behavior_shoppers', 'name' => 'TikTok Shoppers', 'category' => 'Commerce'],
        ];
    }

    /**
     * Get TikTok demographics
     */
    private function getTikTokDemographics(string $accessToken): array
    {
        return [
            'age_ranges' => [
                ['id' => 'AGE_13_17', 'label' => '13-17'],
                ['id' => 'AGE_18_24', 'label' => '18-24'],
                ['id' => 'AGE_25_34', 'label' => '25-34'],
                ['id' => 'AGE_35_44', 'label' => '35-44'],
                ['id' => 'AGE_45_54', 'label' => '45-54'],
                ['id' => 'AGE_55_PLUS', 'label' => '55+'],
            ],
            'genders' => [
                ['id' => 'MALE', 'name' => 'Male'],
                ['id' => 'FEMALE', 'name' => 'Female'],
            ],
            'languages' => [
                ['id' => 'en', 'name' => 'English'],
                ['id' => 'es', 'name' => 'Spanish'],
                ['id' => 'fr', 'name' => 'French'],
                ['id' => 'de', 'name' => 'German'],
                ['id' => 'pt', 'name' => 'Portuguese'],
                ['id' => 'ar', 'name' => 'Arabic'],
            ],
        ];
    }

    // ===================== SNAPCHAT =====================

    private function getSnapchatCustomAudiences(string $accessToken, string $adAccountId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get("https://adsapi.snapchat.com/v1/adaccounts/{$adAccountId}/segments", [
                'limit' => 100,
            ]);

            if ($response->successful()) {
                return array_map(fn($segment) => [
                    'id' => $segment['segment']['id'],
                    'name' => $segment['segment']['name'],
                    'type' => $segment['segment']['source_type'] ?? 'CUSTOM',
                    'size' => $segment['segment']['targetable_status'] ?? null,
                ], $response->json('segments', []));
            }
        } catch (\Exception $e) {
            Log::error('Snapchat audiences fetch error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    private function getSnapchatLookalikeAudiences(string $accessToken, string $adAccountId): array
    {
        return $this->getSnapchatCustomAudiences($accessToken, $adAccountId);
    }

    private function getSnapchatInterests(string $accessToken, ?string $query = null): array
    {
        return [
            ['id' => 'SCIG_Arts_Entertainment', 'name' => 'Arts & Entertainment', 'category' => 'Interest'],
            ['id' => 'SCIG_Automotive', 'name' => 'Automotive', 'category' => 'Interest'],
            ['id' => 'SCIG_Beauty', 'name' => 'Beauty', 'category' => 'Interest'],
            ['id' => 'SCIG_Business', 'name' => 'Business', 'category' => 'Interest'],
            ['id' => 'SCIG_Careers', 'name' => 'Careers', 'category' => 'Interest'],
            ['id' => 'SCIG_Education', 'name' => 'Education', 'category' => 'Interest'],
            ['id' => 'SCIG_Family_Parenting', 'name' => 'Family & Parenting', 'category' => 'Interest'],
            ['id' => 'SCIG_Fashion', 'name' => 'Fashion', 'category' => 'Interest'],
            ['id' => 'SCIG_Finance', 'name' => 'Finance', 'category' => 'Interest'],
            ['id' => 'SCIG_Food_Drink', 'name' => 'Food & Drink', 'category' => 'Interest'],
            ['id' => 'SCIG_Gaming', 'name' => 'Gaming', 'category' => 'Interest'],
            ['id' => 'SCIG_Health_Fitness', 'name' => 'Health & Fitness', 'category' => 'Interest'],
            ['id' => 'SCIG_Home_Garden', 'name' => 'Home & Garden', 'category' => 'Interest'],
            ['id' => 'SCIG_News_Politics', 'name' => 'News & Politics', 'category' => 'Interest'],
            ['id' => 'SCIG_Pets', 'name' => 'Pets', 'category' => 'Interest'],
            ['id' => 'SCIG_Science_Technology', 'name' => 'Science & Technology', 'category' => 'Interest'],
            ['id' => 'SCIG_Shopping', 'name' => 'Shopping', 'category' => 'Interest'],
            ['id' => 'SCIG_Sports', 'name' => 'Sports', 'category' => 'Interest'],
            ['id' => 'SCIG_Travel', 'name' => 'Travel', 'category' => 'Interest'],
        ];
    }

    private function getSnapchatLifestyles(string $accessToken): array
    {
        return [
            ['id' => 'SLS_Foodies', 'name' => 'Foodies', 'category' => 'Lifestyle'],
            ['id' => 'SLS_Gamers', 'name' => 'Gamers', 'category' => 'Lifestyle'],
            ['id' => 'SLS_Travelers', 'name' => 'Travelers', 'category' => 'Lifestyle'],
            ['id' => 'SLS_Movie_Buffs', 'name' => 'Movie Buffs', 'category' => 'Lifestyle'],
            ['id' => 'SLS_Music_Lovers', 'name' => 'Music Lovers', 'category' => 'Lifestyle'],
            ['id' => 'SLS_Fitness_Enthusiasts', 'name' => 'Fitness Enthusiasts', 'category' => 'Lifestyle'],
            ['id' => 'SLS_Fashion_Forward', 'name' => 'Fashion Forward', 'category' => 'Lifestyle'],
            ['id' => 'SLS_Tech_Savvy', 'name' => 'Tech Savvy', 'category' => 'Lifestyle'],
        ];
    }

    private function getSnapchatDemographics(string $accessToken): array
    {
        return [
            'age_ranges' => [
                ['id' => '13-17', 'label' => '13-17'],
                ['id' => '18-24', 'label' => '18-24'],
                ['id' => '25-34', 'label' => '25-34'],
                ['id' => '35+', 'label' => '35+'],
            ],
            'genders' => [
                ['id' => 'MALE', 'name' => 'Male'],
                ['id' => 'FEMALE', 'name' => 'Female'],
            ],
        ];
    }

    // ===================== TWITTER (X) =====================

    private function getTwitterCustomAudiences(string $accessToken, string $accountId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get("https://ads-api.twitter.com/12/accounts/{$accountId}/custom_audiences", [
                'count' => 100,
            ]);

            if ($response->successful()) {
                return array_map(fn($audience) => [
                    'id' => $audience['id'],
                    'name' => $audience['name'],
                    'type' => $audience['audience_type'],
                    'size' => $audience['audience_size'] ?? null,
                ], $response->json('data', []));
            }
        } catch (\Exception $e) {
            Log::error('Twitter audiences fetch error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    private function getTwitterTailoredAudiences(string $accessToken, string $accountId): array
    {
        return $this->getTwitterCustomAudiences($accessToken, $accountId);
    }

    private function getTwitterInterests(string $accessToken, ?string $query = null): array
    {
        return [
            ['id' => 'Books and literature', 'name' => 'Books and literature', 'category' => 'Interest'],
            ['id' => 'Business', 'name' => 'Business', 'category' => 'Interest'],
            ['id' => 'Careers', 'name' => 'Careers', 'category' => 'Interest'],
            ['id' => 'Education', 'name' => 'Education', 'category' => 'Interest'],
            ['id' => 'Events', 'name' => 'Events', 'category' => 'Interest'],
            ['id' => 'Family and parenting', 'name' => 'Family and parenting', 'category' => 'Interest'],
            ['id' => 'Fashion', 'name' => 'Fashion', 'category' => 'Interest'],
            ['id' => 'Food and drink', 'name' => 'Food and drink', 'category' => 'Interest'],
            ['id' => 'Gaming', 'name' => 'Gaming', 'category' => 'Interest'],
            ['id' => 'Health', 'name' => 'Health', 'category' => 'Interest'],
            ['id' => 'Hobbies and interests', 'name' => 'Hobbies and interests', 'category' => 'Interest'],
            ['id' => 'Home and garden', 'name' => 'Home and garden', 'category' => 'Interest'],
            ['id' => 'Law, government, and politics', 'name' => 'Law, government, and politics', 'category' => 'Interest'],
            ['id' => 'Life stages', 'name' => 'Life stages', 'category' => 'Interest'],
            ['id' => 'Movies and TV', 'name' => 'Movies and TV', 'category' => 'Interest'],
            ['id' => 'Music and radio', 'name' => 'Music and radio', 'category' => 'Interest'],
            ['id' => 'Personal finance', 'name' => 'Personal finance', 'category' => 'Interest'],
            ['id' => 'Pets', 'name' => 'Pets', 'category' => 'Interest'],
            ['id' => 'Science', 'name' => 'Science', 'category' => 'Interest'],
            ['id' => 'Society', 'name' => 'Society', 'category' => 'Interest'],
            ['id' => 'Sports', 'name' => 'Sports', 'category' => 'Interest'],
            ['id' => 'Style and fashion', 'name' => 'Style and fashion', 'category' => 'Interest'],
            ['id' => 'Technology and computing', 'name' => 'Technology and computing', 'category' => 'Interest'],
            ['id' => 'Travel', 'name' => 'Travel', 'category' => 'Interest'],
        ];
    }

    private function getTwitterDemographics(string $accessToken): array
    {
        return [
            'age_ranges' => [
                ['id' => '13-24', 'label' => '13-24'],
                ['id' => '25-34', 'label' => '25-34'],
                ['id' => '35-49', 'label' => '35-49'],
                ['id' => '50+', 'label' => '50+'],
            ],
            'genders' => [
                ['id' => 'MALE', 'name' => 'Male'],
                ['id' => 'FEMALE', 'name' => 'Female'],
            ],
        ];
    }

    // ===================== LINKEDIN =====================

    private function getLinkedInCustomAudiences(string $accessToken, string $accountId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'X-Restli-Protocol-Version' => '2.0.0',
            ])->get("https://api.linkedin.com/rest/dmpSegments", [
                'q' => 'account',
                'account' => "urn:li:sponsoredAccount:{$accountId}",
            ]);

            if ($response->successful()) {
                return array_map(fn($segment) => [
                    'id' => $segment['id'],
                    'name' => $segment['name'],
                    'type' => $segment['type'] ?? 'CUSTOM',
                    'size' => $segment['estimatedSize'] ?? null,
                ], $response->json('elements', []));
            }
        } catch (\Exception $e) {
            Log::error('LinkedIn audiences fetch error', ['error' => $e->getMessage()]);
        }

        return [];
    }

    private function getLinkedInMatchedAudiences(string $accessToken, string $accountId): array
    {
        return $this->getLinkedInCustomAudiences($accessToken, $accountId);
    }

    private function getLinkedInInterests(string $accessToken, ?string $query = null): array
    {
        return [
            ['id' => 'arts_entertainment', 'name' => 'Arts and Entertainment', 'category' => 'Interest'],
            ['id' => 'education', 'name' => 'Education', 'category' => 'Interest'],
            ['id' => 'finance', 'name' => 'Finance', 'category' => 'Interest'],
            ['id' => 'government', 'name' => 'Government', 'category' => 'Interest'],
            ['id' => 'healthcare', 'name' => 'Healthcare', 'category' => 'Interest'],
            ['id' => 'legal', 'name' => 'Legal', 'category' => 'Interest'],
            ['id' => 'marketing', 'name' => 'Marketing', 'category' => 'Interest'],
            ['id' => 'politics', 'name' => 'Politics', 'category' => 'Interest'],
            ['id' => 'science', 'name' => 'Science', 'category' => 'Interest'],
            ['id' => 'technology', 'name' => 'Technology', 'category' => 'Interest'],
        ];
    }

    private function getLinkedInDemographics(string $accessToken): array
    {
        return [
            'age_ranges' => [
                ['id' => '18-24', 'label' => '18-24'],
                ['id' => '25-34', 'label' => '25-34'],
                ['id' => '35-54', 'label' => '35-54'],
                ['id' => '55+', 'label' => '55+'],
            ],
            'genders' => [
                ['id' => 'MALE', 'name' => 'Male'],
                ['id' => 'FEMALE', 'name' => 'Female'],
            ],
            'seniority' => [
                ['id' => 'ENTRY', 'name' => 'Entry'],
                ['id' => 'SENIOR', 'name' => 'Senior'],
                ['id' => 'MANAGER', 'name' => 'Manager'],
                ['id' => 'DIRECTOR', 'name' => 'Director'],
                ['id' => 'VP', 'name' => 'VP'],
                ['id' => 'CXO', 'name' => 'CXO'],
                ['id' => 'OWNER', 'name' => 'Owner/Partner'],
            ],
            'company_sizes' => [
                ['id' => 'SIZE_1', 'name' => 'Myself Only (1)'],
                ['id' => 'SIZE_2_10', 'name' => '2-10'],
                ['id' => 'SIZE_11_50', 'name' => '11-50'],
                ['id' => 'SIZE_51_200', 'name' => '51-200'],
                ['id' => 'SIZE_201_500', 'name' => '201-500'],
                ['id' => 'SIZE_501_1000', 'name' => '501-1000'],
                ['id' => 'SIZE_1001_5000', 'name' => '1001-5000'],
                ['id' => 'SIZE_5001_10000', 'name' => '5001-10000'],
                ['id' => 'SIZE_10001_PLUS', 'name' => '10001+'],
            ],
            'industries' => [
                ['id' => 'accounting', 'name' => 'Accounting'],
                ['id' => 'banking', 'name' => 'Banking'],
                ['id' => 'computer_software', 'name' => 'Computer Software'],
                ['id' => 'consumer_goods', 'name' => 'Consumer Goods'],
                ['id' => 'education', 'name' => 'Education'],
                ['id' => 'financial_services', 'name' => 'Financial Services'],
                ['id' => 'healthcare', 'name' => 'Healthcare'],
                ['id' => 'hospitality', 'name' => 'Hospitality'],
                ['id' => 'information_technology', 'name' => 'Information Technology'],
                ['id' => 'insurance', 'name' => 'Insurance'],
                ['id' => 'legal', 'name' => 'Legal Services'],
                ['id' => 'manufacturing', 'name' => 'Manufacturing'],
                ['id' => 'marketing', 'name' => 'Marketing & Advertising'],
                ['id' => 'media', 'name' => 'Media'],
                ['id' => 'real_estate', 'name' => 'Real Estate'],
                ['id' => 'retail', 'name' => 'Retail'],
                ['id' => 'telecommunications', 'name' => 'Telecommunications'],
            ],
        ];
    }
}
