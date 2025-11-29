<?php

namespace App\Services\Social;

use Illuminate\Support\Facades\DB;
use App\Models\Integration;

/**
 * Service for calculating best posting times.
 */
class BestTimesService
{
    /**
     * Day names for mapping.
     */
    protected array $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    /**
     * Platform-specific best times based on industry research.
     */
    protected array $platformBestTimes = [
        'instagram' => [
            'monday' => ['11:00', '14:00', '19:00'],
            'tuesday' => ['10:00', '14:00', '19:00'],
            'wednesday' => ['11:00', '14:00', '19:00'],
            'thursday' => ['11:00', '14:00', '20:00'],
            'friday' => ['10:00', '14:00', '17:00'],
            'saturday' => ['09:00', '11:00', '19:00'],
            'sunday' => ['10:00', '14:00', '19:00'],
        ],
        'facebook' => [
            'monday' => ['09:00', '13:00', '16:00'],
            'tuesday' => ['09:00', '13:00', '16:00'],
            'wednesday' => ['09:00', '13:00', '15:00'],
            'thursday' => ['09:00', '12:00', '15:00'],
            'friday' => ['09:00', '11:00', '14:00'],
            'saturday' => ['09:00', '12:00', '15:00'],
            'sunday' => ['09:00', '12:00', '15:00'],
        ],
        'twitter' => [
            'monday' => ['08:00', '12:00', '17:00'],
            'tuesday' => ['08:00', '12:00', '17:00'],
            'wednesday' => ['09:00', '12:00', '17:00'],
            'thursday' => ['08:00', '12:00', '17:00'],
            'friday' => ['08:00', '12:00', '15:00'],
            'saturday' => ['09:00', '12:00', '14:00'],
            'sunday' => ['09:00', '12:00', '17:00'],
        ],
        'linkedin' => [
            'monday' => ['07:00', '10:00', '17:00'],
            'tuesday' => ['07:00', '10:00', '12:00'],
            'wednesday' => ['07:00', '10:00', '12:00'],
            'thursday' => ['07:00', '10:00', '14:00'],
            'friday' => ['07:00', '10:00', '12:00'],
            'saturday' => ['10:00', '12:00', '14:00'],
            'sunday' => ['10:00', '12:00', '14:00'],
        ],
        'tiktok' => [
            'monday' => ['12:00', '16:00', '21:00'],
            'tuesday' => ['09:00', '15:00', '21:00'],
            'wednesday' => ['12:00', '15:00', '21:00'],
            'thursday' => ['12:00', '15:00', '21:00'],
            'friday' => ['12:00', '15:00', '21:00'],
            'saturday' => ['11:00', '15:00', '21:00'],
            'sunday' => ['11:00', '15:00', '21:00'],
        ],
    ];

    /**
     * Get best posting times for given profiles.
     *
     * @param string $orgId Organization ID
     * @param array $profileIds Profile IDs to analyze
     * @return array Best times data with timezone and notes
     */
    public function getBestTimes(string $orgId, array $profileIds = []): array
    {
        $engagementData = [];
        $hasData = false;

        if (!empty($profileIds)) {
            $engagementData = $this->getEngagementData($orgId, $profileIds);
            $hasData = $engagementData->count() > 0;
        }

        if ($hasData) {
            $bestTimes = $this->calculateFromEngagement($engagementData);
            $note = 'Times are based on your engagement data from the last 90 days';
        } else {
            $platforms = $this->getPlatformsForProfiles($profileIds);
            $bestTimes = $this->getDefaultBestTimes($platforms);
            $note = 'Times are based on general engagement patterns for your platforms';
        }

        return [
            'best_times' => $bestTimes,
            'timezone' => 'UTC',
            'note' => $note,
            'has_custom_data' => $hasData,
        ];
    }

    /**
     * Get engagement data from social posts.
     */
    protected function getEngagementData(string $orgId, array $profileIds)
    {
        return DB::table('cmis.social_posts')
            ->select(
                DB::raw("EXTRACT(DOW FROM published_at) as day_of_week"),
                DB::raw("EXTRACT(HOUR FROM published_at) as hour"),
                DB::raw("AVG(COALESCE((engagement->>'likes')::numeric, 0) +
                         COALESCE((engagement->>'comments')::numeric, 0) +
                         COALESCE((engagement->>'shares')::numeric, 0)) as avg_engagement")
            )
            ->where('org_id', $orgId)
            ->whereIn('integration_id', $profileIds)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->whereRaw("published_at > NOW() - INTERVAL '90 days'")
            ->groupBy(DB::raw("EXTRACT(DOW FROM published_at)"), DB::raw("EXTRACT(HOUR FROM published_at)"))
            ->orderBy('avg_engagement', 'desc')
            ->limit(30)
            ->get();
    }

    /**
     * Get platforms for given profile IDs.
     */
    protected function getPlatformsForProfiles(array $profileIds): array
    {
        if (empty($profileIds)) {
            return [];
        }

        return Integration::whereIn('integration_id', $profileIds)
            ->pluck('platform')
            ->unique()
            ->toArray();
    }

    /**
     * Calculate best times from engagement data.
     */
    protected function calculateFromEngagement($engagementData): array
    {
        $bestTimes = [];

        foreach ($this->dayNames as $day) {
            $bestTimes[$day] = [];
        }

        $dayGroups = $engagementData->groupBy('day_of_week');

        foreach ($dayGroups as $dayIndex => $hours) {
            $dayName = $this->dayNames[(int)$dayIndex] ?? 'monday';
            $topHours = $hours->sortByDesc('avg_engagement')->take(3);

            foreach ($topHours as $hourData) {
                $hour = (int)$hourData->hour;
                $bestTimes[$dayName][] = sprintf('%02d:00', $hour);
            }

            sort($bestTimes[$dayName]);
        }

        // Fill empty days with defaults
        foreach ($this->dayNames as $day) {
            if (empty($bestTimes[$day])) {
                $bestTimes[$day] = ['09:00', '12:00', '18:00'];
            }
        }

        return $bestTimes;
    }

    /**
     * Get default best times based on platform industry data.
     */
    public function getDefaultBestTimes(array $platforms = []): array
    {
        if (!empty($platforms)) {
            return $this->mergePlatformTimes($platforms);
        }

        return [
            'monday' => ['09:00', '12:00', '18:00'],
            'tuesday' => ['09:00', '12:00', '18:00'],
            'wednesday' => ['09:00', '12:00', '18:00'],
            'thursday' => ['09:00', '12:00', '18:00'],
            'friday' => ['09:00', '12:00', '17:00'],
            'saturday' => ['10:00', '14:00', '20:00'],
            'sunday' => ['10:00', '14:00', '19:00'],
        ];
    }

    /**
     * Merge best times from multiple platforms.
     */
    protected function mergePlatformTimes(array $platforms): array
    {
        $mergedTimes = [];

        foreach ($this->dayNames as $day) {
            $allTimes = [];
            foreach ($platforms as $platform) {
                $platform = strtolower($platform);
                if (isset($this->platformBestTimes[$platform][$day])) {
                    $allTimes = array_merge($allTimes, $this->platformBestTimes[$platform][$day]);
                }
            }
            $mergedTimes[$day] = array_values(array_unique($allTimes));
            sort($mergedTimes[$day]);
            $mergedTimes[$day] = array_slice($mergedTimes[$day], 0, 3);
        }

        return $mergedTimes;
    }

    /**
     * Get character limits for all platforms.
     */
    public function getCharacterLimits(): array
    {
        return [
            'twitter' => [
                'text' => 280,
                'with_media' => 280,
                'with_link' => 257,
            ],
            'instagram' => [
                'caption' => 2200,
                'bio' => 150,
                'hashtags' => 30,
            ],
            'facebook' => [
                'post' => 63206,
                'ad_headline' => 40,
                'ad_description' => 125,
            ],
            'linkedin' => [
                'post' => 3000,
                'article' => 120000,
                'comment' => 1250,
            ],
            'tiktok' => [
                'caption' => 2200,
                'bio' => 80,
            ],
        ];
    }
}
