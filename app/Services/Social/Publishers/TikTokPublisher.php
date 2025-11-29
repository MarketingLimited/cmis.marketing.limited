<?php

namespace App\Services\Social\Publishers;

/**
 * Publisher for TikTok.
 *
 * TODO: Implement TikTok API publishing.
 */
class TikTokPublisher extends AbstractPublisher
{
    public function publish(string $content, array $media, array $options = []): array
    {
        return $this->failure('TikTok publishing not yet implemented. Please check back soon.');
    }
}
