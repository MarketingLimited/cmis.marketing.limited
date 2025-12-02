<?php

namespace App\Services\Social;

use App\Models\Core\Integration;
use InvalidArgumentException;

/**
 * Service for managing platform-specific boost configurations.
 *
 * Provides access to platform objectives, placements, targeting options,
 * and validates boost configurations for each supported ad platform.
 */
class BoostConfigurationService
{
    /**
     * Get the full configuration for a specific platform.
     *
     * @param string $platform The platform identifier (meta, google, tiktok, etc.)
     * @return array The complete platform configuration
     * @throws InvalidArgumentException If platform is not supported
     */
    public function getConfigForPlatform(string $platform): array
    {
        $config = config("boost-platforms.{$platform}");

        if (!$config) {
            throw new InvalidArgumentException("Unknown or unsupported platform: {$platform}");
        }

        return $config;
    }

    /**
     * Get campaign objectives for a platform.
     *
     * @param string $platform The platform identifier
     * @param string|null $locale Optional locale for translated names (ar or en)
     * @return array List of objectives with id, name, and description
     */
    public function getObjectives(string $platform, ?string $locale = null): array
    {
        $objectives = $this->getConfigForPlatform($platform)['objectives'] ?? [];

        if ($locale === 'ar') {
            return array_map(function ($obj) {
                $result = [
                    'id' => $obj['id'],
                    'name' => $obj['name_ar'] ?? $obj['name'],
                    'description' => $obj['description_ar'] ?? $obj['description'] ?? '',
                ];

                // Include destination_types with localized names
                if (!empty($obj['destination_types'])) {
                    $result['destination_types'] = array_map(function ($dest) {
                        return [
                            'id' => $dest['id'],
                            'name' => $dest['name_ar'] ?? $dest['name'],
                            'icon' => $dest['icon'] ?? 'fa-circle',
                            'requires' => $dest['requires'] ?? [],
                        ];
                    }, $obj['destination_types']);
                }

                return $result;
            }, $objectives);
        }

        // For non-Arabic, include destination_types as-is
        return array_map(function ($obj) {
            $result = [
                'id' => $obj['id'],
                'name' => $obj['name'],
                'description' => $obj['description'] ?? '',
            ];

            if (!empty($obj['destination_types'])) {
                $result['destination_types'] = $obj['destination_types'];
            }

            return $result;
        }, $objectives);
    }

    /**
     * Get available placements for a platform.
     *
     * @param string $platform The platform identifier
     * @param string|null $locale Optional locale for translated names
     * @return array List of placements
     */
    public function getPlacements(string $platform, ?string $locale = null): array
    {
        $placements = $this->getConfigForPlatform($platform)['placements'] ?? [];

        if ($locale === 'ar') {
            return array_map(function ($placement) {
                return [
                    'id' => $placement['id'],
                    'name' => $placement['name_ar'] ?? $placement['name'],
                ];
            }, $placements);
        }

        return $placements;
    }

    /**
     * Get ad formats for a platform.
     *
     * @param string $platform The platform identifier
     * @param string|null $locale Optional locale for translated names
     * @return array List of ad formats
     */
    public function getAdFormats(string $platform, ?string $locale = null): array
    {
        $formats = $this->getConfigForPlatform($platform)['ad_formats'] ?? [];

        if ($locale === 'ar') {
            return array_map(function ($format) {
                return [
                    'id' => $format['id'],
                    'name' => $format['name_ar'] ?? $format['name'],
                    'description' => $format['description_ar'] ?? $format['description'] ?? '',
                ];
            }, $formats);
        }

        return $formats;
    }

    /**
     * Get bidding strategies for a platform.
     *
     * @param string $platform The platform identifier
     * @param string|null $locale Optional locale for translated names
     * @return array List of bidding strategies
     */
    public function getBiddingStrategies(string $platform, ?string $locale = null): array
    {
        $strategies = $this->getConfigForPlatform($platform)['bidding_strategies'] ?? [];

        if ($locale === 'ar') {
            return array_map(function ($strategy) {
                return [
                    'id' => $strategy['id'],
                    'name' => $strategy['name_ar'] ?? $strategy['name'],
                ];
            }, $strategies);
        }

        return $strategies;
    }

    /**
     * Get special features available for a platform.
     *
     * @param string $platform The platform identifier
     * @return array Associative array of feature => boolean
     */
    public function getSpecialFeatures(string $platform): array
    {
        return $this->getConfigForPlatform($platform)['special_features'] ?? [];
    }

    /**
     * Get B2B targeting options (LinkedIn specific).
     *
     * @param string $platform The platform identifier
     * @return array B2B targeting options or empty array if not supported
     */
    public function getB2BTargeting(string $platform): array
    {
        return $this->getConfigForPlatform($platform)['b2b_targeting'] ?? [];
    }

    /**
     * Get company size options (LinkedIn specific).
     *
     * @param string $platform The platform identifier
     * @param string|null $locale Optional locale for translated names
     * @return array List of company size options
     */
    public function getCompanySizes(string $platform, ?string $locale = null): array
    {
        $sizes = $this->getConfigForPlatform($platform)['company_sizes'] ?? [];

        if ($locale === 'ar') {
            return array_map(function ($size) {
                return [
                    'id' => $size['id'],
                    'name' => $size['name_ar'] ?? $size['name'],
                ];
            }, $sizes);
        }

        return $sizes;
    }

    /**
     * Get seniority levels (LinkedIn specific).
     *
     * @param string $platform The platform identifier
     * @param string|null $locale Optional locale for translated names
     * @return array List of seniority levels
     */
    public function getSeniorityLevels(string $platform, ?string $locale = null): array
    {
        $levels = $this->getConfigForPlatform($platform)['seniority_levels'] ?? [];

        if ($locale === 'ar') {
            return array_map(function ($level) {
                return [
                    'id' => $level['id'],
                    'name' => $level['name_ar'] ?? $level['name'],
                ];
            }, $levels);
        }

        return $levels;
    }

    /**
     * Get optimization goals for a platform.
     *
     * @param string $platform The platform identifier
     * @param string|null $locale Optional locale for translated names
     * @return array List of optimization goals
     */
    public function getOptimizationGoals(string $platform, ?string $locale = null): array
    {
        $goals = $this->getConfigForPlatform($platform)['optimization_goals'] ?? [];

        if ($locale === 'ar') {
            return array_map(function ($goal) {
                return [
                    'id' => $goal['id'],
                    'name' => $goal['name_ar'] ?? $goal['name'],
                ];
            }, $goals);
        }

        return $goals;
    }

    /**
     * Get ad types (Snapchat specific).
     *
     * @param string $platform The platform identifier
     * @param string|null $locale Optional locale for translated names
     * @return array List of ad types
     */
    public function getAdTypes(string $platform, ?string $locale = null): array
    {
        $types = $this->getConfigForPlatform($platform)['ad_types'] ?? [];

        if ($locale === 'ar') {
            return array_map(function ($type) {
                return [
                    'id' => $type['id'],
                    'name' => $type['name_ar'] ?? $type['name'],
                    'description' => $type['description_ar'] ?? $type['description'] ?? '',
                ];
            }, $types);
        }

        return $types;
    }

    /**
     * Get bid types (TikTok specific).
     *
     * @param string $platform The platform identifier
     * @param string|null $locale Optional locale for translated names
     * @return array List of bid types
     */
    public function getBidTypes(string $platform, ?string $locale = null): array
    {
        $types = $this->getConfigForPlatform($platform)['bid_types'] ?? [];

        if ($locale === 'ar') {
            return array_map(function ($type) {
                return [
                    'id' => $type['id'],
                    'name' => $type['name_ar'] ?? $type['name'],
                ];
            }, $types);
        }

        return $types;
    }

    /**
     * Get the budget multiplier for a platform.
     *
     * Some platforms expect budget in different units:
     * - Meta: cents (multiply by 100)
     * - Snapchat/Twitter: micros (multiply by 1,000,000)
     * - Others: standard units (multiply by 1)
     *
     * @param string $platform The platform identifier
     * @return int The multiplier to convert standard currency to platform units
     */
    public function getBudgetMultiplier(string $platform): int
    {
        return $this->getConfigForPlatform($platform)['budget_multiplier'] ?? 1;
    }

    /**
     * Get the minimum budget for a platform.
     *
     * @param string $platform The platform identifier
     * @return float The minimum budget in standard currency units
     */
    public function getMinBudget(string $platform): float
    {
        return $this->getConfigForPlatform($platform)['min_budget'] ?? 1;
    }

    /**
     * Get the minimum audience size for a platform.
     *
     * @param string $platform The platform identifier
     * @return int|null The minimum audience size or null if not applicable
     */
    public function getMinAudienceSize(string $platform): ?int
    {
        return $this->getConfigForPlatform($platform)['min_audience_size'] ?? null;
    }

    /**
     * Get the platform display name.
     *
     * @param string $platform The platform identifier
     * @return string The display name
     */
    public function getPlatformName(string $platform): string
    {
        return $this->getConfigForPlatform($platform)['name'] ?? ucfirst($platform);
    }

    /**
     * Check if a platform supports a specific feature.
     *
     * @param string $platform The platform identifier
     * @param string $feature The feature name (e.g., 'advantage_plus', 'spark_ads')
     * @return bool Whether the platform supports the feature
     */
    public function hasFeature(string $platform, string $feature): bool
    {
        $features = $this->getSpecialFeatures($platform);
        return !empty($features[$feature]);
    }

    /**
     * Check if a platform supports B2B targeting.
     *
     * @param string $platform The platform identifier
     * @return bool Whether the platform supports B2B targeting
     */
    public function supportsB2BTargeting(string $platform): bool
    {
        return !empty($this->getB2BTargeting($platform));
    }

    /**
     * Convert a budget to platform-specific units.
     *
     * @param string $platform The platform identifier
     * @param float $budget The budget in standard currency units
     * @return int The budget in platform-specific units
     */
    public function convertBudgetToPlatformUnits(string $platform, float $budget): int
    {
        return (int) round($budget * $this->getBudgetMultiplier($platform));
    }

    /**
     * Convert platform-specific budget units to standard currency.
     *
     * @param string $platform The platform identifier
     * @param int $platformBudget The budget in platform-specific units
     * @return float The budget in standard currency units
     */
    public function convertBudgetFromPlatformUnits(string $platform, int $platformBudget): float
    {
        $multiplier = $this->getBudgetMultiplier($platform);
        return $multiplier > 0 ? $platformBudget / $multiplier : $platformBudget;
    }

    /**
     * Validate a boost configuration for a platform.
     *
     * @param string $platform The platform identifier
     * @param array $config The boost configuration to validate
     * @return array Validation result with 'valid', 'errors', and 'warnings' keys
     */
    public function validateBoostConfig(string $platform, array $config): array
    {
        $errors = [];
        $warnings = [];

        try {
            $platformConfig = $this->getConfigForPlatform($platform);
        } catch (InvalidArgumentException $e) {
            return [
                'valid' => false,
                'errors' => [$e->getMessage()],
                'warnings' => [],
            ];
        }

        // Validate objective
        $validObjectives = array_column($platformConfig['objectives'] ?? [], 'id');
        if (!empty($config['objective']) && !in_array($config['objective'], $validObjectives)) {
            $errors[] = __('profiles.invalid_objective_for_platform', [
                'platform' => $platformConfig['name'],
            ]);
        }

        // Validate budget
        $minBudget = $platformConfig['min_budget'] ?? 1;
        if (isset($config['budget']) && $config['budget'] < $minBudget) {
            $errors[] = __('profiles.minimum_budget_for_platform', [
                'platform' => $platformConfig['name'],
                'amount' => $minBudget,
            ]);
        }

        // Validate placements if specified
        if (!empty($config['placements']) && is_array($config['placements'])) {
            $validPlacements = array_column($platformConfig['placements'] ?? [], 'id');
            foreach ($config['placements'] as $placement) {
                if (!in_array($placement, $validPlacements)) {
                    $warnings[] = __('profiles.invalid_placement', ['placement' => $placement]);
                }
            }
        }

        // Validate bidding strategy if specified
        if (!empty($config['bidding_strategy'])) {
            $validStrategies = array_column($platformConfig['bidding_strategies'] ?? [], 'id');
            if (!empty($validStrategies) && !in_array($config['bidding_strategy'], $validStrategies)) {
                $warnings[] = __('profiles.invalid_bidding_strategy');
            }
        }

        // Platform-specific validations
        if ($platform === 'linkedin' && !empty($config['targeting']['job_titles'])) {
            // LinkedIn job title validation
            $minAudience = $platformConfig['min_audience_size'] ?? 300;
            $warnings[] = __('profiles.linkedin_audience_size_warning', ['size' => $minAudience]);
        }

        if ($platform === 'tiktok' && !empty($config['spark_ads'])) {
            // Spark Ads requires organic post ID
            if (empty($config['organic_post_id'])) {
                $warnings[] = __('profiles.spark_ads_requires_post');
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get destination types for a specific objective.
     *
     * Destination types define WHERE the conversion happens (Website, App, Messenger, WhatsApp, etc.)
     * These are based on Meta's ODAX framework and adapted for other platforms.
     *
     * @param string $platform The platform identifier
     * @param string $objectiveId The objective ID
     * @param string|null $locale Optional locale for translated names
     * @return array List of destination types for the objective, empty if none required
     */
    public function getDestinationTypes(string $platform, string $objectiveId, ?string $locale = null): array
    {
        $objectives = $this->getConfigForPlatform($platform)['objectives'] ?? [];

        foreach ($objectives as $objective) {
            if ($objective['id'] === $objectiveId) {
                $destinationTypes = $objective['destination_types'] ?? [];

                if (empty($destinationTypes)) {
                    return [];
                }

                if ($locale === 'ar') {
                    return array_map(function ($dest) {
                        return [
                            'id' => $dest['id'],
                            'name' => $dest['name_ar'] ?? $dest['name'],
                            'icon' => $dest['icon'] ?? 'fa-circle',
                            'requires' => $dest['requires'] ?? [],
                        ];
                    }, $destinationTypes);
                }

                return $destinationTypes;
            }
        }

        return [];
    }

    /**
     * Check if an objective requires destination type selection.
     *
     * @param string $platform The platform identifier
     * @param string $objectiveId The objective ID
     * @return bool True if destination type is required
     */
    public function requiresDestinationType(string $platform, string $objectiveId): bool
    {
        $destinationTypes = $this->getDestinationTypes($platform, $objectiveId);
        return !empty($destinationTypes);
    }

    /**
     * Validate a destination type selection for an objective.
     *
     * @param string $platform The platform identifier
     * @param string $objectiveId The objective ID
     * @param string $destinationType The destination type ID to validate
     * @return bool True if the destination type is valid for this objective
     */
    public function validateDestinationType(string $platform, string $objectiveId, string $destinationType): bool
    {
        $validTypes = $this->getDestinationTypes($platform, $objectiveId);
        $validIds = array_column($validTypes, 'id');

        return in_array($destinationType, $validIds);
    }

    /**
     * Get connected messaging accounts for the organization.
     *
     * Returns WhatsApp numbers and Messenger pages connected via Meta integrations.
     * Used for messaging destination type selection in boost configuration.
     *
     * When a pageId or instagramId is provided, WhatsApp numbers are fetched specifically
     * for the business that owns that page (profile-aware fetching).
     *
     * @param string $orgId The organization ID
     * @param string $platform The platform identifier (currently only 'meta' supported)
     * @param string|null $pageId Optional Facebook Page ID to get WhatsApp numbers for that specific page's business
     * @param string|null $instagramId Optional Instagram ID to get WhatsApp numbers for the linked page's business
     * @return array Connected messaging accounts grouped by type (whatsapp, messenger, instagram_dm)
     */
    public function getConnectedMessagingAccounts(
        string $orgId,
        string $platform = 'meta',
        ?string $pageId = null,
        ?string $instagramId = null
    ): array {
        $accounts = [
            'whatsapp' => [],
            'messenger' => [],
            'instagram_dm' => [],
        ];

        // First try platform_connections (new structure)
        $connection = \App\Models\Platform\PlatformConnection::where('org_id', $orgId)
            ->where('platform', 'meta')
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->first();

        if ($connection) {
            $metadata = $connection->account_metadata ?? [];
            $selectedAssets = $metadata['selected_assets'] ?? [];
            $accessToken = $connection->access_token;

            // Facebook Pages for Messenger
            $pageIds = $selectedAssets['page'] ?? [];
            if (!empty($metadata['facebook_page_id'])) {
                $pageIds = array_unique(array_merge($pageIds, [$metadata['facebook_page_id']]));
            }
            foreach ($pageIds as $pId) {
                $accounts['messenger'][] = [
                    'id' => $pId,
                    'name' => $metadata['facebook_page_name'] ?? "Page {$pId}",
                    'connection_id' => $connection->connection_id,
                ];
            }

            // Instagram accounts for DM
            $igIds = $selectedAssets['instagram_account'] ?? [];
            if (!empty($metadata['instagram_account_id'])) {
                $igIds = array_unique(array_merge($igIds, [$metadata['instagram_account_id']]));
            }
            foreach ($igIds as $igId) {
                $accounts['instagram_dm'][] = [
                    'id' => $igId,
                    'name' => $metadata['instagram_username'] ?? "Instagram {$igId}",
                    'connection_id' => $connection->connection_id,
                ];
            }

            // WhatsApp Business numbers - profile-aware fetching
            if (!empty($accessToken)) {
                $businessId = null;

                // Priority 1: If page ID provided, get business from that specific page
                if ($pageId) {
                    $businessId = $this->getBusinessIdFromPage($accessToken, $pageId);
                    \Log::info('WhatsApp: Got business from page', [
                        'page_id' => $pageId,
                        'business_id' => $businessId,
                    ]);
                }

                // Priority 2: If Instagram ID provided (and no page ID), find linked page first
                if (!$businessId && $instagramId) {
                    $linkedPageId = $this->getPageIdFromInstagram($accessToken, $instagramId);
                    if ($linkedPageId) {
                        $businessId = $this->getBusinessIdFromPage($accessToken, $linkedPageId);
                        \Log::info('WhatsApp: Got business from Instagram linked page', [
                            'instagram_id' => $instagramId,
                            'linked_page_id' => $linkedPageId,
                            'business_id' => $businessId,
                        ]);
                    }
                }

                // Priority 3: Fallback to org-level business_id from metadata
                if (!$businessId && !empty($metadata['business_id'])) {
                    $businessId = $metadata['business_id'];
                    \Log::info('WhatsApp: Using org-level business_id', [
                        'business_id' => $businessId,
                    ]);
                }

                // Fetch WhatsApp numbers if we have a business ID
                if ($businessId) {
                    try {
                        $whatsappAccounts = $this->fetchWhatsAppBusinessNumbers($accessToken, $businessId);
                        $accounts['whatsapp'] = $whatsappAccounts;
                    } catch (\Exception $e) {
                        \Log::warning('Failed to fetch WhatsApp numbers', [
                            'business_id' => $businessId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        // Fallback: check legacy integrations table
        if (empty($accounts['whatsapp']) && empty($accounts['messenger']) && empty($accounts['instagram_dm'])) {
            $integrations = Integration::where('org_id', $orgId)
                ->whereIn('platform', ['meta', 'whatsapp', 'facebook'])
                ->where('status', 'active')
                ->get();

            foreach ($integrations as $integration) {
                $settings = $integration->settings ?? [];

                if (!empty($settings['phone_number_id'])) {
                    $accounts['whatsapp'][] = [
                        'id' => $settings['phone_number_id'],
                        'name' => $settings['phone_display'] ?? $settings['phone_number'] ?? $integration->name,
                        'integration_id' => $integration->id,
                    ];
                }

                if (in_array($integration->platform, ['facebook', 'meta'])) {
                    $accounts['messenger'][] = [
                        'id' => $integration->platform_account_id,
                        'name' => $integration->name,
                        'integration_id' => $integration->id,
                    ];
                }

                if (!empty($settings['instagram_account_id'])) {
                    $accounts['instagram_dm'][] = [
                        'id' => $settings['instagram_account_id'],
                        'name' => $settings['instagram_username'] ?? $integration->name,
                        'integration_id' => $integration->id,
                    ];
                }
            }
        }

        return $accounts;
    }

    /**
     * Fetch WhatsApp Business phone numbers from Meta API.
     */
    private function fetchWhatsAppBusinessNumbers(string $accessToken, string $businessId): array
    {
        $numbers = [];

        try {
            // Get WhatsApp Business Accounts owned by this business
            $response = \Http::get("https://graph.facebook.com/v21.0/{$businessId}/owned_whatsapp_business_accounts", [
                'access_token' => $accessToken,
                'fields' => 'id,name,phone_numbers{id,display_phone_number,verified_name,quality_rating}',
            ]);

            if ($response->successful()) {
                $data = $response->json('data', []);
                foreach ($data as $waba) {
                    $phoneNumbers = $waba['phone_numbers']['data'] ?? [];
                    foreach ($phoneNumbers as $phone) {
                        $numbers[] = [
                            'id' => $phone['id'],
                            'name' => $phone['verified_name'] ?? $phone['display_phone_number'],
                            'phone_number' => $phone['display_phone_number'] ?? '',
                            'waba_id' => $waba['id'],
                            'waba_name' => $waba['name'] ?? '',
                            'quality_rating' => $phone['quality_rating'] ?? null,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::warning('WhatsApp API error', ['error' => $e->getMessage()]);
        }

        return $numbers;
    }

    /**
     * Get the Business ID that owns a Facebook Page.
     *
     * @param string $accessToken Meta access token
     * @param string $pageId Facebook Page ID
     * @return string|null Business ID or null if not found
     */
    public function getBusinessIdFromPage(string $accessToken, string $pageId): ?string
    {
        try {
            $response = \Http::timeout(10)->get("https://graph.facebook.com/v21.0/{$pageId}", [
                'access_token' => $accessToken,
                'fields' => 'business{id,name}',
            ]);

            if ($response->successful()) {
                return $response->json('business.id');
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to get business from page', [
                'page_id' => $pageId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Get the Facebook Page ID linked to an Instagram account.
     *
     * Searches through connected pages to find which one has this Instagram account linked.
     *
     * @param string $accessToken Meta access token
     * @param string $instagramId Instagram account ID
     * @return string|null Facebook Page ID or null if not found
     */
    public function getPageIdFromInstagram(string $accessToken, string $instagramId): ?string
    {
        try {
            // Get all pages the user has access to, with their linked Instagram accounts
            $response = \Http::timeout(10)->get("https://graph.facebook.com/v21.0/me/accounts", [
                'access_token' => $accessToken,
                'fields' => 'id,name,instagram_business_account{id,username}',
            ]);

            if ($response->successful()) {
                $pages = $response->json('data', []);
                foreach ($pages as $page) {
                    $linkedIgAccount = $page['instagram_business_account'] ?? null;
                    if ($linkedIgAccount && $linkedIgAccount['id'] === $instagramId) {
                        return $page['id'];
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to get linked page from Instagram', [
                'instagram_id' => $instagramId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Get requirements for a specific destination type.
     *
     * @param string $platform The platform identifier
     * @param string $objectiveId The objective ID
     * @param string $destinationType The destination type ID
     * @return array List of required fields (e.g., ['url'], ['whatsapp_number'], ['page_id'])
     */
    public function getDestinationRequirements(string $platform, string $objectiveId, string $destinationType): array
    {
        $destinationTypes = $this->getDestinationTypes($platform, $objectiveId);

        foreach ($destinationTypes as $dest) {
            if ($dest['id'] === $destinationType) {
                return $dest['requires'] ?? [];
            }
        }

        return [];
    }

    /**
     * Get all supported platforms.
     *
     * @return array List of platform identifiers
     */
    public function getAllPlatforms(): array
    {
        return array_keys(config('boost-platforms', []));
    }

    /**
     * Get a summary of all platforms with basic info.
     *
     * @return array List of platforms with name and feature summary
     */
    public function getPlatformsSummary(): array
    {
        $platforms = config('boost-platforms', []);
        $summary = [];

        foreach ($platforms as $key => $config) {
            $summary[$key] = [
                'id' => $key,
                'name' => $config['name'] ?? ucfirst($key),
                'objectives_count' => count($config['objectives'] ?? []),
                'placements_count' => count($config['placements'] ?? []),
                'min_budget' => $config['min_budget'] ?? 1,
                'has_b2b_targeting' => !empty($config['b2b_targeting']),
                'has_spark_ads' => !empty($config['special_features']['spark_ads']),
                'has_advantage_plus' => !empty($config['special_features']['advantage_plus']),
            ];
        }

        return $summary;
    }
}
