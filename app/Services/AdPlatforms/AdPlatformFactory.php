<?php

namespace App\Services\AdPlatforms;

use App\Models\Core\Integration;
use App\Services\AdPlatforms\Contracts\AdPlatformInterface;
use App\Services\AdPlatforms\Meta\MetaAdsPlatform;
use App\Services\AdPlatforms\Google\GoogleAdsPlatform;
use App\Services\AdPlatforms\TikTok\TikTokAdsPlatform;
use App\Services\AdPlatforms\LinkedIn\LinkedInAdsPlatform;
use App\Services\AdPlatforms\Twitter\TwitterAdsPlatform;
use App\Services\AdPlatforms\Snapchat\SnapchatAdsPlatform;
use App\Services\FeatureToggle\FeatureFlagService;
use App\Exceptions\FeatureDisabledException;

/**
 * Factory for creating Ad Platform Service instances
 *
 * Creates the appropriate platform-specific service based on integration platform type.
 *
 * @package App\Services\AdPlatforms
 */
class AdPlatformFactory
{
    /**
     * Create a platform service instance
     *
     * @param Integration $integration The platform integration
     * @return AdPlatformInterface
     * @throws \InvalidArgumentException If platform is not supported
     * @throws FeatureDisabledException If paid campaigns are disabled for this platform
     */
    public static function make(Integration $integration): AdPlatformInterface
    {
        // Get canonical platform name
        $platform = self::getCanonicalName($integration->platform);

        // Check if paid campaigns are enabled for this platform
        $featureFlags = app(FeatureFlagService::class);
        $featureKey = "paid_campaigns.{$platform}.enabled";

        if (!$featureFlags->isEnabled($featureKey)) {
            $availablePlatforms = $featureFlags->getEnabledPlatforms('paid_campaigns');

            throw new FeatureDisabledException(
                "Paid campaigns for {$platform} are not available. " .
                "Enabled platforms: " . implode(', ', $availablePlatforms) . ". " .
                "Contact your administrator to enable this feature."
            );
        }

        return match ($integration->platform) {
            'meta', 'facebook', 'instagram' => new MetaAdsPlatform($integration),
            'google', 'google_ads' => new GoogleAdsPlatform($integration),
            'tiktok' => new TikTokAdsPlatform($integration),
            'linkedin' => new LinkedInAdsPlatform($integration),
            'twitter', 'x' => new TwitterAdsPlatform($integration),
            'snapchat' => new SnapchatAdsPlatform($integration),
            default => throw new \InvalidArgumentException(
                "Unsupported ad platform: {$integration->platform}"
            ),
        };
    }

    /**
     * Get list of supported platforms
     *
     * @return array
     */
    public static function getSupportedPlatforms(): array
    {
        return [
            'meta' => [
                'name' => 'Meta Ads (Facebook & Instagram)',
                'aliases' => ['facebook', 'instagram'],
                'features' => ['campaigns', 'ad_sets', 'ads', 'audiences', 'insights'],
            ],
            'google' => [
                'name' => 'Google Ads',
                'aliases' => ['google_ads'],
                'features' => ['campaigns', 'ad_groups', 'ads', 'keywords', 'reports'],
            ],
            'tiktok' => [
                'name' => 'TikTok Ads',
                'aliases' => [],
                'features' => ['campaigns', 'ad_groups', 'ads', 'audiences'],
            ],
            'linkedin' => [
                'name' => 'LinkedIn Ads',
                'aliases' => [],
                'features' => ['campaigns', 'creatives', 'ads', 'targeting'],
            ],
            'twitter' => [
                'name' => 'X Ads (Twitter)',
                'aliases' => ['x'],
                'features' => ['campaigns', 'line_items', 'ads', 'targeting'],
            ],
            'snapchat' => [
                'name' => 'Snapchat Ads',
                'aliases' => [],
                'features' => ['campaigns', 'ad_squads', 'ads', 'audiences'],
            ],
        ];
    }

    /**
     * Check if a platform is supported
     *
     * @param string $platform Platform identifier
     * @return bool
     */
    public static function isSupported(string $platform): bool
    {
        $supported = self::getSupportedPlatforms();

        if (isset($supported[$platform])) {
            return true;
        }

        // Check aliases
        foreach ($supported as $platformData) {
            if (in_array($platform, $platformData['aliases'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get platform canonical name from alias
     *
     * @param string $platform Platform identifier or alias
     * @return string Canonical platform name
     */
    public static function getCanonicalName(string $platform): string
    {
        $supported = self::getSupportedPlatforms();

        if (isset($supported[$platform])) {
            return $platform;
        }

        // Check aliases
        foreach ($supported as $canonicalName => $platformData) {
            if (in_array($platform, $platformData['aliases'])) {
                return $canonicalName;
            }
        }

        return $platform;
    }

    /**
     * Get list of platforms with paid campaigns enabled
     *
     * @return array Array of enabled platform configurations
     */
    public static function getEnabledPlatforms(): array
    {
        $featureFlags = app(FeatureFlagService::class);
        $supported = self::getSupportedPlatforms();
        $enabled = [];

        foreach ($supported as $platform => $config) {
            if ($featureFlags->isEnabled("paid_campaigns.{$platform}.enabled")) {
                $enabled[$platform] = $config;
            }
        }

        return $enabled;
    }

    /**
     * Check if paid campaigns are enabled for a platform
     *
     * @param string $platform Platform identifier
     * @return bool
     */
    public static function isPlatformEnabled(string $platform): bool
    {
        $platform = self::getCanonicalName($platform);
        $featureFlags = app(FeatureFlagService::class);

        return $featureFlags->isEnabled("paid_campaigns.{$platform}.enabled");
    }
}
