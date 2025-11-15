<?php

namespace App\Listeners\Content;

use App\Events\Content\PostScheduled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\{Cache, Log};

/**
 * Handles actions when post is scheduled
 */
class NotifyPostScheduled implements ShouldQueue
{
    public function handle(PostScheduled $event): void
    {
        $post = $event->post;

        Log::info('Post scheduled', [
            'post_id' => $post->post_id,
            'platforms' => $post->platforms,
            'scheduled_for' => $post->scheduled_for,
            'org_id' => $post->org_id,
        ]);

        // Clear dashboard cache to show new scheduled post
        Cache::forget("dashboard:org:{$post->org_id}");

        // TODO: Send confirmation notification
        // TODO: Schedule publication job
        // TODO: Update content calendar
    }
}
