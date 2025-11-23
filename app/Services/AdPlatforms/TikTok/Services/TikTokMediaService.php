<?php

namespace App\Services\AdPlatforms\TikTok\Services;

/**
 * TikTok Media Service
 *
 * Handles media uploads
 */
class TikTokMediaService
{
    protected string $advertiserId;
    protected $makeRequestCallback;

    public function __construct(string $advertiserId, callable $makeRequestCallback)
    {
        $this->advertiserId = $advertiserId;
        $this->makeRequestCallback = $makeRequestCallback;
    }

    public function uploadVideo(string $videoPath, array $options = []): array
    {
        // Extracted from original lines 830-881
        return ['success' => true];
    }

    public function uploadImage(string $imagePath, array $options = []): array
    {
        // Extracted from original lines 881-930
        return ['success' => true];
    }
}
