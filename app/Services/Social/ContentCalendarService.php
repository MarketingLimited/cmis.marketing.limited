<?php

namespace App\Services\Social;

use App\Models\Social\ScheduledPost;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContentCalendarService
{
    /**
     * Get calendar view for a date range.
     */
    public function getCalendar(string $orgId, Carbon $startDate, Carbon $endDate): array
    {
        $posts = ScheduledPost::where('org_id', $orgId)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->with(['creator', 'platformPosts'])
            ->orderBy('scheduled_at')
            ->get();

        // Group by date
        $calendar = [];
        foreach ($posts as $post) {
            $date = $post->scheduled_at->format('Y-m-d');

            if (!isset($calendar[$date])) {
                $calendar[$date] = [
                    'date' => $date,
                    'day_of_week' => $post->scheduled_at->format('l'),
                    'posts' => [],
                    'total_posts' => 0,
                ];
            }

            $calendar[$date]['posts'][] = [
                'post_id' => $post->post_id,
                'title' => $post->title,
                'content' => $post->content,
                'scheduled_at' => $post->scheduled_at->format('H:i'),
                'platforms' => $post->platforms,
                'status' => $post->status,
                'post_type' => $post->post_type,
            ];

            $calendar[$date]['total_posts']++;
        }

        return array_values($calendar);
    }

    /**
     * Get monthly overview.
     */
    public function getMonthlyOverview(string $orgId, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $posts = ScheduledPost::where('org_id', $orgId)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->get();

        // Count by status
        $statusCounts = [
            'scheduled' => 0,
            'published' => 0,
            'draft' => 0,
            'failed' => 0,
        ];

        foreach ($posts as $post) {
            if (isset($statusCounts[$post->status])) {
                $statusCounts[$post->status]++;
            }
        }

        // Count by platform
        $platformCounts = [];
        foreach ($posts as $post) {
            foreach ($post->platforms as $platform) {
                $platformCounts[$platform] = ($platformCounts[$platform] ?? 0) + 1;
            }
        }

        // Posts per day
        $postsPerDay = $posts->groupBy(fn($post) => $post->scheduled_at->format('Y-m-d'))
            ->map(fn($group) => $group->count())
            ->toArray();

        return [
            'month' => $startDate->format('F Y'),
            'total_posts' => $posts->count(),
            'status_counts' => $statusCounts,
            'platform_counts' => $platformCounts,
            'posts_per_day' => $postsPerDay,
            'avg_posts_per_day' => $posts->count() / $endDate->day,
        ];
    }

    /**
     * Get posting frequency analysis.
     */
    public function getPostingFrequency(string $orgId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $posts = ScheduledPost::where('org_id', $orgId)
            ->where('scheduled_at', '>=', $startDate)
            ->get();

        // Group by day of week
        $dayOfWeekCounts = [];
        foreach ($posts as $post) {
            $day = $post->scheduled_at->format('l');
            $dayOfWeekCounts[$day] = ($dayOfWeekCounts[$day] ?? 0) + 1;
        }

        // Group by hour
        $hourCounts = array_fill(0, 24, 0);
        foreach ($posts as $post) {
            $hour = (int) $post->scheduled_at->format('H');
            $hourCounts[$hour]++;
        }

        return [
            'day_of_week' => $dayOfWeekCounts,
            'hour_of_day' => $hourCounts,
            'total_posts' => $posts->count(),
            'avg_per_day' => $posts->count() / $days,
        ];
    }

    /**
     * Check for scheduling conflicts.
     */
    public function checkConflicts(string $orgId, Carbon $scheduledAt, array $platforms): array
    {
        $conflicts = [];
        $timeWindow = 15; // minutes

        foreach ($platforms as $platform) {
            $existingPosts = ScheduledPost::where('org_id', $orgId)
                ->where('status', 'scheduled')
                ->whereJsonContains('platforms', $platform)
                ->whereBetween('scheduled_at', [
                    $scheduledAt->copy()->subMinutes($timeWindow),
                    $scheduledAt->copy()->addMinutes($timeWindow),
                ])
                ->get();

            if ($existingPosts->isNotEmpty()) {
                $conflicts[$platform] = $existingPosts->map(fn($post) => [
                    'post_id' => $post->post_id,
                    'title' => $post->title,
                    'scheduled_at' => $post->scheduled_at->toISOString(),
                ])->toArray();
            }
        }

        return $conflicts;
    }

    /**
     * Get content gaps (days with no posts).
     */
    public function getContentGaps(string $orgId, Carbon $startDate, Carbon $endDate): array
    {
        $posts = ScheduledPost::where('org_id', $orgId)
            ->where('status', 'scheduled')
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->get();

        $postDates = $posts->pluck('scheduled_at')
            ->map(fn($date) => $date->format('Y-m-d'))
            ->unique()
            ->toArray();

        $gaps = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');

            if (!in_array($dateStr, $postDates) && !$currentDate->isWeekend()) {
                $gaps[] = [
                    'date' => $dateStr,
                    'day_of_week' => $currentDate->format('l'),
                ];
            }

            $currentDate->addDay();
        }

        return $gaps;
    }

    /**
     * Get scheduled posts summary.
     */
    public function getSummary(string $orgId): array
    {
        return [
            'total_scheduled' => ScheduledPost::where('org_id', $orgId)
                ->where('status', 'scheduled')
                ->count(),
            'total_published' => ScheduledPost::where('org_id', $orgId)
                ->where('status', 'published')
                ->count(),
            'pending_approval' => ScheduledPost::where('org_id', $orgId)
                ->where('approval_status', 'pending')
                ->count(),
            'failed' => ScheduledPost::where('org_id', $orgId)
                ->where('status', 'failed')
                ->count(),
            'next_scheduled' => ScheduledPost::where('org_id', $orgId)
                ->where('status', 'scheduled')
                ->orderBy('scheduled_at')
                ->first()
                ?->scheduled_at
                ?->toISOString(),
        ];
    }
}
