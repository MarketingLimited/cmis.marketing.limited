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
        'meta' => MetaConnector::class,
        'facebook' => MetaConnector::class,
        'instagram' => MetaConnector::class,
        'google' => GoogleConnector::class,
        'google_ads' => GoogleConnector::class,
        'google_analytics' => GoogleConnector::class,
        // Add other connectors here as they are implemented
        // 'tiktok' => \App\Services\Connectors\Providers\TikTokConnector::class,
        // 'linkedin' => \App\Services\Connectors\Providers\LinkedInConnector::class,
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
