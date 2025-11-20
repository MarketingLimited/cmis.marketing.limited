<?php

namespace App\Jobs\Social;

use App\Models\Social\ScheduledSocialPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishToFacebookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $post;

    public function __construct(ScheduledSocialPost $post)
    {
        $this->post = $post;
    }

    public function handle(): array
    {
        $result = [
            'success' => true,
        ];

        // Stub implementation - would call Facebook Graph API
        // For testing, just update the status
        $this->post->update(['status' => 'published']);

        $result['platform'] = 'facebook';
        $result['post_id'] = $this->post->post_id;
        $result['published_at'] = now()->toIso8601String();

        return $result;
    }
}
