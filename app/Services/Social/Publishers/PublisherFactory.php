<?php

namespace App\Services\Social\Publishers;

use App\Models\Platform\PlatformConnection;

/**
 * Factory for creating platform-specific publishers.
 */
class PublisherFactory
{
    protected string $orgId;
    protected array $publishers = [];

    /**
     * Get a publisher for the specified platform.
     *
     * @param string $platform Platform name (facebook, instagram, google_business, etc.)
     * @param string $orgId Organization ID
     * @return AbstractPublisher|null
     */
    public function getPublisher(string $platform, string $orgId): ?AbstractPublisher
    {
        $this->orgId = $orgId;

        // Map platform to publisher class
        $platformType = $this->getPlatformType($platform);
        $publisherClass = $this->getPublisherClass($platformType);

        if (!$publisherClass) {
            return null;
        }

        // Get or create publisher instance
        $key = "{$platformType}_{$orgId}";
        if (!isset($this->publishers[$key])) {
            $connection = $this->getConnection($platformType, $orgId);
            $this->publishers[$key] = new $publisherClass($connection, $platform);
        }

        return $this->publishers[$key];
    }

    /**
     * Get the platform type from the platform name.
     */
    protected function getPlatformType(string $platform): string
    {
        return match ($platform) {
            'facebook', 'instagram' => 'meta',
            'google_business' => 'google',
            default => $platform,
        };
    }

    /**
     * Get the publisher class for a platform type.
     */
    protected function getPublisherClass(string $platformType): ?string
    {
        return match ($platformType) {
            'meta' => MetaPublisher::class,
            'google' => GoogleBusinessPublisher::class,
            'twitter' => TwitterPublisher::class,
            'linkedin' => LinkedInPublisher::class,
            'tiktok' => TikTokPublisher::class,
            default => null,
        };
    }

    /**
     * Get the platform connection for the organization.
     */
    protected function getConnection(string $platformType, string $orgId): ?PlatformConnection
    {
        return PlatformConnection::where('org_id', $orgId)
            ->where('platform', $platformType)
            ->where('status', 'active')
            ->first();
    }
}
