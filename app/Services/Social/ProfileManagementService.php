<?php

namespace App\Services\Social;

use App\Models\Core\Integration;
use App\Models\Social\ProfileGroup;
use App\Models\Social\IntegrationQueueSettings;
use App\Models\Platform\BoostRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Service for managing social profiles (integrations) in a VistaSocial-like manner.
 * Provides business logic for profile listing, filtering, updating, and configuration.
 */
class ProfileManagementService
{
    /**
     * Get paginated list of profiles with filters.
     *
     * @param string $orgId Organization ID
     * @param array $filters Search and filter parameters
     * @param int $perPage Items per page
     * @return LengthAwarePaginator
     */
    public function getProfiles(string $orgId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Integration::where('org_id', $orgId)
            ->whereIn('platform', ['instagram', 'facebook', 'twitter', 'linkedin', 'tiktok', 'youtube', 'threads', 'google_business'])
            ->with(['profileGroup', 'connectedByUser', 'queueSettings']);

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Apply platform filter
        if (!empty($filters['platform'])) {
            $query->forPlatform($filters['platform']);
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->whereIn('status', ['active', 'connected']);
            } elseif ($filters['status'] === 'inactive') {
                $query->whereIn('status', ['inactive', 'disconnected']);
            } elseif ($filters['status'] === 'error') {
                $query->whereIn('status', ['error', 'failed']);
            }
        }

        // Apply profile group filter
        if (!empty($filters['group_id'])) {
            $query->inGroup($filters['group_id']);
        }

        // Default sort by created_at desc
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Get a single profile with all related data.
     *
     * @param string $orgId Organization ID
     * @param string $integrationId Integration ID
     * @return Integration|null
     */
    public function getProfile(string $orgId, string $integrationId): ?Integration
    {
        return Integration::where('org_id', $orgId)
            ->where('integration_id', $integrationId)
            ->with([
                'profileGroup',
                'connectedByUser',
                'queueSettings',
                'boostRules' => function ($query) {
                    $query->where('is_active', true);
                },
                'creator',
            ])
            ->first();
    }

    /**
     * Update profile settings.
     *
     * @param string $orgId Organization ID
     * @param string $integrationId Integration ID
     * @param array $data Update data
     * @return Integration|null
     */
    public function updateProfile(string $orgId, string $integrationId, array $data): ?Integration
    {
        $profile = Integration::where('org_id', $orgId)
            ->where('integration_id', $integrationId)
            ->first();

        if (!$profile) {
            return null;
        }

        // Allowed fields for update
        $allowedFields = [
            'display_name',
            'industry',
            'bio',
            'website_url',
            'profile_type',
            'is_enabled',
            'auto_boost_enabled',
            'custom_fields',
            'profile_group_id',
            'timezone',
        ];

        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (!empty($updateData)) {
            $profile->update($updateData);
            Log::info('Profile updated', [
                'integration_id' => $integrationId,
                'updated_fields' => array_keys($updateData),
            ]);
        }

        return $profile->fresh(['profileGroup', 'queueSettings']);
    }

    /**
     * Update profile avatar.
     *
     * @param string $orgId Organization ID
     * @param string $integrationId Integration ID
     * @param string $avatarUrl URL of the new avatar
     * @return Integration|null
     */
    public function updateAvatar(string $orgId, string $integrationId, string $avatarUrl): ?Integration
    {
        $profile = Integration::where('org_id', $orgId)
            ->where('integration_id', $integrationId)
            ->first();

        if (!$profile) {
            return null;
        }

        $profile->update(['avatar_url' => $avatarUrl]);

        return $profile;
    }

    /**
     * Assign profile to a profile group.
     *
     * @param string $orgId Organization ID
     * @param string $integrationId Integration ID
     * @param string $groupId Profile group ID
     * @return Integration|null
     */
    public function assignToGroup(string $orgId, string $integrationId, string $groupId): ?Integration
    {
        $profile = Integration::where('org_id', $orgId)
            ->where('integration_id', $integrationId)
            ->first();

        if (!$profile) {
            return null;
        }

        // Verify group exists and belongs to same org
        $group = ProfileGroup::where('org_id', $orgId)
            ->where('group_id', $groupId)
            ->first();

        if (!$group) {
            return null;
        }

        $profile->update(['profile_group_id' => $groupId]);

        Log::info('Profile assigned to group', [
            'integration_id' => $integrationId,
            'group_id' => $groupId,
        ]);

        return $profile->fresh(['profileGroup']);
    }

    /**
     * Remove profile from its profile group.
     *
     * @param string $orgId Organization ID
     * @param string $integrationId Integration ID
     * @return Integration|null
     */
    public function removeFromGroup(string $orgId, string $integrationId): ?Integration
    {
        $profile = Integration::where('org_id', $orgId)
            ->where('integration_id', $integrationId)
            ->first();

        if (!$profile) {
            return null;
        }

        $profile->update(['profile_group_id' => null]);

        return $profile;
    }

    /**
     * Toggle profile enabled status.
     *
     * @param string $orgId Organization ID
     * @param string $integrationId Integration ID
     * @return Integration|null
     */
    public function toggleEnabled(string $orgId, string $integrationId): ?Integration
    {
        $profile = Integration::where('org_id', $orgId)
            ->where('integration_id', $integrationId)
            ->first();

        if (!$profile) {
            return null;
        }

        $profile->update(['is_enabled' => !$profile->is_enabled]);

        return $profile;
    }

    /**
     * Get profiles by profile group.
     *
     * @param string $orgId Organization ID
     * @param string $groupId Profile group ID
     * @return Collection
     */
    public function getProfilesByGroup(string $orgId, string $groupId): Collection
    {
        return Integration::where('org_id', $orgId)
            ->where('profile_group_id', $groupId)
            ->orderBy('account_name')
            ->get();
    }

    /**
     * Get available profile groups for assignment.
     *
     * @param string $orgId Organization ID
     * @return Collection
     */
    public function getAvailableGroups(string $orgId): Collection
    {
        return ProfileGroup::where('org_id', $orgId)
            ->orderBy('name')
            ->get(['group_id', 'name', 'color', 'timezone']);
    }

    /**
     * Get profile statistics.
     *
     * @param string $orgId Organization ID
     * @return array
     */
    public function getProfileStats(string $orgId): array
    {
        $total = Integration::where('org_id', $orgId)
            ->whereIn('platform', ['instagram', 'facebook', 'twitter', 'linkedin', 'tiktok', 'youtube', 'threads', 'google_business'])
            ->count();

        $active = Integration::where('org_id', $orgId)
            ->whereIn('platform', ['instagram', 'facebook', 'twitter', 'linkedin', 'tiktok', 'youtube', 'threads', 'google_business'])
            ->whereIn('status', ['active', 'connected'])
            ->count();

        $byPlatform = Integration::where('org_id', $orgId)
            ->whereIn('platform', ['instagram', 'facebook', 'twitter', 'linkedin', 'tiktok', 'youtube', 'threads', 'google_business'])
            ->select('platform', DB::raw('count(*) as count'))
            ->groupBy('platform')
            ->pluck('count', 'platform')
            ->toArray();

        $withGroups = Integration::where('org_id', $orgId)
            ->whereIn('platform', ['instagram', 'facebook', 'twitter', 'linkedin', 'tiktok', 'youtube', 'threads', 'google_business'])
            ->whereNotNull('profile_group_id')
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'by_platform' => $byPlatform,
            'with_groups' => $withGroups,
            'without_groups' => $total - $withGroups,
        ];
    }

    /**
     * Get queue settings for a profile.
     *
     * @param string $orgId Organization ID
     * @param string $integrationId Integration ID
     * @return IntegrationQueueSettings|null
     */
    public function getQueueSettings(string $orgId, string $integrationId): ?IntegrationQueueSettings
    {
        return IntegrationQueueSettings::where('org_id', $orgId)
            ->where('integration_id', $integrationId)
            ->first();
    }

    /**
     * Update or create queue settings for a profile.
     *
     * @param string $orgId Organization ID
     * @param string $integrationId Integration ID
     * @param array $data Queue settings data
     * @return IntegrationQueueSettings
     */
    public function updateQueueSettings(string $orgId, string $integrationId, array $data): IntegrationQueueSettings
    {
        // Build flat posting_times array from schedule for backwards compatibility
        $postingTimes = [];
        $schedule = $data['schedule'] ?? [];
        foreach ($schedule as $dayTimes) {
            foreach ($dayTimes as $time) {
                if (!in_array($time, $postingTimes)) {
                    $postingTimes[] = $time;
                }
            }
        }
        sort($postingTimes);

        return IntegrationQueueSettings::updateOrCreate(
            [
                'org_id' => $orgId,
                'integration_id' => $integrationId,
            ],
            [
                'queue_enabled' => $data['queue_enabled'] ?? false,
                'posting_times' => $postingTimes,
                'days_enabled' => $data['days_enabled'] ?? [],
                'schedule' => $schedule,
                'posts_per_day' => $data['posts_per_day'] ?? 3,
            ]
        );
    }

    /**
     * Get boost rules for a profile.
     *
     * @param string $orgId Organization ID
     * @param string $integrationId Integration ID
     * @return Collection
     */
    public function getBoostRules(string $orgId, string $integrationId): Collection
    {
        $profile = $this->getProfile($orgId, $integrationId);

        if (!$profile || !$profile->profile_group_id) {
            return collect();
        }

        return BoostRule::where('org_id', $orgId)
            ->where('profile_group_id', $profile->profile_group_id)
            ->where(function ($query) use ($integrationId) {
                // Rules that apply to all profiles or specifically to this profile
                $query->whereJsonLength('apply_to_social_profiles', 0)
                      ->orWhereJsonContains('apply_to_social_profiles', $integrationId);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Refresh profile connection (re-sync from platform).
     *
     * @param string $orgId Organization ID
     * @param string $integrationId Integration ID
     * @return array Result with success status and message
     */
    public function refreshConnection(string $orgId, string $integrationId): array
    {
        $profile = Integration::where('org_id', $orgId)
            ->where('integration_id', $integrationId)
            ->first();

        if (!$profile) {
            return [
                'success' => false,
                'message' => 'Profile not found',
            ];
        }

        try {
            // Check if token needs refresh
            if ($profile->needsTokenRefresh()) {
                $refreshed = $profile->refreshAccessToken();
                if (!$refreshed) {
                    return [
                        'success' => false,
                        'message' => 'Failed to refresh access token',
                    ];
                }
            }

            // Update sync status
            $profile->updateSyncStatus('success');

            return [
                'success' => true,
                'message' => 'Connection refreshed successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to refresh profile connection', [
                'integration_id' => $integrationId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to refresh connection: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Disconnect (soft delete) a profile.
     *
     * @param string $orgId Organization ID
     * @param string $integrationId Integration ID
     * @return bool
     */
    public function disconnectProfile(string $orgId, string $integrationId): bool
    {
        $profile = Integration::where('org_id', $orgId)
            ->where('integration_id', $integrationId)
            ->first();

        if (!$profile) {
            return false;
        }

        // Soft delete the integration
        $profile->delete();

        Log::info('Profile disconnected', [
            'integration_id' => $integrationId,
            'org_id' => $orgId,
        ]);

        return true;
    }

    /**
     * Get available platforms for filter dropdown.
     *
     * @return array
     */
    public function getAvailablePlatforms(): array
    {
        return [
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'twitter' => 'Twitter',
            'linkedin' => 'LinkedIn',
            'tiktok' => 'TikTok',
            'youtube' => 'YouTube',
            'threads' => 'Threads',
            'google_business' => 'Google Business',
        ];
    }

    /**
     * Get available industries for profile settings.
     *
     * @return array
     */
    public function getAvailableIndustries(): array
    {
        return [
            'automotive' => 'Automotive',
            'beauty' => 'Beauty & Cosmetics',
            'education' => 'Education',
            'entertainment' => 'Entertainment',
            'fashion' => 'Fashion & Apparel',
            'finance' => 'Finance & Banking',
            'food_beverage' => 'Food & Beverage',
            'healthcare' => 'Healthcare',
            'hospitality' => 'Hospitality & Travel',
            'real_estate' => 'Real Estate',
            'retail' => 'Retail',
            'sports' => 'Sports & Fitness',
            'technology' => 'Technology',
            'other' => 'Other',
        ];
    }
}
