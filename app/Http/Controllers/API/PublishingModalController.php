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

            $postData = $this->buildPostData($org, $profile, $content, $isDraft, $requiresApproval, $needsApproval, $scheduledAt);

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

        return $this->buildCreationResultAsync($posts, $isDraft, $needsApproval, $requiresApproval, $scheduledAt, $jobsDispatched);
    }

    /**
     * Parse schedule time from request.
     */
    protected function parseScheduleTime(?array $schedule): ?\Carbon\Carbon
    {
        if (!$schedule || empty($schedule['date']) || empty($schedule['time'])) {
            return null;
        }

        $timezone = $schedule['timezone'] ?? 'UTC';
        return \Carbon\Carbon::parse("{$schedule['date']} {$schedule['time']}", $timezone)->utc();
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
     * Build post data for creation.
     */
    protected function buildPostData(
        string $org,
        Integration $profile,
        array $content,
        bool $isDraft,
        bool $requiresApproval,
        bool $needsApproval,
        ?\Carbon\Carbon $scheduledAt
    ): array {
        $platform = $profile->platform;
        $postContent = $content['platforms'][$platform]['text'] ?? $content['global']['text'] ?? '';

        $status = match (true) {
            $isDraft => 'draft',
            $requiresApproval || $needsApproval => 'pending_approval',
            $scheduledAt !== null => 'scheduled',
            default => 'pending',
        };

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
        int $jobsDispatched
    ): array {
        $postIds = array_map(fn($p) => $p->id, $posts);

        $statusMessage = match (true) {
            $isDraft => 'saved as draft',
            $needsApproval || $requiresApproval => 'submitted for approval',
            $scheduledAt !== null => 'scheduled',
            $jobsDispatched > 0 => 'queued for publishing',
            default => 'created'
        };

        return [
            'data' => [
                'posts' => $posts,
                'post_ids' => $postIds,
                'count' => count($posts),
                'queued_count' => $jobsDispatched,
                'requires_approval' => $needsApproval || $requiresApproval,
                'is_async' => $jobsDispatched > 0,
            ],
            'message' => count($posts) . ' post(s) ' . $statusMessage . ' successfully',
        ];
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
