<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FeatureToggle\FeatureFlagService;
use Illuminate\Http\JsonResponse;

/**
 * API Controller for feature flags (used by frontend)
 */
class FeatureController extends Controller
{
    public function __construct(
        protected FeatureFlagService $featureFlags
    ) {}

    /**
     * Get available platforms with their enabled features
     *
     * GET /api/features/available-platforms
     *
     * @return JsonResponse
     */
    public function getAvailablePlatforms(): JsonResponse
    {
        $platforms = [
            'meta' => ['name' => 'Meta', 'display_name' => 'Meta (Facebook & Instagram)', 'logo' => 'meta-logo.png'],
            'google' => ['name' => 'Google', 'display_name' => 'Google Ads', 'logo' => 'google-logo.png'],
            'tiktok' => ['name' => 'TikTok', 'display_name' => 'TikTok Ads', 'logo' => 'tiktok-logo.png'],
            'linkedin' => ['name' => 'LinkedIn', 'display_name' => 'LinkedIn Ads', 'logo' => 'linkedin-logo.png'],
            'twitter' => ['name' => 'Twitter', 'display_name' => 'Twitter Ads', 'logo' => 'twitter-logo.png'],
            'snapchat' => ['name' => 'Snapchat', 'display_name' => 'Snapchat Ads', 'logo' => 'snapchat-logo.png'],
        ];

        $availablePlatforms = [];

        foreach ($platforms as $key => $info) {
            $features = $this->getPlatformFeatures($key);

            $availablePlatforms[] = [
                'key' => $key,
                'name' => $info['name'],
                'display_name' => $info['display_name'],
                'logo' => $info['logo'],
                'enabled' => $this->isPlatformEnabled($key),
                'features' => $features,
            ];
        }

        return response()->json([
            'platforms' => $availablePlatforms,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get feature matrix for all platforms
     *
     * GET /api/features/matrix
     *
     * @return JsonResponse
     */
    public function getFeatureMatrix(): JsonResponse
    {
        $platforms = ['meta', 'google', 'tiktok', 'linkedin', 'twitter', 'snapchat'];
        $features = ['scheduling', 'paid_campaigns', 'analytics', 'organic_posts'];

        $matrix = [];

        foreach ($features as $feature) {
            $matrix[$feature] = [];
            foreach ($platforms as $platform) {
                $matrix[$feature][$platform] = $this->featureFlags->isEnabled(
                    "{$feature}.{$platform}.enabled"
                );
            }
        }

        return response()->json([
            'matrix' => $matrix,
            'features' => $features,
            'platforms' => $platforms,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get enabled platforms for a specific feature category
     *
     * GET /api/features/enabled-platforms/{category}
     *
     * @param string $category (e.g., 'scheduling', 'paid_campaigns')
     * @return JsonResponse
     */
    public function getEnabledPlatformsForFeature(string $category): JsonResponse
    {
        $enabledPlatforms = $this->featureFlags->getEnabledPlatforms($category);

        return response()->json([
            'feature_category' => $category,
            'enabled_platforms' => $enabledPlatforms,
            'count' => count($enabledPlatforms),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Check if a specific feature is enabled
     *
     * GET /api/features/check/{featureKey}
     *
     * @param string $featureKey
     * @return JsonResponse
     */
    public function checkFeature(string $featureKey): JsonResponse
    {
        $enabled = $this->featureFlags->isEnabled($featureKey);

        return response()->json([
            'feature_key' => $featureKey,
            'enabled' => $enabled,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get all features for a platform
     *
     * @param string $platform
     * @return array
     */
    protected function getPlatformFeatures(string $platform): array
    {
        $featureCategories = [
            'scheduling' => 'Post Scheduling',
            'paid_campaigns' => 'Paid Campaigns',
            'analytics' => 'Analytics & Reporting',
            'organic_posts' => 'Organic Posts',
        ];

        $features = [];

        foreach ($featureCategories as $key => $label) {
            $featureKey = "{$key}.{$platform}.enabled";
            $features[$key] = [
                'enabled' => $this->featureFlags->isEnabled($featureKey),
                'label' => $label,
            ];
        }

        return $features;
    }

    /**
     * Check if platform is enabled (has at least one feature enabled)
     *
     * @param string $platform
     * @return bool
     */
    protected function isPlatformEnabled(string $platform): bool
    {
        $featureCategories = ['scheduling', 'paid_campaigns', 'analytics', 'organic_posts'];

        foreach ($featureCategories as $category) {
            if ($this->featureFlags->isEnabled("{$category}.{$platform}.enabled")) {
                return true;
            }
        }

        return false;
    }
}
