<?php
namespace App\Services\Social;

class YouTubeService
{
    public function uploadVideo($videoPath, array $metadata)
    {
        \Log::info("YouTubeService::uploadVideo", ['path' => $videoPath, 'metadata' => $metadata]);
        
        return [
            'success' => true,
            'data' => [
                'video_id' => 'yt_' . uniqid(),
                'url' => 'https://youtube.com/watch?v=' . uniqid()
            ]
        ];
    }
}
