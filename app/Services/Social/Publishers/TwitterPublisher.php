<?php

namespace App\Services\Social\Publishers;

/**
 * Publisher for Twitter/X.
 *
 * TODO: Implement Twitter API v2 publishing.
 */
class TwitterPublisher extends AbstractPublisher
{
    public function publish(string $content, array $media, array $options = []): array
    {
        return $this->failure('Twitter publishing not yet implemented. Please check back soon.');
    }
}
