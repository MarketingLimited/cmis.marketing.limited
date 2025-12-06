<?php

namespace App\Listeners\Content;

use App\Events\Content\PostScheduled;
use App\Jobs\Social\PublishScheduledPostJob;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\{Cache, Log};
use Carbon\Carbon;

/**
 * Handles actions when post is scheduled
 */
class NotifyPostScheduled implements ShouldQueue
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle post scheduled event
     */
    public function handle(PostScheduled $event): void
    {
        $post = $event->post;

        Log::info('NotifyPostScheduled::handle - Post scheduled', [
            'post_id' => $post->post_id,
            'platforms' => $post->platforms,
            'scheduled_for' => $post->scheduled_for,
            'org_id' => $post->org_id,
        ]);

        // Clear dashboard cache to show new scheduled post
        Cache::forget("dashboard:org:{$post->org_id}");
        Cache::forget("content_calendar:org:{$post->org_id}");

        // Get the user who scheduled the post
        $userId = $post->created_by ?? $post->user_id ?? null;

        if ($userId) {
            $platforms = is_array($post->platforms) ? implode(', ', $post->platforms) : $post->platforms;
            $scheduledTime = Carbon::parse($post->scheduled_for)->format('M d, Y \a\t h:i A');

            $this->notificationService->notify(
                $userId,
                NotificationService::TYPE_SYSTEM_ALERT,
                __('notifications.post_scheduled_title'),
                __('notifications.post_scheduled_message', [
                    'platforms' => $platforms,
                    'time' => $scheduledTime,
                ]),
                [
                    'org_id' => $post->org_id,
                    'priority' => NotificationService::PRIORITY_LOW,
                    'category' => 'content',
                    'related_entity_type' => 'social_post',
                    'related_entity_id' => $post->post_id,
                    'data' => [
                        'post_id' => $post->post_id,
                        'platforms' => $post->platforms,
                        'scheduled_for' => $post->scheduled_for,
                    ],
                    'action_url' => route('social.posts.show', ['id' => $post->post_id], false),
                    'channels' => ['in_app'],
                ]
            );
        }

        // Schedule the publication job
        $scheduledTime = Carbon::parse($post->scheduled_for);
        if ($scheduledTime->isFuture()) {
            PublishScheduledPostJob::dispatch($post->post_id)
                ->delay($scheduledTime);

            Log::info('Publication job scheduled', [
                'post_id' => $post->post_id,
                'scheduled_for' => $scheduledTime->toDateTimeString(),
            ]);
        }
    }
}
