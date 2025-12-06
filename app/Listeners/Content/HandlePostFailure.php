<?php

namespace App\Listeners\Content;

use App\Events\Content\PostFailed;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles actions when post publishing fails
 */
class HandlePostFailure implements ShouldQueue
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle post failure event
     */
    public function handle(PostFailed $event): void
    {
        $post = $event->post;

        Log::error('HandlePostFailure::handle - Post publishing failed', [
            'post_id' => $post->post_id,
            'platforms' => $post->platforms,
            'error' => $event->error,
            'org_id' => $post->org_id,
        ]);

        // Get the user who created the post
        $userId = $post->created_by ?? $post->user_id ?? null;

        if ($userId) {
            // Send notification to post creator
            $platforms = is_array($post->platforms) ? implode(', ', $post->platforms) : $post->platforms;

            $this->notificationService->notifyPublishingFailure(
                $userId,
                $post->post_id,
                $platforms,
                $event->error,
                [
                    'org_id' => $post->org_id,
                    'data' => [
                        'post_id' => $post->post_id,
                        'platforms' => $post->platforms,
                        'error' => $event->error,
                        'content_preview' => substr($post->content ?? '', 0, 100),
                    ],
                ]
            );
        }

        // Update post status to failed
        DB::table('cmis_social.social_posts')
            ->where('post_id', $post->post_id)
            ->update([
                'status' => 'failed',
                'error_message' => $event->error,
                'failed_at' => now(),
                'updated_at' => now(),
            ]);

        // Log to incident tracking (create alert record)
        DB::table('cmis.alerts')->insert([
            'org_id' => $post->org_id,
            'type' => 'publishing_failed',
            'severity' => 'high',
            'title' => __('notifications.post_publishing_failed'),
            'message' => $event->error,
            'related_entity_type' => 'social_post',
            'related_entity_id' => $post->post_id,
            'is_read' => false,
            'created_at' => now(),
        ]);
    }
}
