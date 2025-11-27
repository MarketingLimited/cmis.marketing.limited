<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Social\ProfileGroup;
use App\Models\Integration;
use App\Models\Creative\BrandVoice;
use App\Models\Compliance\BrandSafetyPolicy;
use App\Models\Social\SocialPost;
use App\Models\Workflow\ApprovalWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PublishingModalController extends Controller
{
    use ApiResponse;

    /**
     * Get profile groups with their social profiles for the publishing modal.
     */
    public function getProfileGroupsWithProfiles(Request $request, string $org)
    {
        $profileGroups = ProfileGroup::where('org_id', $org)
            ->with(['socialProfiles' => function ($query) {
                $query->whereNull('deleted_at')
                    ->select('integration_id', 'platform', 'profile_group_id',
                             'account_name', 'platform_handle', 'avatar_url', 'status',
                             'username', 'account_id'); // Include fallback columns
            }])
            ->select('group_id', 'name', 'description')
            ->get()
            ->map(function ($group) {
                return [
                    'group_id' => $group->group_id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'profiles' => $group->socialProfiles->map(function ($profile) use ($group) {
                        return [
                            'integration_id' => $profile->integration_id,
                            'platform' => $profile->platform,
                            // Use account_name if available, fallback to username
                            'account_name' => $profile->account_name ?? $profile->username ?? $profile->account_id,
                            'platform_handle' => $profile->platform_handle ?? '@' . ($profile->username ?? $profile->account_id),
                            'avatar_url' => $profile->avatar_url,
                            'status' => $profile->status ?? 'active',
                            'group_id' => $group->group_id,
                        ];
                    }),
                ];
            });

        return $this->success($profileGroups, 'Profile groups retrieved successfully');
    }

    /**
     * Get brand voices for the publishing modal AI assistant.
     */
    public function getBrandVoices(Request $request, string $org)
    {
        $brandVoices = BrandVoice::where('org_id', $org)
            ->select('voice_id', 'name', 'tone', 'personality_traits', 'description')
            ->get();

        return $this->success($brandVoices, 'Brand voices retrieved successfully');
    }

    /**
     * Validate content against brand safety policies.
     */
    public function validateBrandSafety(Request $request, string $org)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'profile_group_id' => 'nullable|uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $content = $request->input('content');
        $profileGroupId = $request->input('profile_group_id');

        // Get applicable brand safety policies
        $policiesQuery = BrandSafetyPolicy::where('org_id', $org)
            ->where('is_active', true);

        if ($profileGroupId) {
            $policiesQuery->where(function ($q) use ($profileGroupId) {
                $q->whereNull('profile_group_id')
                  ->orWhere('profile_group_id', $profileGroupId);
            });
        }

        $policies = $policiesQuery->get();

        $issues = [];
        $passed = true;

        foreach ($policies as $policy) {
            // Check blocked words
            if (!empty($policy->blocked_words)) {
                foreach ($policy->blocked_words as $word) {
                    if (stripos($content, $word) !== false) {
                        $issues[] = "Contains blocked word: '{$word}'";
                        $passed = false;
                    }
                }
            }

            // Check required elements
            if (!empty($policy->required_elements)) {
                $hasRequired = false;
                foreach ($policy->required_elements as $element) {
                    if (stripos($content, $element) !== false) {
                        $hasRequired = true;
                        break;
                    }
                }
                if (!$hasRequired && count($policy->required_elements) > 0) {
                    $issues[] = "Missing required element from: " . implode(', ', $policy->required_elements);
                    if ($policy->severity_level === 'block') {
                        $passed = false;
                    }
                }
            }

            // Check character limits
            if (!empty($policy->rules['min_characters']) && strlen($content) < $policy->rules['min_characters']) {
                $issues[] = "Content is shorter than minimum {$policy->rules['min_characters']} characters";
                $passed = false;
            }

            if (!empty($policy->rules['max_characters']) && strlen($content) > $policy->rules['max_characters']) {
                $issues[] = "Content exceeds maximum {$policy->rules['max_characters']} characters";
                $passed = false;
            }

            // Check for URLs if blocked
            if (!empty($policy->rules['block_urls'])) {
                if (preg_match('/https?:\/\/[^\s]+/', $content)) {
                    $issues[] = "External URLs are not allowed";
                    $passed = false;
                }
            }

            // Check disclosure requirement
            if (!empty($policy->rules['require_disclosure'])) {
                if (!preg_match('/#(ad|sponsored|partnership)/i', $content)) {
                    $issues[] = "Sponsored content must include disclosure (#ad, #sponsored)";
                    if ($policy->severity_level === 'block') {
                        $passed = false;
                    }
                }
            }
        }

        return $this->success([
            'passed' => $passed,
            'issues' => array_unique($issues),
        ], 'Brand safety validation completed');
    }

    /**
     * Create or schedule a social post.
     */
    public function createPost(Request $request, string $org)
    {
        $validator = Validator::make($request->all(), [
            'profile_ids' => 'required|array|min:1',
            'profile_ids.*' => 'uuid',
            'content' => 'required|array',
            'content.global.text' => 'nullable|string|max:10000',
            'content.global.media' => 'nullable|array',
            'content.global.link' => 'nullable|url',
            'content.platforms' => 'nullable|array',
            'schedule' => 'nullable|array',
            'schedule.date' => 'nullable|date',
            'schedule.time' => 'nullable|date_format:H:i',
            'schedule.timezone' => 'nullable|string',
            'is_draft' => 'boolean',
            'requires_approval' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $profileIds = $request->input('profile_ids');
            $content = $request->input('content');
            $schedule = $request->input('schedule');
            $isDraft = $request->input('is_draft', false);
            $requiresApproval = $request->input('requires_approval', false);

            // Get global content for brand safety validation
            $globalText = $content['global']['text'] ?? '';

            // Pre-validate brand safety for all content
            if (!$isDraft && $globalText) {
                $safetyResult = $this->performBrandSafetyCheck($org, $globalText, null);
                if (!$safetyResult['passed']) {
                    return $this->error('Brand safety check failed: ' . implode(', ', $safetyResult['issues']), 422);
                }
            }

            $posts = [];
            $scheduledAt = null;
            $needsApproval = false;

            if ($schedule && !empty($schedule['date']) && !empty($schedule['time'])) {
                $timezone = $schedule['timezone'] ?? 'UTC';
                $scheduledAt = \Carbon\Carbon::parse("{$schedule['date']} {$schedule['time']}", $timezone)->utc();
            }

            foreach ($profileIds as $profileId) {
                $profile = Integration::where('org_id', $org)
                    ->where('integration_id', $profileId)
                    ->first();

                if (!$profile) {
                    continue;
                }

                // Check if approval workflow applies to this profile's group
                if (!$isDraft && $profile->profile_group_id) {
                    $workflow = ApprovalWorkflow::where('org_id', $org)
                        ->where('profile_group_id', $profile->profile_group_id)
                        ->where('is_active', true)
                        ->first();

                    if ($workflow) {
                        $needsApproval = true;
                    }
                }

                // Get platform-specific content or fall back to global
                $platform = $profile->platform;
                $postContent = $content['platforms'][$platform]['text'] ?? $content['global']['text'] ?? '';

                // Determine post status
                $status = 'pending';
                if ($isDraft) {
                    $status = 'draft';
                } elseif ($requiresApproval || $needsApproval) {
                    $status = 'pending_approval';
                } elseif ($scheduledAt) {
                    $status = 'scheduled';
                }

                $post = SocialPost::create([
                    'org_id' => $org,
                    'integration_id' => $profileId,
                    'profile_group_id' => $profile->profile_group_id,
                    'platform' => $platform,
                    'content' => $postContent,
                    'media' => $content['global']['media'] ?? [],
                    'link' => $content['global']['link'] ?? null,
                    'labels' => $content['global']['labels'] ?? [],
                    'platform_specific_data' => $content['platforms'][$platform] ?? [],
                    'status' => $status,
                    'scheduled_at' => $scheduledAt,
                    'created_by' => auth()->id(),
                ]);

                $posts[] = $post;
            }

            $statusMessage = match (true) {
                $isDraft => 'saved as draft',
                $needsApproval || $requiresApproval => 'submitted for approval',
                $scheduledAt !== null => 'scheduled',
                default => 'created'
            };

            return $this->created([
                'posts' => $posts,
                'count' => count($posts),
                'requires_approval' => $needsApproval || $requiresApproval,
            ], count($posts) . ' post(s) ' . $statusMessage . ' successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to create posts: ' . $e->getMessage());
        }
    }

    /**
     * Perform brand safety check on content.
     */
    private function performBrandSafetyCheck(string $org, string $content, ?string $profileGroupId): array
    {
        $policiesQuery = BrandSafetyPolicy::where('org_id', $org)
            ->where('is_active', true);

        if ($profileGroupId) {
            $policiesQuery->where(function ($q) use ($profileGroupId) {
                $q->whereNull('profile_group_id')
                  ->orWhere('profile_group_id', $profileGroupId);
            });
        }

        $policies = $policiesQuery->get();

        $issues = [];
        $passed = true;

        foreach ($policies as $policy) {
            // Check blocked words
            if (!empty($policy->blocked_words)) {
                foreach ($policy->blocked_words as $word) {
                    if (stripos($content, $word) !== false) {
                        $issues[] = "Contains blocked word: '{$word}'";
                        $passed = false;
                    }
                }
            }

            // Check character limits
            if (!empty($policy->rules['min_characters']) && strlen($content) < $policy->rules['min_characters']) {
                $issues[] = "Content is shorter than minimum {$policy->rules['min_characters']} characters";
                $passed = false;
            }

            if (!empty($policy->rules['max_characters']) && strlen($content) > $policy->rules['max_characters']) {
                $issues[] = "Content exceeds maximum {$policy->rules['max_characters']} characters";
                $passed = false;
            }
        }

        return [
            'passed' => $passed,
            'issues' => array_unique($issues),
        ];
    }

    /**
     * Save a post as draft.
     */
    public function saveDraft(Request $request, string $org)
    {
        $request->merge(['is_draft' => true]);
        return $this->createPost($request, $org);
    }

    /**
     * Get best posting times for selected profiles.
     */
    public function getBestTimes(Request $request, string $org)
    {
        $profileIds = $request->input('profile_ids', []);

        // Try to get actual engagement data from social posts
        $engagementData = [];
        $hasData = false;

        if (!empty($profileIds)) {
            // Query published posts with engagement data for these profiles
            $engagementData = DB::table('cmis.social_posts')
                ->select(
                    DB::raw("EXTRACT(DOW FROM published_at) as day_of_week"),
                    DB::raw("EXTRACT(HOUR FROM published_at) as hour"),
                    DB::raw("AVG(COALESCE((engagement->>'likes')::numeric, 0) +
                             COALESCE((engagement->>'comments')::numeric, 0) +
                             COALESCE((engagement->>'shares')::numeric, 0)) as avg_engagement")
                )
                ->where('org_id', $org)
                ->whereIn('integration_id', $profileIds)
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->whereRaw("published_at > NOW() - INTERVAL '90 days'")
                ->groupBy(DB::raw("EXTRACT(DOW FROM published_at)"), DB::raw("EXTRACT(HOUR FROM published_at)"))
                ->orderBy('avg_engagement', 'desc')
                ->limit(30)
                ->get();

            $hasData = $engagementData->count() > 0;
        }

        // If we have engagement data, calculate best times
        if ($hasData) {
            $bestTimes = $this->calculateBestTimesFromEngagement($engagementData);
            $note = 'Times are based on your engagement data from the last 90 days';
        } else {
            // Fallback to industry-standard best times by platform
            $platforms = [];
            if (!empty($profileIds)) {
                $platforms = Integration::whereIn('integration_id', $profileIds)
                    ->pluck('platform')
                    ->unique()
                    ->toArray();
            }

            $bestTimes = $this->getDefaultBestTimes($platforms);
            $note = 'Times are based on general engagement patterns for your platforms';
        }

        return $this->success([
            'best_times' => $bestTimes,
            'timezone' => 'UTC',
            'note' => $note,
            'has_custom_data' => $hasData,
        ], 'Best posting times retrieved');
    }

    /**
     * Calculate best times from engagement data.
     */
    private function calculateBestTimesFromEngagement($engagementData): array
    {
        $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $bestTimes = [];

        foreach ($dayNames as $day) {
            $bestTimes[$day] = [];
        }

        // Group by day and get top 3 hours for each day
        $dayGroups = $engagementData->groupBy('day_of_week');

        foreach ($dayGroups as $dayIndex => $hours) {
            $dayName = $dayNames[(int)$dayIndex] ?? 'monday';
            $topHours = $hours->sortByDesc('avg_engagement')->take(3);

            foreach ($topHours as $hourData) {
                $hour = (int)$hourData->hour;
                $bestTimes[$dayName][] = sprintf('%02d:00', $hour);
            }

            // Sort times chronologically
            sort($bestTimes[$dayName]);
        }

        // Fill in empty days with defaults
        foreach ($dayNames as $day) {
            if (empty($bestTimes[$day])) {
                $bestTimes[$day] = ['09:00', '12:00', '18:00'];
            }
        }

        return $bestTimes;
    }

    /**
     * Get default best times based on platform industry data.
     */
    private function getDefaultBestTimes(array $platforms = []): array
    {
        // Platform-specific best times based on industry research
        $platformBestTimes = [
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

        // If we have specific platforms, merge their best times
        if (!empty($platforms)) {
            $mergedTimes = [];
            $dayNames = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

            foreach ($dayNames as $day) {
                $allTimes = [];
                foreach ($platforms as $platform) {
                    $platform = strtolower($platform);
                    if (isset($platformBestTimes[$platform][$day])) {
                        $allTimes = array_merge($allTimes, $platformBestTimes[$platform][$day]);
                    }
                }
                $mergedTimes[$day] = array_values(array_unique($allTimes));
                sort($mergedTimes[$day]);
                $mergedTimes[$day] = array_slice($mergedTimes[$day], 0, 3);
            }

            return $mergedTimes;
        }

        // General fallback
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
     * Get character limits for different platforms.
     */
    public function getCharacterLimits(Request $request)
    {
        $limits = [
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

        return $this->success($limits, 'Character limits retrieved');
    }
}
