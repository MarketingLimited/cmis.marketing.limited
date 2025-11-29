<?php

namespace App\Services\Social\Publishers;

/**
 * Publisher for LinkedIn.
 *
 * TODO: Implement LinkedIn API publishing.
 */
class LinkedInPublisher extends AbstractPublisher
{
    public function publish(string $content, array $media, array $options = []): array
    {
        return $this->failure('LinkedIn publishing not yet implemented. Please check back soon.');
    }
}
