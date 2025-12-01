<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Jobs\Social\PublishSocialPostJob;
use App\Models\Social\ProfileGroup;
use App\Models\Integration;
use App\Models\Creative\BrandVoice;
use App\Models\Social\SocialPost;
use App\Models\Workflow\ApprovalWorkflow;
use App\Services\Social\BrandSafetyService;
use App\Services\Social\BestTimesService;
use App\Services\Social\Publishers\PublisherFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Controller for the publishing modal functionality.
 *
 * Handles profile groups, brand safety validation, post creation,
 * and best posting times for social media publishing.
 */
class PublishingModalController extends Controller
{
    use ApiResponse;

    protected BrandSafetyService $brandSafetyService;
    protected BestTimesService $bestTimesService;
    protected PublisherFactory $publisherFactory;

    public function __construct(
        BrandSafetyService $brandSafetyService,
        BestTimesService $bestTimesService,
        PublisherFactory $publisherFactory
    ) {
        $this->brandSafetyService = $brandSafetyService;
        $this->bestTimesService = $bestTimesService;
        $this->publisherFactory = $publisherFactory;
    }

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
                             'username', 'account_id');
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

        $result = $this->brandSafetyService->validate(
            $org,
            $request->input('content'),
            $request->input('profile_group_id')
        );

        return $this->success($result, 'Brand safety validation completed');
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
            'queue_position' => 'nullable|string|in:next,available,last', // NEW: Queue support
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $result = $this->processPostCreation($request, $org);
            return $this->created($result['data'], $result['message']);
        } catch (\Exception $e) {
            return $this->serverError('Failed to create posts: ' . $e->getMessage());
        }
    }

    /**
     * Process post creation logic.
     *
     * Posts are created and then dispatched to a queue for async publishing,
     * providing immediate response to the user while publishing happens in background.
     */
    protected function processPostCreation(Request $request, string $org): array
    {
        // [PERF] Start timing the entire post creation process
        $perfStart = microtime(true);
        Log::debug('[PERF] Post creation started');

        $profileIds = $request->input('profile_ids');
        $content = $request->input('content');
        $schedule = $request->input('schedule');
        $isDraft = $request->input('is_draft', false);
        $requiresApproval = $request->input('requires_approval', false);
        $queuePosition = $request->input('queue_position'); // NEW: Queue support
        $isQueueRequest = !empty($queuePosition); // NEW: Detect queue requests

        Log::debug('[QUEUE] Post creation request', [
            'is_queue_request' => $isQueueRequest,
            'queue_position' => $queuePosition,
            'has_schedule' => !empty($schedule),
        ]);

        $globalText = $content['global']['text'] ?? '';

        // [PERF] Measure brand safety validation
        $safetyStart = microtime(true);
        Log::debug('[PERF] Starting brand safety validation');
        // Pre-validate brand safety
        if (!$isDraft && $globalText) {
            $safetyResult = $this->brandSafetyService->validate($org, $globalText, null);
            if (!$safetyResult['passed']) {
                throw new \Exception('Brand safety check failed: ' . implode(', ', $safetyResult['issues']));
            }
        }
        $safetyDuration = (microtime(true) - $safetyStart) * 1000;
        Log::debug('[PERF] Brand safety validation completed', ['duration_ms' => round($safetyDuration, 2)]);

        $scheduledAt = $this->parseScheduleTime($schedule);
        $posts = [];
        $needsApproval = false;
        $jobsDispatched = 0;

        // [PERF] Measure profile processing loop
        $loopStart = microtime(true);
        Log::debug('[PERF] Starting profile processing loop', ['profile_count' => count($profileIds)]);

        foreach ($profileIds as $index => $profileId) {
            $iterStart = microtime(true);

            // [PERF] Measure profile query
            $queryStart = microtime(true);
            $profile = Integration::where('org_id', $org)
                ->where('integration_id', $profileId)
                ->first();
            $queryDuration = (microtime(true) - $queryStart) * 1000;

            if (!$profile) {
                Log::debug('[PERF] Profile not found, skipping', [
                    'profile_id' => $profileId,
                    'query_duration_ms' => round($queryDuration, 2)
                ]);
                continue;
            }

            // [PERF] Measure approval check
            $approvalStart = microtime(true);
            $needsApproval = $needsApproval || $this->checkApprovalRequired($org, $profile);
            $approvalDuration = (microtime(true) - $approvalStart) * 1000;

            // NEW: For queue requests, get the next available queue slot for this profile
            $profileScheduledAt = $scheduledAt;
            if ($isQueueRequest && !$scheduledAt) {
                $profileScheduledAt = $this->getNextQueueSlotForProfile($org, $profileId);
                Log::debug('[QUEUE] Queue slot for profile', [
                    'profile_id' => $profileId,
                    'platform' => $profile->platform,
                    'queue_slot' => $profileScheduledAt?->toIso8601String(),
                ]);
            }

            $postData = $this->buildPostData($org, $profile, $content, $isDraft, $requiresApproval, $needsApproval, $profileScheduledAt, $isQueueRequest);

            // [PERF] Measure post creation
            $createStart = microtime(true);
            $post = SocialPost::create($postData);
            $createDuration = (microtime(true) - $createStart) * 1000;

            // Dispatch async publishing job if post should be published immediately
            if ($postData['status'] === 'pending') {
                // [PERF] Measure job dispatch
                $dispatchStart = microtime(true);
                $post->update(['status' => 'queued']);

                // [DEBUG] Check queue before dispatch
                $queueCountBefore = DB::table('cmis.jobs')->count();
                Log::debug('[DEBUG] Before job dispatch', [
                    'queue_count' => $queueCountBefore,
                    'post_id' => $post->id,
                    'platform' => $profile->platform,
                ]);

                PublishSocialPostJob::dispatch(
                    $post->id,
                    $org,
                    $profile->platform,
                    $postData['content'],
                    $content['global']['media'] ?? [],
                    $content['platforms'][$profile->platform] ?? []
                )->onConnection('database')->onQueue('social-publishing');

                // [DEBUG] Check queue after dispatch
                $queueCountAfter = DB::table('cmis.jobs')->count();
                $jobsDispatched++;
                $dispatchDuration = (microtime(true) - $dispatchStart) * 1000;

                Log::debug('[DEBUG] After job dispatch', [
                    'queue_count_before' => $queueCountBefore,
                    'queue_count_after' => $queueCountAfter,
                    'jobs_added' => $queueCountAfter - $queueCountBefore,
                    'dispatch_duration_ms' => round($dispatchDuration, 2),
                    'post_id' => $post->id,
                    'platform' => $profile->platform,
                ]);

                Log::info('Post queued for publishing', [
                    'post_id' => $post->id,
                    'platform' => $profile->platform,
                    'org_id' => $org,
                ]);
            } else {
                $dispatchDuration = 0;
            }

            $iterDuration = (microtime(true) - $iterStart) * 1000;
            Log::debug('[PERF] Profile iteration completed', [
                'iteration' => $index + 1,
                'profile_id' => $profileId,
                'platform' => $profile->platform,
                'total_ms' => round($iterDuration, 2),
                'breakdown' => [
                    'query_ms' => round($queryDuration, 2),
                    'approval_check_ms' => round($approvalDuration, 2),
                    'post_create_ms' => round($createDuration, 2),
                    'job_dispatch_ms' => round($dispatchDuration, 2),
                ]
            ]);

            $posts[] = $post;
        }

        $loopDuration = (microtime(true) - $loopStart) * 1000;
        Log::debug('[PERF] Profile processing loop completed', [
            'profile_count' => count($profileIds),
            'duration_ms' => round($loopDuration, 2)
        ]);

        // [PERF] Total time
        $totalDuration = (microtime(true) - $perfStart) * 1000;
        Log::info('[PERF] TOTAL post creation completed', [
            'duration_ms' => round($totalDuration, 2),
            'breakdown' => [
                'brand_safety_ms' => round($safetyDuration, 2),
                'profile_loop_ms' => round($loopDuration, 2),
                'other_ms' => round($totalDuration - $safetyDuration - $loopDuration, 2),
            ],
            'posts_created' => count($posts),
            'jobs_dispatched' => $jobsDispatched,
        ]);

        return $this->buildCreationResultAsync($posts, $isDraft, $needsApproval, $requiresApproval, $scheduledAt, $jobsDispatched, $isQueueRequest);
    }

    /**
     * Parse schedule time from request.
     */
    protected function parseScheduleTime(?array $schedule): ?\Carbon\Carbon
    {
        Log::debug('[TIMEZONE] parseScheduleTime called', [
            'schedule' => $schedule,
        ]);

        if (!$schedule || empty($schedule['date']) || empty($schedule['time'])) {
            Log::debug('[TIMEZONE] No schedule data, returning null');
            return null;
        }

        $timezone = $schedule['timezone'] ?? 'UTC';
        $dateTimeStr = "{$schedule['date']} {$schedule['time']}";
        $parsed = \Carbon\Carbon::parse($dateTimeStr, $timezone);
        $utc = $parsed->utc();

        Log::debug('[TIMEZONE] Schedule time conversion', [
            'input_datetime' => $dateTimeStr,
            'input_timezone' => $timezone,
            'parsed' => $parsed->toIso8601String(),
            'utc' => $utc->toIso8601String(),
        ]);

        return $utc;
    }

    /**
     * Check if approval is required for a profile.
     */
    protected function checkApprovalRequired(string $org, Integration $profile): bool
    {
        if (!$profile->profile_group_id) {
            return false;
        }

        return ApprovalWorkflow::where('org_id', $org)
            ->where('profile_group_id', $profile->profile_group_id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get the next available queue slot for a profile.
     *
     * Uses the schedule column which has per-day time slots like:
     * {"monday": [{time: "09:00", label_id: null, is_evergreen: false}, ...], ...}
     *
     * @param string $org Organization ID
     * @param string $profileId Integration/Profile ID
     * @return \Carbon\Carbon|null Next available slot time, or null if queue not enabled/configured
     */
    protected function getNextQueueSlotForProfile(string $org, string $profileId): ?\Carbon\Carbon
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$org]);

            // Get queue settings for this profile
            $queueSettings = DB::table('cmis.integration_queue_settings')
                ->where('org_id', $org)
                ->where('integration_id', $profileId)
                ->whereNull('deleted_at')
                ->first();

            if (!$queueSettings || !$queueSettings->queue_enabled) {
                Log::warning('[QUEUE] Queue not enabled for profile', [
                    'profile_id' => $profileId,
                    'has_settings' => (bool) $queueSettings,
                    'queue_enabled' => $queueSettings->queue_enabled ?? false,
                ]);
                return null;
            }

            // Use the schedule column which has per-day slots
            $schedule = json_decode($queueSettings->schedule ?? '{}', true);
            $daysEnabledRaw = json_decode($queueSettings->days_enabled ?? '[]', true);

            if (empty($schedule) || empty($daysEnabledRaw)) {
                Log::warning('[QUEUE] No schedule or days configured', [
                    'profile_id' => $profileId,
                    'has_schedule' => !empty($schedule),
                    'has_days' => !empty($daysEnabledRaw),
                ]);
                return null;
            }

            // Map day names to numbers (Carbon uses 0=Sunday, 6=Saturday)
            $dayNameToNumber = [
                'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
                'thursday' => 4, 'friday' => 5, 'saturday' => 6
            ];
            $numberToDayName = array_flip($dayNameToNumber);

            // Convert days_enabled to day numbers
            $daysEnabled = array_map(function ($day) use ($dayNameToNumber) {
                if (is_string($day)) {
                    return $dayNameToNumber[strtolower($day)] ?? null;
                }
                return (int) $day;
            }, $daysEnabledRaw);
            $daysEnabled = array_filter($daysEnabled, fn($d) => $d !== null);

            // Get timezone for this profile (inheritance: profile -> group -> org -> UTC)
            $timezoneData = DB::table('cmis.integrations as i')
                ->leftJoin('cmis.social_accounts as sa', 'i.integration_id', '=', 'sa.integration_id')
                ->leftJoin('cmis.profile_groups as pg', 'i.profile_group_id', '=', 'pg.group_id')
                ->join('cmis.orgs as o', 'i.org_id', '=', 'o.org_id')
                ->where('i.integration_id', $profileId)
                ->select(DB::raw('COALESCE(sa.timezone, pg.timezone, o.timezone, \'UTC\') as timezone'))
                ->first();

            $timezone = $timezoneData?->timezone ?? 'UTC';

            // Work in the profile's timezone
            $now = \Carbon\Carbon::now($timezone);
            $currentTime = $now->format('H:i');
            $currentDay = $now->dayOfWeek; // 0=Sunday, 6=Saturday
            $currentDayName = strtolower($now->format('l')); // monday, tuesday, etc.

            Log::debug('[QUEUE] Finding next slot', [
                'profile_id' => $profileId,
                'timezone' => $timezone,
                'now' => $now->toDateTimeString(),
                'current_time' => $currentTime,
                'current_day' => $currentDay,
                'current_day_name' => $currentDayName,
                'days_enabled_raw' => $daysEnabledRaw,
                'days_enabled_numeric' => $daysEnabled,
                'schedule_days' => array_keys($schedule),
            ]);

            // Helper to extract time from slot (supports both old string and new object format)
            $getSlotTime = function ($slot) {
                if (is_string($slot)) {
                    return $slot;
                }
                return $slot['time'] ?? null;
            };

            // Try to find a slot today
            if (in_array($currentDay, $daysEnabled)) {
                $todaySlots = $schedule[$currentDayName] ?? [];
                // Extract times and sort them
                $todayTimes = array_filter(array_map($getSlotTime, $todaySlots));
                sort($todayTimes);

                foreach ($todayTimes as $time) {
                    if ($time > $currentTime) {
                        $slotTime = \Carbon\Carbon::parse($now->format('Y-m-d') . ' ' . $time . ':00', $timezone);
                        Log::debug('[QUEUE] Found slot today', [
                            'slot_local' => $slotTime->toDateTimeString(),
                            'slot_utc' => $slotTime->utc()->toDateTimeString(),
                        ]);
                        return $slotTime->utc();
                    }
                }
            }

            // Find the next enabled day with slots
            for ($i = 1; $i <= 7; $i++) {
                $nextDay = $now->copy()->addDays($i);
                $nextDayOfWeek = $nextDay->dayOfWeek;
                $nextDayName = strtolower($nextDay->format('l'));

                if (in_array($nextDayOfWeek, $daysEnabled)) {
                    $daySlots = $schedule[$nextDayName] ?? [];
                    $dayTimes = array_filter(array_map($getSlotTime, $daySlots));

                    if (!empty($dayTimes)) {
                        sort($dayTimes);
                        $firstTime = $dayTimes[0];
                        $slotTime = \Carbon\Carbon::parse($nextDay->format('Y-m-d') . ' ' . $firstTime . ':00', $timezone);
                        Log::debug('[QUEUE] Found slot on future day', [
                            'days_ahead' => $i,
                            'day_name' => $nextDayName,
                            'slot_local' => $slotTime->toDateTimeString(),
                            'slot_utc' => $slotTime->utc()->toDateTimeString(),
                        ]);
                        return $slotTime->utc();
                    }
                }
            }

            Log::warning('[QUEUE] No slot found within 7 days', ['profile_id' => $profileId]);
            return null;

        } catch (\Exception $e) {
            Log::error('[QUEUE] Failed to get queue slot', [
                'profile_id' => $profileId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Build post data for creation.
     */
    protected function buildPostData(
        string $org,
        Integration $profile,
        array $content,
        bool $isDraft,
        bool $requiresApproval,
        bool $needsApproval,
        ?\Carbon\Carbon $scheduledAt,
        bool $isQueueRequest = false
    ): array {
        $platform = $profile->platform;
        $postContent = $content['platforms'][$platform]['text'] ?? $content['global']['text'] ?? '';

        // Determine status based on request type
        $status = match (true) {
            $isDraft => 'draft',
            $requiresApproval || $needsApproval => 'pending_approval',
            $scheduledAt !== null => 'scheduled',
            // NEW: Queue request without slot should still be scheduled (won't publish immediately)
            $isQueueRequest && $scheduledAt === null => 'draft', // Save as draft if no queue slot available
            default => 'pending',
        };

        // Log if queue request but no slot found
        if ($isQueueRequest && $scheduledAt === null) {
            Log::warning('[QUEUE] Queue request but no slot available, saving as draft', [
                'profile_id' => $profile->integration_id,
                'platform' => $platform,
            ]);
        }

        return [
            'org_id' => $org,
            'integration_id' => $profile->integration_id,
            'profile_group_id' => $profile->profile_group_id,
            'platform' => $platform,
            'content' => $postContent,
            'media' => $content['global']['media'] ?? [],
            'tags' => $content['global']['labels'] ?? [],
            'options' => $content['platforms'][$platform] ?? [],
            'metadata' => [
                'link' => $content['global']['link'] ?? null,
                'created_from' => 'publish_modal',
                'is_queue_request' => $isQueueRequest, // NEW: Track if this was a queue request
            ],
            'status' => $status,
            'scheduled_at' => $scheduledAt,
            'created_by' => auth()->id(),
        ];
    }

    /**
     * Publish a post to its platform.
     */
    protected function publishPost(string $org, SocialPost $post, string $platform, string $content, array $media): array
    {
        try {
            Log::info('Publishing post to platform', [
                'post_id' => $post->id ?? $post->post_id ?? 'unknown',
                'platform' => $platform,
                'org_id' => $org,
            ]);

            $post->update(['status' => 'publishing']);

            $publisher = $this->publisherFactory->getPublisher($platform, $org);

            if (!$publisher || !$publisher->hasActiveConnection()) {
                $platformType = $this->getPlatformType($platform);
                $errorMsg = "No active {$platformType} connection found. Please connect your account in Settings > Platform Connections.";

                $post->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'error_message' => $errorMsg,
                ]);

                return ['success' => false, 'message' => $errorMsg];
            }

            $result = $publisher->publish($content, $media, $post->options ?? []);

            if ($result['success']) {
                $post->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'post_external_id' => $result['post_id'] ?? null,
                    'permalink' => $result['permalink'] ?? null,
                ]);
            } else {
                $post->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'error_message' => $result['message'] ?? 'Unknown error',
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to publish post', [
                'post_id' => $post->id ?? 'unknown',
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);

            $post->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get the platform type from the platform name.
     */
    protected function getPlatformType(string $platform): string
    {
        return match ($platform) {
            'facebook', 'instagram' => 'meta',
            'google_business' => 'google',
            default => $platform,
        };
    }

    /**
     * Build the creation result response (async version).
     */
    protected function buildCreationResultAsync(
        array $posts,
        bool $isDraft,
        bool $needsApproval,
        bool $requiresApproval,
        ?\Carbon\Carbon $scheduledAt,
        int $jobsDispatched,
        bool $isQueueRequest = false
    ): array {
        $postIds = array_map(fn($p) => $p->id, $posts);

        // Count scheduled posts (for queue requests, each post may have different scheduled_at)
        $scheduledCount = 0;
        $draftCount = 0;
        foreach ($posts as $post) {
            if ($post->status === 'scheduled' && $post->scheduled_at) {
                $scheduledCount++;
            } elseif ($post->status === 'draft') {
                $draftCount++;
            }
        }

        // Build status message
        $statusMessage = match (true) {
            $isDraft => 'saved as draft',
            $needsApproval || $requiresApproval => 'submitted for approval',
            $isQueueRequest && $scheduledCount > 0 => 'added to queue',
            $scheduledAt !== null => 'scheduled',
            $jobsDispatched > 0 => 'queued for publishing',
            default => 'created'
        };

        // Add warning for queue requests where some posts couldn't be scheduled
        $warning = null;
        if ($isQueueRequest && $draftCount > 0) {
            $warning = sprintf(
                '%d post(s) saved as draft because queue is not configured for those profiles. Please configure queue settings in Profile Management.',
                $draftCount
            );
        }

        $result = [
            'data' => [
                'posts' => $posts,
                'post_ids' => $postIds,
                'count' => count($posts),
                'queued_count' => $jobsDispatched,
                'scheduled_count' => $scheduledCount, // NEW: Count of posts added to queue
                'draft_count' => $draftCount, // NEW: Count of posts saved as draft
                'requires_approval' => $needsApproval || $requiresApproval,
                'is_async' => $jobsDispatched > 0,
                'is_queue_request' => $isQueueRequest, // NEW: Track queue requests
            ],
            'message' => count($posts) . ' post(s) ' . $statusMessage . ' successfully',
        ];

        if ($warning) {
            $result['data']['warning'] = $warning;
        }

        return $result;
    }

    /**
     * Get the status of multiple posts (for polling after async publish).
     */
    public function getPostsStatus(Request $request, string $org)
    {
        $validator = Validator::make($request->all(), [
            'post_ids' => 'required|array|min:1',
            'post_ids.*' => 'uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $postIds = $request->input('post_ids');

        $posts = SocialPost::where('org_id', $org)
            ->whereIn('id', $postIds)
            ->select([
                'id',
                'platform',
                'status',
                'published_at',
                'failed_at',
                'error_message',
                'post_external_id',
                'permalink',
            ])
            ->get()
            ->keyBy('id');

        $statuses = [];
        $allComplete = true;
        $successCount = 0;
        $failedCount = 0;

        foreach ($postIds as $postId) {
            $post = $posts->get($postId);
            if (!$post) {
                $statuses[$postId] = ['status' => 'not_found'];
                continue;
            }

            $statuses[$postId] = [
                'status' => $post->status,
                'platform' => $post->platform,
                'published_at' => $post->published_at?->toIso8601String(),
                'failed_at' => $post->failed_at?->toIso8601String(),
                'error_message' => $post->error_message,
                'post_external_id' => $post->post_external_id,
                'permalink' => $post->permalink,
            ];

            if (in_array($post->status, ['queued', 'pending', 'publishing'])) {
                $allComplete = false;
            } elseif ($post->status === 'published') {
                $successCount++;
            } elseif ($post->status === 'failed') {
                $failedCount++;
            }
        }

        return $this->success([
            'statuses' => $statuses,
            'all_complete' => $allComplete,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'pending_count' => count($postIds) - $successCount - $failedCount,
        ], 'Post statuses retrieved');
    }

    /**
     * Build the creation result response (legacy sync version).
     * @deprecated Use buildCreationResultAsync instead
     */
    protected function buildCreationResult(array $posts, bool $isDraft, bool $needsApproval, bool $requiresApproval, ?\Carbon\Carbon $scheduledAt): array
    {
        $successCount = 0;
        $failedCount = 0;

        foreach ($posts as $post) {
            if (isset($post->publish_result)) {
                if ($post->publish_result['success'] ?? false) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            }
        }

        $statusMessage = match (true) {
            $isDraft => 'saved as draft',
            $needsApproval || $requiresApproval => 'submitted for approval',
            $scheduledAt !== null => 'scheduled',
            $successCount > 0 && $failedCount === 0 => 'published',
            $successCount > 0 && $failedCount > 0 => 'partially published',
            $failedCount > 0 && $successCount === 0 => 'failed to publish',
            default => 'created'
        };

        return [
            'data' => [
                'posts' => $posts,
                'count' => count($posts),
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'requires_approval' => $needsApproval || $requiresApproval,
            ],
            'message' => count($posts) . ' post(s) ' . $statusMessage . ' successfully',
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
        $result = $this->bestTimesService->getBestTimes($org, $profileIds);

        return $this->success($result, 'Best posting times retrieved');
    }

    /**
     * Get character limits for different platforms.
     */
    public function getCharacterLimits(Request $request)
    {
        $limits = $this->bestTimesService->getCharacterLimits();
        return $this->success($limits, 'Character limits retrieved');
    }
}
