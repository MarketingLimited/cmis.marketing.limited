<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * AdvancedSchedulingService
 *
 * Handles advanced scheduling features
 * Implements Sprint 6.3: Advanced Scheduling
 *
 * Features:
 * - Recurring post templates
 * - Queue management
 * - Post recycling
 * - Timezone handling
 * - Conflict resolution
 * - Bulk scheduling optimization
 */
class AdvancedSchedulingService
{
    /**
     * Create recurring post template
     *
     * @param array $data
     * @return array
     */
    public function createRecurringTemplate(array $data): array
    {
        try {
            DB::beginTransaction();

            $templateId = (string) Str::uuid();

            DB::table('cmis.recurring_post_templates')->insert([
                'template_id' => $templateId,
                'social_account_id' => $data['social_account_id'],
                'template_name' => $data['template_name'],
                'content_template' => $data['content_template'],
                'media_urls' => json_encode($data['media_urls'] ?? []),
                'hashtags' => json_encode($data['hashtags'] ?? []),
                'recurrence_pattern' => $data['recurrence_pattern'], // daily, weekly, monthly
                'recurrence_interval' => $data['recurrence_interval'] ?? 1,
                'days_of_week' => json_encode($data['days_of_week'] ?? []), // For weekly: [1,3,5] = Mon, Wed, Fri
                'time_of_day' => $data['time_of_day'], // HH:MM format
                'timezone' => $data['timezone'] ?? 'UTC',
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $data['created_by'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Generate initial schedule
            $this->generateRecurringPosts($templateId);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Recurring template created successfully',
                'data' => [
                    'template_id' => $templateId,
                    'template_name' => $data['template_name']
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to create recurring template',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate posts from recurring template
     *
     * @param string $templateId
     * @param int $daysAhead
     * @return array
     */
    public function generateRecurringPosts(string $templateId, int $daysAhead = 30): array
    {
        try {
            $template = DB::table('cmis.recurring_post_templates')
                ->where('template_id', $templateId)
                ->first();

            if (!$template) {
                return ['success' => false, 'message' => 'Template not found'];
            }

            if (!$template->is_active) {
                return ['success' => false, 'message' => 'Template is inactive'];
            }

            $startDate = Carbon::parse($template->start_date);
            $endDate = $template->end_date ? Carbon::parse($template->end_date) : now()->addDays($daysAhead);
            $timezone = $template->timezone;

            $scheduledDates = [];

            // Generate dates based on recurrence pattern
            switch ($template->recurrence_pattern) {
                case 'daily':
                    $scheduledDates = $this->generateDailySchedule($startDate, $endDate, $template->recurrence_interval, $template->time_of_day, $timezone);
                    break;

                case 'weekly':
                    $daysOfWeek = json_decode($template->days_of_week, true);
                    $scheduledDates = $this->generateWeeklySchedule($startDate, $endDate, $daysOfWeek, $template->time_of_day, $timezone);
                    break;

                case 'monthly':
                    $scheduledDates = $this->generateMonthlySchedule($startDate, $endDate, $template->recurrence_interval, $template->time_of_day, $timezone);
                    break;
            }

            // Create posts for each scheduled date
            $createdCount = 0;
            foreach ($scheduledDates as $scheduledFor) {
                // Check if post already exists for this date
                $exists = DB::table('cmis.social_posts')
                    ->where('social_account_id', $template->social_account_id)
                    ->where('recurring_template_id', $templateId)
                    ->where('scheduled_for', $scheduledFor)
                    ->exists();

                if (!$exists) {
                    $this->createPostFromTemplate($template, $scheduledFor);
                    $createdCount++;
                }
            }

            return [
                'success' => true,
                'message' => "Generated {$createdCount} posts from template",
                'data' => [
                    'posts_created' => $createdCount,
                    'next_scheduled' => $scheduledDates[0] ?? null
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to generate recurring posts',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get scheduling queue
     *
     * @param string $accountId
     * @param array $filters
     * @return array
     */
    public function getSchedulingQueue(string $accountId, array $filters = []): array
    {
        try {
            $query = DB::table('cmis.social_posts')
                ->where('social_account_id', $accountId)
                ->where('status', 'scheduled')
                ->whereNotNull('scheduled_for')
                ->where('scheduled_for', '>', now());

            // Apply filters
            if (!empty($filters['start_date'])) {
                $query->where('scheduled_for', '>=', $filters['start_date']);
            }

            if (!empty($filters['end_date'])) {
                $query->where('scheduled_for', '<=', $filters['end_date']);
            }

            $posts = $query->orderBy('scheduled_for', 'asc')->get();

            // Detect conflicts
            $conflicts = $this->detectSchedulingConflicts($posts);

            return [
                'success' => true,
                'data' => [
                    'queue' => $posts->map(function ($post) {
                        return [
                            'post_id' => $post->post_id,
                            'content' => substr($post->content, 0, 100) . '...',
                            'scheduled_for' => $post->scheduled_for,
                            'status' => $post->status,
                            'is_recurring' => !empty($post->recurring_template_id)
                        ];
                    }),
                    'conflicts' => $conflicts,
                    'total' => $posts->count()
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get scheduling queue',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Recycle a post
     *
     * @param string $postId
     * @param array $options
     * @return array
     */
    public function recyclePost(string $postId, array $options = []): array
    {
        try {
            $originalPost = DB::table('cmis.social_posts')->where('post_id', $postId)->first();

            if (!$originalPost) {
                return ['success' => false, 'message' => 'Post not found'];
            }

            // Create new post based on original
            $newPostId = (string) Str::uuid();

            $scheduledFor = isset($options['scheduled_for'])
                ? Carbon::parse($options['scheduled_for'])
                : now()->addWeeks(4); // Default: recycle after 4 weeks

            DB::table('cmis.social_posts')->insert([
                'post_id' => $newPostId,
                'social_account_id' => $originalPost->social_account_id,
                'content' => $options['content'] ?? $originalPost->content,
                'media_urls' => $originalPost->media_urls,
                'hashtags' => $originalPost->hashtags,
                'scheduled_for' => $scheduledFor,
                'status' => 'scheduled',
                'original_post_id' => $postId,
                'is_recycled' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return [
                'success' => true,
                'message' => 'Post recycled successfully',
                'data' => [
                    'new_post_id' => $newPostId,
                    'scheduled_for' => $scheduledFor->toDateTimeString()
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to recycle post',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Resolve scheduling conflicts
     *
     * @param string $accountId
     * @param string $strategy
     * @return array
     */
    public function resolveConflicts(string $accountId, string $strategy = 'space_evenly'): array
    {
        try {
            $posts = DB::table('cmis.social_posts')
                ->where('social_account_id', $accountId)
                ->where('status', 'scheduled')
                ->orderBy('scheduled_for', 'asc')
                ->get();

            $conflicts = $this->detectSchedulingConflicts($posts);

            if (empty($conflicts)) {
                return [
                    'success' => true,
                    'message' => 'No conflicts found',
                    'data' => ['conflicts_resolved' => 0]
                ];
            }

            $resolved = 0;

            foreach ($conflicts as $conflict) {
                $conflictingPosts = $conflict['posts'];

                switch ($strategy) {
                    case 'space_evenly':
                        $resolved += $this->spacePostsEvenly($conflictingPosts);
                        break;

                    case 'prioritize_important':
                        $resolved += $this->prioritizeImportantPosts($conflictingPosts);
                        break;

                    case 'move_to_optimal':
                        $resolved += $this->moveToOptimalTimes($conflictingPosts);
                        break;
                }
            }

            return [
                'success' => true,
                'message' => 'Conflicts resolved',
                'data' => [
                    'conflicts_found' => count($conflicts),
                    'conflicts_resolved' => $resolved
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to resolve conflicts',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Bulk reschedule posts
     *
     * @param array $postIds
     * @param array $options
     * @return array
     */
    public function bulkReschedule(array $postIds, array $options): array
    {
        try {
            $strategy = $options['strategy'] ?? 'preserve_order'; // preserve_order, optimize_times
            $startDate = Carbon::parse($options['start_date']);
            $timezone = $options['timezone'] ?? 'UTC';

            DB::beginTransaction();

            $posts = DB::table('cmis.social_posts')
                ->whereIn('post_id', $postIds)
                ->orderBy('scheduled_for', 'asc')
                ->get();

            $rescheduled = 0;
            $currentDate = $startDate->copy();

            foreach ($posts as $post) {
                if ($strategy === 'optimize_times') {
                    // Get optimal time for the day
                    $optimalHour = $this->getOptimalHourForDay($post->social_account_id, $currentDate->dayOfWeek);
                    $currentDate->setTime($optimalHour, 0);
                } else {
                    // Preserve original time of day
                    $originalTime = Carbon::parse($post->scheduled_for);
                    $currentDate->setTime($originalTime->hour, $originalTime->minute);
                }

                DB::table('cmis.social_posts')
                    ->where('post_id', $post->post_id)
                    ->update([
                        'scheduled_for' => $currentDate->setTimezone('UTC'),
                        'updated_at' => now()
                    ]);

                $rescheduled++;
                $currentDate->addDay();
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Rescheduled {$rescheduled} posts",
                'data' => [
                    'posts_rescheduled' => $rescheduled,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $currentDate->toDateString()
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to bulk reschedule',
                'error' => $e->getMessage()
            ];
        }
    }

    // Helper methods

    protected function generateDailySchedule(Carbon $start, Carbon $end, int $interval, string $timeOfDay, string $timezone): array
    {
        $dates = [];
        $current = $start->copy()->setTimezone($timezone);
        list($hour, $minute) = explode(':', $timeOfDay);
        $current->setTime((int)$hour, (int)$minute);

        while ($current->lte($end)) {
            $dates[] = $current->copy()->setTimezone('UTC')->toDateTimeString();
            $current->addDays($interval);
        }

        return $dates;
    }

    protected function generateWeeklySchedule(Carbon $start, Carbon $end, array $daysOfWeek, string $timeOfDay, string $timezone): array
    {
        $dates = [];
        $current = $start->copy()->setTimezone($timezone);
        list($hour, $minute) = explode(':', $timeOfDay);

        while ($current->lte($end)) {
            if (in_array($current->dayOfWeek, $daysOfWeek)) {
                $scheduled = $current->copy()->setTime((int)$hour, (int)$minute);
                $dates[] = $scheduled->setTimezone('UTC')->toDateTimeString();
            }
            $current->addDay();
        }

        return $dates;
    }

    protected function generateMonthlySchedule(Carbon $start, Carbon $end, int $dayOfMonth, string $timeOfDay, string $timezone): array
    {
        $dates = [];
        $current = $start->copy()->setTimezone($timezone);
        list($hour, $minute) = explode(':', $timeOfDay);

        while ($current->lte($end)) {
            $scheduled = $current->copy()->day($dayOfMonth)->setTime((int)$hour, (int)$minute);
            if ($scheduled->gte($start) && $scheduled->lte($end)) {
                $dates[] = $scheduled->setTimezone('UTC')->toDateTimeString();
            }
            $current->addMonth();
        }

        return $dates;
    }

    protected function createPostFromTemplate($template, string $scheduledFor): void
    {
        $postId = (string) Str::uuid();

        DB::table('cmis.social_posts')->insert([
            'post_id' => $postId,
            'social_account_id' => $template->social_account_id,
            'content' => $template->content_template,
            'media_urls' => $template->media_urls,
            'hashtags' => $template->hashtags,
            'scheduled_for' => $scheduledFor,
            'status' => 'scheduled',
            'recurring_template_id' => $template->template_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    protected function detectSchedulingConflicts($posts): array
    {
        $conflicts = [];
        $timeWindow = 30; // minutes

        for ($i = 0; $i < count($posts); $i++) {
            $post1 = $posts[$i];
            $time1 = Carbon::parse($post1->scheduled_for);

            $conflictingPosts = [$post1];

            for ($j = $i + 1; $j < count($posts); $j++) {
                $post2 = $posts[$j];
                $time2 = Carbon::parse($post2->scheduled_for);

                if (abs($time1->diffInMinutes($time2)) < $timeWindow) {
                    $conflictingPosts[] = $post2;
                }
            }

            if (count($conflictingPosts) > 1) {
                $conflicts[] = [
                    'time' => $time1->toDateTimeString(),
                    'posts' => $conflictingPosts,
                    'count' => count($conflictingPosts)
                ];
            }
        }

        return $conflicts;
    }

    protected function spacePostsEvenly(array $posts): int
    {
        $spacing = 120; // minutes
        $baseTime = Carbon::parse($posts[0]->scheduled_for);

        for ($i = 1; $i < count($posts); $i++) {
            $newTime = $baseTime->copy()->addMinutes($spacing * $i);
            DB::table('cmis.social_posts')
                ->where('post_id', $posts[$i]->post_id)
                ->update(['scheduled_for' => $newTime, 'updated_at' => now()]);
        }

        return count($posts) - 1;
    }

    protected function prioritizeImportantPosts(array $posts): int
    {
        // Keep first post, move others
        for ($i = 1; $i < count($posts); $i++) {
            $newTime = Carbon::parse($posts[$i]->scheduled_for)->addHours(2);
            DB::table('cmis.social_posts')
                ->where('post_id', $posts[$i]->post_id)
                ->update(['scheduled_for' => $newTime, 'updated_at' => now()]);
        }

        return count($posts) - 1;
    }

    protected function moveToOptimalTimes(array $posts): int
    {
        // Move to next optimal time slot
        $resolved = 0;
        foreach ($posts as $index => $post) {
            if ($index > 0) {
                // Get next optimal time
                $newTime = Carbon::parse($post->scheduled_for)->addHours(4);
                DB::table('cmis.social_posts')
                    ->where('post_id', $post->post_id)
                    ->update(['scheduled_for' => $newTime, 'updated_at' => now()]);
                $resolved++;
            }
        }
        return $resolved;
    }

    protected function getOptimalHourForDay(string $accountId, int $dayOfWeek): int
    {
        // Simplified - would use AI insights in production
        return match($dayOfWeek) {
            0, 6 => 10, // Weekend: 10 AM
            default => 14 // Weekday: 2 PM
        };
    }
}
