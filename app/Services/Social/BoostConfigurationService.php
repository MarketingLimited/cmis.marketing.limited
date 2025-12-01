<?php

namespace App\Services\Social;

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
                return [
                    'id' => $obj['id'],
                    'name' => $obj['name_ar'] ?? $obj['name'],
                    'description' => $obj['description_ar'] ?? $obj['description'] ?? '',
                ];
            }, $objectives);
        }

        return $objectives;
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
