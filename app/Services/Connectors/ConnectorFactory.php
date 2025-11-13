<?php

namespace App\Services\Connectors;

use App\Services\Connectors\Contracts\ConnectorInterface;
use App\Services\Connectors\Providers\MetaConnector;
use App\Services\Connectors\Providers\GoogleConnector;
use InvalidArgumentException;

/**
 * Factory class for creating connector instances.
 * This class abstracts the instantiation logic for different platform connectors.
 */
class ConnectorFactory
{
    /**
     * A map of platform identifiers to their connector classes.
     *
     * @var array<string, string>
     */
    protected static $connectorMap = [
        // Meta (Facebook & Instagram)
        'meta' => MetaConnector::class,
        'facebook' => MetaConnector::class,
        'instagram' => MetaConnector::class,

        // Google
        'google' => GoogleConnector::class,
        'google_ads' => GoogleConnector::class,
        'google_analytics' => GoogleConnector::class,

        // TikTok
        'tiktok' => \App\Services\Connectors\Providers\TikTokConnector::class,

        // Snapchat
        'snapchat' => \App\Services\Connectors\Providers\SnapchatConnector::class,

        // Twitter/X
        'twitter' => \App\Services\Connectors\Providers\TwitterConnector::class,
        'x' => \App\Services\Connectors\Providers\TwitterConnector::class,

        // LinkedIn
        'linkedin' => \App\Services\Connectors\Providers\LinkedInConnector::class,

        // YouTube
        'youtube' => \App\Services\Connectors\Providers\YouTubeConnector::class,

        // WooCommerce
        'woocommerce' => \App\Services\Connectors\Providers\WooCommerceConnector::class,

        // Microsoft Clarity
        'clarity' => \App\Services\Connectors\Providers\ClarityConnector::class,
        'microsoft_clarity' => \App\Services\Connectors\Providers\ClarityConnector::class,

        // Google Merchant Center
        'google_merchant' => \App\Services\Connectors\Providers\GoogleMerchantConnector::class,

        // Google Business
        'google_business' => \App\Services\Connectors\Providers\GoogleBusinessConnector::class,
    ];

    /**
     * Create a new connector instance for the given platform.
     *
     * @param string $platform The platform identifier (e.g., 'meta', 'google').
     * @return ConnectorInterface
     * @throws InvalidArgumentException If the platform is not supported.
     */
    public static function make(string $platform): ConnectorInterface
    {
        if (!isset(self::$connectorMap[$platform])) {
            throw new InvalidArgumentException("Unsupported connector platform provided: [{$platform}]");
        }

        $connectorClass = self::$connectorMap[$platform];

        if (!class_exists($connectorClass)) {
            throw new InvalidArgumentException("Connector class not found for platform [{$platform}]: [{$connectorClass}]");
        }

        // Use Laravel's service container to resolve the class.
        // This allows for dependency injection in the connector's constructor.
        $connector = app($connectorClass);

        if (!$connector instanceof ConnectorInterface) {
            throw new InvalidArgumentException("The class [{$connectorClass}] must implement ConnectorInterface.");
        }

        return $connector;
    }
}
