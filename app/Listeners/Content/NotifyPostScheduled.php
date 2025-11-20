<?php

namespace App\Listeners\Content;

use App\Events\Content\PostScheduled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\{Cache, Log};

/**
 * Handles actions when post is scheduled
 * Note: Stub implementation
 */
class NotifyPostScheduled implements ShouldQueue
{
    /**
     * Handle post scheduled event
     *
     * @param PostScheduled $event Post scheduled event
     * @return void
     */
    public function handle(PostScheduled $event): void
    {
        $post = $event->post;

        Log::info('NotifyPostScheduled::handle called (stub) - Post scheduled', [
            'post_id' => $post->post_id,
            'platforms' => $post->platforms,
            'scheduled_for' => $post->scheduled_for,
            'org_id' => $post->org_id,
        ]);

        // Clear dashboard cache to show new scheduled post
        Cache::forget("dashboard:org:{$post->org_id}");

        // Stub implementation - Send confirmation notification
        // Stub implementation - Schedule publication job
        // Stub implementation - Update content calendar
    }
}
