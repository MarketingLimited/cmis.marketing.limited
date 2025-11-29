<?php

namespace App\Services\Social;

use App\Models\Social\SocialPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Social Queue Service
 *
 * Manages queue settings and scheduling for social media posts.
 * Handles posting times, day scheduling, and next slot calculation.
 */
class SocialQueueService
{
    /**
     * Get queue settings for all connected integrations
     *
     * @param string $orgId Organization UUID
     * @return array Queue settings for all integrations
     */
    public function getQueueSettings(string $orgId): array
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

            // Get all active integrations
            $integrations = DB::table('cmis.integrations')
                ->where('org_id', $orgId)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->get();

            $settings = [];

            foreach ($integrations as $integration) {
                $queueSettings = $this->getIntegrationQueueSettings($orgId, $integration->integration_id);

                if (!$queueSettings) {
                    // Create default settings
                    $queueSettings = $this->getDefaultQueueSettings($integration->platform, $integration->integration_id);
                }

                $settings[] = $this->formatQueueSettings($integration, $queueSettings);
            }

            return $settings;

        } catch (\Exception $e) {
            Log::error('Failed to fetch queue settings', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get queue settings for a specific integration
     *
     * @param string $orgId
     * @param string $integrationId
     * @return object|null
     */
    protected function getIntegrationQueueSettings(string $orgId, string $integrationId): ?object
    {
        return DB::table('cmis.integration_queue_settings')
            ->where('org_id', $orgId)
            ->where('integration_id', $integrationId)
            ->whereNull('deleted_at')
            ->first();
    }

    /**
     * Get default queue settings for a platform
     *
     * @param string $platform
     * @param string $integrationId
     * @return object
     */
    protected function getDefaultQueueSettings(string $platform, string $integrationId): object
    {
        $defaults = \App\Models\Social\IntegrationQueueSettings::getDefaultSettings($platform);

        return (object) [
            'integration_id' => $integrationId,
            'queue_enabled' => false,
            'posting_times' => $defaults['posting_times'],
            'days_enabled' => [1, 2, 3, 4, 5], // Weekdays by default
            'posts_per_day' => $defaults['posts_per_day'],
        ];
    }

    /**
     * Format queue settings for response
     *
     * @param object $integration
     * @param object $queueSettings
     * @return array
     */
    protected function formatQueueSettings(object $integration, object $queueSettings): array
    {
        return [
            'integration_id' => $integration->integration_id,
            'platform' => $integration->platform,
            'account_id' => $integration->account_id,
            'username' => $integration->username,
            'queue_enabled' => $queueSettings->queue_enabled ?? false,
            'posting_times' => json_decode($queueSettings->posting_times ?? '[]', true),
            'days_enabled' => json_decode($queueSettings->days_enabled ?? '[1,2,3,4,5]', true),
            'posts_per_day' => $queueSettings->posts_per_day ?? 3,
        ];
    }

    /**
     * Save queue settings for an integration
     *
     * @param string $orgId
     * @param array $data Queue settings data
     * @return bool Success status
     */
    public function saveQueueSettings(string $orgId, array $data): bool
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

            $exists = DB::table('cmis.integration_queue_settings')
                ->where('org_id', $orgId)
                ->where('integration_id', $data['integration_id'])
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                return $this->updateQueueSettings($orgId, $data);
            }

            return $this->createQueueSettings($orgId, $data);

        } catch (\Exception $e) {
            Log::error('Failed to save queue settings', [
                'org_id' => $orgId,
                'integration_id' => $data['integration_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update existing queue settings
     *
     * @param string $orgId
     * @param array $data
     * @return bool
     */
    protected function updateQueueSettings(string $orgId, array $data): bool
    {
        DB::table('cmis.integration_queue_settings')
            ->where('org_id', $orgId)
            ->where('integration_id', $data['integration_id'])
            ->update([
                'queue_enabled' => $data['queue_enabled'],
                'posting_times' => json_encode($data['posting_times']),
                'days_enabled' => json_encode($data['days_enabled']),
                'posts_per_day' => $data['posts_per_day'],
                'updated_at' => now(),
            ]);

        return true;
    }

    /**
     * Create new queue settings
     *
     * @param string $orgId
     * @param array $data
     * @return bool
     */
    protected function createQueueSettings(string $orgId, array $data): bool
    {
        DB::table('cmis.integration_queue_settings')->insert([
            'id' => Str::uuid()->toString(),
            'org_id' => $orgId,
            'integration_id' => $data['integration_id'],
            'queue_enabled' => $data['queue_enabled'],
            'posting_times' => json_encode($data['posting_times']),
            'days_enabled' => json_encode($data['days_enabled']),
            'posts_per_day' => $data['posts_per_day'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    /**
     * Get the next available queue slot for an integration
     *
     * @param string $orgId
     * @param string $integrationId
     * @return string|null Next available slot datetime
     * @throws \Exception
     */
    public function getNextQueueSlot(string $orgId, string $integrationId): ?string
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

            $queueSettings = DB::table('cmis.integration_queue_settings')
                ->where('org_id', $orgId)
                ->where('integration_id', $integrationId)
                ->whereNull('deleted_at')
                ->first();

            if (!$queueSettings || !$queueSettings->queue_enabled) {
                throw new \Exception('Queue is not enabled for this integration');
            }

            $postingTimes = json_decode($queueSettings->posting_times ?? '[]', true);
            $daysEnabled = json_decode($queueSettings->days_enabled ?? '[1,2,3,4,5]', true);

            if (empty($postingTimes)) {
                throw new \Exception('No posting times configured');
            }

            // Try to find slot today
            $todaySlot = $this->findSlotToday($postingTimes, $daysEnabled);
            if ($todaySlot) {
                return $todaySlot;
            }

            // Find next available day
            return $this->findNextDaySlot($postingTimes, $daysEnabled);

        } catch (\Exception $e) {
            Log::error('Failed to get next queue slot', [
                'org_id' => $orgId,
                'integration_id' => $integrationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Find available slot today
     *
     * @param array $postingTimes
     * @param array $daysEnabled
     * @return string|null
     */
    protected function findSlotToday(array $postingTimes, array $daysEnabled): ?string
    {
        $now = now();
        $currentTime = $now->format('H:i');
        $currentDay = $now->dayOfWeek;

        foreach ($postingTimes as $time) {
            if ($time > $currentTime && in_array($currentDay, $daysEnabled)) {
                return $now->format('Y-m-d') . ' ' . $time;
            }
        }

        return null;
    }

    /**
     * Find next available day slot
     *
     * @param array $postingTimes
     * @param array $daysEnabled
     * @return string|null
     */
    protected function findNextDaySlot(array $postingTimes, array $daysEnabled): ?string
    {
        $now = now();

        for ($i = 1; $i <= 7; $i++) {
            $nextDay = $now->copy()->addDays($i);
            $nextDayOfWeek = $nextDay->dayOfWeek;

            if (in_array($nextDayOfWeek, $daysEnabled)) {
                return $nextDay->format('Y-m-d') . ' ' . $postingTimes[0];
            }
        }

        return null;
    }

    /**
     * Get scheduled posts for the organization
     *
     * @param string $orgId
     * @return array Scheduled posts
     */
    public function getScheduledPosts(string $orgId): array
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

            $posts = DB::table('cmis.social_posts')
                ->where('org_id', $orgId)
                ->where('status', 'scheduled')
                ->whereNotNull('scheduled_at')
                ->whereNull('deleted_at')
                ->orderBy('scheduled_at', 'asc')
                ->get();

            return $posts->map(function ($post) {
                return [
                    'id' => $post->id,
                    'platform' => $post->platform,
                    'account_id' => $post->account_id,
                    'content' => $post->content,
                    'scheduled_at' => $post->scheduled_at,
                    'media' => json_decode($post->media ?? '[]', true),
                    'metadata' => json_decode($post->metadata ?? '{}', true),
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to get scheduled posts', [
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Reschedule a post
     *
     * @param string $orgId
     * @param string $postId
     * @param string $scheduledAt New scheduled datetime
     * @return array Updated post data
     */
    public function reschedulePost(string $orgId, string $postId, string $scheduledAt): array
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

            $socialPost = SocialPost::where('org_id', $orgId)
                ->where('id', $postId)
                ->firstOrFail();

            $socialPost->scheduled_at = $scheduledAt;
            $socialPost->save();

            return [
                'id' => $socialPost->id,
                'scheduled_at' => $socialPost->scheduled_at,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to reschedule post', [
                'org_id' => $orgId,
                'post_id' => $postId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate optimal posting times based on engagement data
     *
     * @param string $orgId
     * @param string $platform
     * @return array Recommended posting times
     */
    public function calculateOptimalTimes(string $orgId, string $platform): array
    {
        // TODO: Implement engagement-based analysis
        // This would analyze historical post performance and suggest best times

        // For now, return platform-specific defaults
        $defaults = [
            'facebook' => ['09:00', '13:00', '18:00'],
            'instagram' => ['09:00', '12:00', '19:00'],
            'twitter' => ['08:00', '12:00', '17:00', '21:00'],
            'linkedin' => ['08:00', '12:00', '17:00'],
            'tiktok' => ['07:00', '12:00', '19:00'],
        ];

        return $defaults[$platform] ?? ['09:00', '12:00', '18:00'];
    }

    /**
     * Get queue statistics for an integration
     *
     * @param string $orgId
     * @param string $integrationId
     * @return array Queue statistics
     */
    public function getQueueStatistics(string $orgId, string $integrationId): array
    {
        DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

        $stats = [
            'total_queued' => DB::table('cmis.social_posts')
                ->where('org_id', $orgId)
                ->where('integration_id', $integrationId)
                ->where('status', 'scheduled')
                ->whereNull('deleted_at')
                ->count(),

            'next_slot' => $this->getNextQueueSlot($orgId, $integrationId),

            'slots_available_today' => $this->getSlotsAvailableToday($orgId, $integrationId),
        ];

        return $stats;
    }

    /**
     * Get available slots remaining today
     *
     * @param string $orgId
     * @param string $integrationId
     * @return int
     */
    protected function getSlotsAvailableToday(string $orgId, string $integrationId): int
    {
        $queueSettings = DB::table('cmis.integration_queue_settings')
            ->where('org_id', $orgId)
            ->where('integration_id', $integrationId)
            ->whereNull('deleted_at')
            ->first();

        if (!$queueSettings || !$queueSettings->queue_enabled) {
            return 0;
        }

        $postingTimes = json_decode($queueSettings->posting_times ?? '[]', true);
        $daysEnabled = json_decode($queueSettings->days_enabled ?? '[1,2,3,4,5]', true);
        $now = now();
        $currentDay = $now->dayOfWeek;

        if (!in_array($currentDay, $daysEnabled)) {
            return 0;
        }

        $currentTime = $now->format('H:i');
        $remainingSlots = 0;

        foreach ($postingTimes as $time) {
            if ($time > $currentTime) {
                $remainingSlots++;
            }
        }

        return $remainingSlots;
    }
}
