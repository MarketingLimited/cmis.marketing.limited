<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
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
     */
    protected function processPostCreation(Request $request, string $org): array
    {
        $profileIds = $request->input('profile_ids');
        $content = $request->input('content');
        $schedule = $request->input('schedule');
        $isDraft = $request->input('is_draft', false);
        $requiresApproval = $request->input('requires_approval', false);

        $globalText = $content['global']['text'] ?? '';

        // Pre-validate brand safety
        if (!$isDraft && $globalText) {
            $safetyResult = $this->brandSafetyService->validate($org, $globalText, null);
            if (!$safetyResult['passed']) {
                throw new \Exception('Brand safety check failed: ' . implode(', ', $safetyResult['issues']));
            }
        }

        $scheduledAt = $this->parseScheduleTime($schedule);
        $posts = [];
        $needsApproval = false;

        foreach ($profileIds as $profileId) {
            $profile = Integration::where('org_id', $org)
                ->where('integration_id', $profileId)
                ->first();

            if (!$profile) {
                continue;
            }

            $needsApproval = $needsApproval || $this->checkApprovalRequired($org, $profile);

            $postData = $this->buildPostData($org, $profile, $content, $isDraft, $requiresApproval, $needsApproval, $scheduledAt);
            $post = SocialPost::create($postData);

            // Publish immediately if not draft/scheduled/pending
            if ($postData['status'] === 'pending') {
                $post->publish_result = $this->publishPost($org, $post, $profile->platform, $postData['content'], $content['global']['media'] ?? []);
            }

            $posts[] = $post;
        }

        return $this->buildCreationResult($posts, $isDraft, $needsApproval, $requiresApproval, $scheduledAt);
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
     * Build the creation result response.
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
