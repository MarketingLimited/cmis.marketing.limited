<?php

namespace App\Services\Social;

use App\Models\Social\ScheduledPost;
use App\Models\Social\PublishingQueue;
use App\Models\Social\BestTimeRecommendation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SchedulingService
{
    /**
     * Create and schedule a post.
     */
    public function schedulePost(string $orgId, string $userId, array $data): ScheduledPost
    {
        DB::beginTransaction();
        try {
            // Create scheduled post
            $post = ScheduledPost::create(array_merge($data, [
                'org_id' => $orgId,
                'created_by' => $userId,
                'status' => 'draft',
            ]));

            // If scheduled_at is provided, schedule the post
            if (isset($data['scheduled_at'])) {
                $post->schedule($data['scheduled_at']);

                // Add to publishing queue for each platform
                foreach ($post->platforms as $platform) {
                    $this->addToQueue($post, $platform);
                }
            }

            DB::commit();
            return $post;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to schedule post', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Add post to publishing queue.
     */
    protected function addToQueue(ScheduledPost $post, string $platform): PublishingQueue
    {
        return PublishingQueue::create([
            'org_id' => $post->org_id,
            'scheduled_post_id' => $post->post_id,
            'platform' => $platform,
            'status' => 'pending',
            'scheduled_for' => $post->scheduled_at,
            'max_attempts' => 3,
        ]);
    }

    /**
     * Reschedule a post.
     */
    public function reschedulePost(ScheduledPost $post, string $newTime): void
    {
        DB::beginTransaction();
        try {
            $post->update([
                'scheduled_at' => $newTime,
                'status' => 'scheduled',
            ]);

            // Update queue items
            PublishingQueue::where('scheduled_post_id', $post->post_id)
                ->where('status', 'pending')
                ->update(['scheduled_for' => $newTime]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel a scheduled post.
     */
    public function cancelPost(ScheduledPost $post): void
    {
        DB::beginTransaction();
        try {
            $post->cancel();

            // Remove from queue
            PublishingQueue::where('scheduled_post_id', $post->post_id)
                ->where('status', 'pending')
                ->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get posts due for publishing.
     */
    public function getDuePostsForPublishing(): array
    {
        return ScheduledPost::dueForPublishing()
            ->with(['platformPosts', 'queueItems'])
            ->get()
            ->filter(fn($post) => $post->canBePublished())
            ->toArray();
    }

    /**
     * Get best time to post for a platform.
     */
    public function getBestTimeToPost(string $orgId, string $platform, ?string $dayOfWeek = null): ?BestTimeRecommendation
    {
        $query = BestTimeRecommendation::where('org_id', $orgId)
            ->where('platform', $platform);

        if ($dayOfWeek) {
            $query->where('day_of_week', strtolower($dayOfWeek));
        }

        return $query->orderByDesc('engagement_score')->first();
    }

    /**
     * Suggest optimal posting time.
     */
    public function suggestPostingTime(string $orgId, string $platform, ?Carbon $preferredDate = null): Carbon
    {
        $date = $preferredDate ?? now()->addDay();
        $dayOfWeek = strtolower($date->format('l'));

        $bestTime = $this->getBestTimeToPost($orgId, $platform, $dayOfWeek);

        if ($bestTime) {
            return $date->setHour($bestTime->hour_of_day)->setMinute(0);
        }

        // Default: next day at 10 AM
        return $date->setHour(10)->setMinute(0);
    }

    /**
     * Bulk schedule posts.
     */
    public function bulkSchedule(string $orgId, string $userId, array $posts): array
    {
        $scheduled = [];

        foreach ($posts as $postData) {
            try {
                $scheduled[] = $this->schedulePost($orgId, $userId, $postData);
            } catch (\Exception $e) {
                Log::error('Failed to bulk schedule post', [
                    'post_data' => $postData,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $scheduled;
    }

    /**
     * Calculate best time recommendations for an organization.
     */
    public function calculateBestTimes(string $orgId): void
    {
        $platforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok'];
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($platforms as $platform) {
            foreach ($daysOfWeek as $day) {
                for ($hour = 0; $hour < 24; $hour++) {
                    $this->calculateBestTimeForSlot($orgId, $platform, $day, $hour);
                }
            }
        }
    }

    /**
     * Calculate best time for specific time slot.
     */
    protected function calculateBestTimeForSlot(string $orgId, string $platform, string $dayOfWeek, int $hour): void
    {
        // Get posts published at this time slot
        $posts = ScheduledPost::where('org_id', $orgId)
            ->whereJsonContains('platforms', $platform)
            ->where('status', 'published')
            ->whereRaw('EXTRACT(DOW FROM scheduled_at) = ?', [$this->getDayOfWeekNumber($dayOfWeek)])
            ->whereRaw('EXTRACT(HOUR FROM scheduled_at) = ?', [$hour])
            ->with('platformPosts')
            ->get();

        if ($posts->isEmpty()) {
            return;
        }

        // Calculate engagement metrics
        $totalEngagement = 0;
        $totalViews = 0;
        $sampleSize = 0;

        foreach ($posts as $post) {
            $platformPost = $post->platformPosts()->where('platform', $platform)->first();
            if ($platformPost) {
                $totalEngagement += $platformPost->engagement;
                $totalViews += $platformPost->views;
                $sampleSize++;
            }
        }

        if ($sampleSize === 0) {
            return;
        }

        $avgEngagementRate = $totalViews > 0 ? ($totalEngagement / $totalViews) * 100 : 0;
        $engagementScore = min($avgEngagementRate * 10, 100); // Normalize to 0-100

        // Update or create recommendation
        BestTimeRecommendation::updateOrCreate(
            [
                'org_id' => $orgId,
                'platform' => $platform,
                'day_of_week' => $dayOfWeek,
                'hour_of_day' => $hour,
            ],
            [
                'engagement_score' => $engagementScore,
                'sample_size' => $sampleSize,
                'avg_engagement_rate' => $avgEngagementRate,
                'calculated_at' => now(),
            ]
        );
    }

    /**
     * Get day of week number (0 = Sunday, 6 = Saturday).
     */
    protected function getDayOfWeekNumber(string $dayOfWeek): int
    {
        $days = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];

        return $days[strtolower($dayOfWeek)] ?? 0;
    }
}
