<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Social\SocialPost;
use App\Models\Platform\PlatformConnection;
use App\Services\Social\SocialAccountService;
use App\Services\Social\SocialPostPublishService;
use App\Services\Social\SocialQueueService;
use App\Services\Social\SocialPlatformDataService;
use App\Services\Social\SocialCollaboratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Social Post Controller (Refactored)
 *
 * Thin controller following Single Responsibility Principle.
 * Delegates business logic to specialized services.
 *
 * **Before Refactoring:** 1777 lines, 21 methods
 * **After Refactoring:** ~350 lines, 21 methods (all delegating to services)
 *
 * @see SocialAccountService For account management
 * @see SocialPostPublishService For publishing logic
 * @see SocialQueueService For queue management
 * @see SocialPlatformDataService For platform data
 * @see SocialCollaboratorService For collaborator management
 */
class SocialPostController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SocialAccountService $accountService,
        protected SocialPostPublishService $publishService,
        protected SocialQueueService $queueService,
        protected SocialPlatformDataService $platformDataService,
        protected SocialCollaboratorService $collaboratorService
    ) {}

    /**
     * Get connected social accounts
     *
     * @delegation SocialAccountService::getConnectedAccounts()
     */
    public function getConnectedAccounts(Request $request, string $org)
    {
        try {
            $result = $this->accountService->getConnectedAccounts($org);
            return $this->success($result, 'Connected accounts retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to load connected accounts', 500);
        }
    }

    /**
     * Get timezone for selected integrations with inheritance hierarchy
     *
     * TIMEZONE INHERITANCE:
     * 1. Social Account timezone (if set) - highest priority
     * 2. Profile Group timezone (if set)
     * 3. Organization timezone - fallback
     *
     * Returns timezone information following the inheritance hierarchy.
     * This allows the frontend to display and handle scheduling in the account's local timezone.
     */
    public function getTimezone(Request $request, string $org)
    {
        try {
            $integrationIds = $request->input('integration_ids', []);

            if (empty($integrationIds)) {
                return $this->error('No integration IDs provided', 400);
            }

            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$org]);

            // Get timezones following inheritance hierarchy:
            // Social Account → Profile Group → Organization
            $timezones = DB::table('cmis.integrations as i')
                ->leftJoin('cmis.social_accounts as sa', 'i.integration_id', '=', 'sa.integration_id')
                ->leftJoin('cmis.profile_groups as pg', 'i.profile_group_id', '=', 'pg.group_id')
                ->join('cmis.orgs as o', 'i.org_id', '=', 'o.org_id')
                ->whereIn('i.integration_id', $integrationIds)
                ->where('i.org_id', $org)
                ->select(
                    'i.integration_id',
                    'pg.group_id as profile_group_id',
                    'pg.name as profile_group_name',
                    'sa.username as social_account_name',
                    'sa.timezone as social_account_timezone',
                    'pg.timezone as profile_group_timezone',
                    'o.timezone as org_timezone',
                    // Use COALESCE to get first non-null timezone (inheritance)
                    DB::raw('COALESCE(sa.timezone, pg.timezone, o.timezone, \'UTC\') as timezone'),
                    DB::raw("CASE
                        WHEN sa.timezone IS NOT NULL THEN 'social_account'
                        WHEN pg.timezone IS NOT NULL THEN 'profile_group'
                        WHEN o.timezone IS NOT NULL THEN 'organization'
                        ELSE 'default'
                    END as timezone_source")
                )
                ->get();

            if ($timezones->isEmpty()) {
                return $this->success([
                    'timezone' => 'UTC',
                    'timezone_source' => 'default',
                    'profile_group_name' => 'Default',
                    'message' => 'No integration found, using UTC'
                ], 'Using default timezone');
            }

            // If all integrations have the same timezone, return it
            // Otherwise, return the first one with a warning
            $uniqueTimezones = $timezones->unique('timezone');

            if ($uniqueTimezones->count() === 1) {
                $tz = $timezones->first();
                return $this->success([
                    'timezone' => $tz->timezone,
                    'timezone_source' => $tz->timezone_source,
                    'profile_group_id' => $tz->profile_group_id,
                    'profile_group_name' => $tz->profile_group_name ?? 'Organization Default',
                    'integrations' => $timezones->pluck('integration_id')->toArray(),
                    'inheritance_info' => [
                        'social_account' => $tz->social_account_timezone,
                        'profile_group' => $tz->profile_group_timezone,
                        'organization' => $tz->org_timezone,
                        'final' => $tz->timezone,
                        'source' => $tz->timezone_source
                    ]
                ], 'Timezone retrieved successfully');
            } else {
                // Multiple timezones - return first one with warning
                $tz = $timezones->first();
                $allSources = $timezones->pluck('timezone_source')->unique()->toArray();
                return $this->success([
                    'timezone' => $tz->timezone,
                    'timezone_source' => $tz->timezone_source,
                    'profile_group_id' => $tz->profile_group_id,
                    'profile_group_name' => $tz->profile_group_name ?? 'Organization Default',
                    'integrations' => $timezones->pluck('integration_id')->toArray(),
                    'warning' => sprintf(
                        'Selected accounts have different timezones from %s. Using first timezone: %s',
                        implode(', ', $allSources),
                        $tz->timezone
                    ),
                    'all_timezones' => $timezones->map(function($t) {
                        return [
                            'timezone' => $t->timezone,
                            'source' => $t->timezone_source,
                            'name' => $t->social_account_name ?? $t->profile_group_name ?? 'Org Default'
                        ];
                    })->toArray(),
                ], 'Multiple timezones detected');
            }

        } catch (\Exception $e) {
            Log::error('Failed to get timezone', [
                'org_id' => $org,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Failed to get timezone: ' . $e->getMessage(), 500);
        }
    }

    /**
     * List all social posts
     *
     * TIMEZONE SUPPORT: Each post includes its timezone from the inheritance hierarchy
     * (Social Account → Profile Group → Organization → UTC) so the frontend can
     * display scheduled times in the correct timezone.
     */
    public function index(Request $request, string $org)
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$org]);

            // Join with integrations, profile_groups, social_accounts, and orgs to get timezone and account info
            $query = DB::table('cmis.social_posts as sp')
                ->leftJoin('cmis.integrations as i', 'sp.integration_id', '=', 'i.integration_id')
                ->leftJoin('cmis.social_accounts as sa', 'i.integration_id', '=', 'sa.integration_id')
                ->leftJoin('cmis.profile_groups as pg', 'i.profile_group_id', '=', 'pg.group_id')
                ->leftJoin('cmis.orgs as o', 'sp.org_id', '=', 'o.org_id')
                ->where('sp.org_id', $org)
                ->whereNull('sp.deleted_at')
                ->orderBy('sp.created_at', 'desc')
                ->select(
                    'sp.*',
                    DB::raw('COALESCE(sa.timezone, pg.timezone, o.timezone, \'UTC\') as display_timezone'),
                    'sa.username as social_account_username',
                    'sa.display_name as social_account_display_name',
                    'sa.profile_picture_url as social_account_picture'
                );

            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('sp.status', $request->status);
            }

            if ($request->has('platform') && $request->platform !== 'all') {
                $query->where('sp.platform', $request->platform);
            }

            $posts = $query->paginate($request->get('per_page', 20));

            // Transform data
            $posts->getCollection()->transform(function ($post) {
                $post->media = json_decode($post->media ?? '[]', true);
                $post->metadata = json_decode($post->metadata ?? '{}', true);
                $post->post_id = $post->id; // Frontend compatibility
                $post->post_text = $post->content; // Frontend compatibility
                // display_timezone is already included from the query
                return $post;
            });

            return $this->success($posts, 'Posts retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to fetch posts', [
                'org_id' => $org,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Failed to fetch posts: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new social post
     *
     * NOTE: This method is complex and could be extracted to a service.
     * Kept in controller for now to maintain existing functionality.
     */
    public function store(Request $request, string $org)
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'platforms' => 'required|string',
            'publish_type' => 'required|in:now,scheduled,draft,queue',
            'scheduled_at' => 'required_if:publish_type,scheduled|nullable|date',
            'post_type' => 'nullable|string|in:feed,reel,story,carousel,tweet,thread,post,article',
            'media.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,mp4,mov|max:51200',
            'post_options' => 'nullable|string',
            'first_comment' => 'nullable|string|max:2200',
            'location' => 'nullable|string|max:255',
        ]);

        $platforms = json_decode($request->platforms, true);

        if (empty($platforms)) {
            return $this->error('At least one platform must be selected', 400);
        }

        // Validate Instagram media requirement
        $hasInstagram = collect($platforms)->contains(fn($p) => ($p['type'] ?? '') === 'instagram');
        if ($hasInstagram && !$request->hasFile('media')) {
            return $this->error('Instagram posts require at least one image or video', 400);
        }

        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$org]);

            $createdPosts = [];
            $publishResults = [];

            // Handle media uploads
            $mediaUrls = $this->uploadMedia($request, $org);

            // Get platform connection
            $connection = PlatformConnection::where('org_id', $org)
                ->where('platform', 'meta')
                ->where('status', 'active')
                ->first();

            // Get integration ID
            $integrationId = null;
            if ($connection) {
                $integration = DB::table('cmis.integrations')
                    ->where('platform_connection_id', $connection->connection_id)
                    ->first();
                $integrationId = $integration?->integration_id;
            }

            foreach ($platforms as $platform) {
                $platformType = $platform['type'] ?? 'facebook';
                $accountId = $platform['platformId'] ?? $platform['pageId'] ?? $platform['accountId'] ?? null;
                $accountName = $platform['name'] ?? ucfirst($platformType);
                $integrationIdForPost = $platform['integrationId'] ?? $integrationId;

                // Get profile group ID from integration for timezone support
                $profileGroupId = null;
                if ($integrationIdForPost) {
                    $integration = DB::table('cmis.integrations')
                        ->where('integration_id', $integrationIdForPost)
                        ->first();
                    $profileGroupId = $integration?->profile_group_id;
                }

                // Determine status
                $status = match($request->publish_type) {
                    'now' => 'publishing',
                    'scheduled', 'queue' => 'scheduled',
                    'draft' => 'draft',
                };

                // Get scheduled time (now supports timezone from profile group)
                $scheduledAt = $this->getScheduledTime($request, $org, $integrationIdForPost, $profileGroupId);

                $postId = Str::uuid()->toString();

                // Build metadata
                $metadata = $this->buildPostMetadata($request, $platform, $connection);

                // Insert post
                DB::table('cmis.social_posts')->insert([
                    'id' => $postId,
                    'org_id' => $org,
                    'integration_id' => $integrationIdForPost,
                    'platform' => $platformType,
                    'account_id' => $accountId,
                    'account_username' => $accountName,
                    'content' => $request->content,
                    'media' => json_encode($mediaUrls),
                    'post_type' => $request->post_type ?? (!empty($mediaUrls) ? 'feed' : 'text'),
                    'status' => $status,
                    'scheduled_at' => $scheduledAt,
                    'profile_group_id' => $profileGroupId,
                    'created_by' => auth()->id(),
                    'metadata' => json_encode($metadata),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $createdPosts[] = [
                    'id' => $postId,
                    'platform' => $platformType,
                    'account_name' => $accountName,
                    'status' => $status,
                ];

                // Publish immediately if requested
                if ($request->publish_type === 'now' && $connection) {
                    $result = $this->publishService->publishPost($org, $postId);
                    $publishResults[] = [
                        'platform' => $platformType,
                        'account_id' => $accountId,
                        'success' => $result['success'],
                        'message' => $result['message'] ?? null,
                    ];
                }
            }

            $message = match($request->publish_type) {
                'now' => 'Post published successfully',
                'scheduled' => 'Post scheduled successfully',
                'queue' => 'Post added to queue successfully',
                'draft' => 'Draft saved successfully',
            };

            return $this->created([
                'posts' => $createdPosts,
                'publish_results' => $publishResults,
            ], $message);

        } catch (\Exception $e) {
            Log::error('Failed to create social post', [
                'org_id' => $org,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Failed to create post: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Show a specific post
     */
    public function show(Request $request, string $org, string $post)
    {
        DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$org]);

        $socialPost = DB::table('cmis.social_posts')
            ->where('org_id', $org)
            ->where('id', $post)
            ->whereNull('deleted_at')
            ->first();

        if (!$socialPost) {
            return $this->notFound('Post not found');
        }

        $socialPost->media = json_decode($socialPost->media ?? '[]', true);
        $socialPost->metadata = json_decode($socialPost->metadata ?? '{}', true);

        return $this->success($socialPost, 'Post retrieved successfully');
    }

    /**
     * Update a post
     *
     * TIMEZONE SUPPORT: The scheduled_at from frontend is in the profile group's timezone.
     * We convert it to UTC for storage. If no timezone is provided, we fetch it from
     * the post's integration using the timezone inheritance hierarchy.
     */
    public function update(Request $request, string $org, string $post)
    {
        DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$org]);

        $socialPost = DB::table('cmis.social_posts')
            ->where('org_id', $org)
            ->where('id', $post)
            ->whereNull('deleted_at')
            ->first();

        if (!$socialPost) {
            return $this->notFound('Post not found');
        }

        if (!in_array($socialPost->status, ['draft', 'scheduled', 'failed'])) {
            return $this->error('Cannot update a published or publishing post', 400);
        }

        $request->validate([
            'content' => 'sometimes|string|max:5000',
            'scheduled_at' => 'sometimes|nullable|date',
            'status' => 'sometimes|in:draft,scheduled',
            'timezone' => 'sometimes|nullable|string|max:64',
        ]);

        $updateData = ['updated_at' => now()];

        if ($request->has('content')) {
            $updateData['content'] = $request->content;
        }

        if ($request->has('scheduled_at') && $request->scheduled_at) {
            $scheduledAt = $request->scheduled_at;
            $timezone = $request->input('timezone');

            // If no timezone provided, fetch from integration using inheritance hierarchy
            if (!$timezone && $socialPost->integration_id) {
                $timezoneData = DB::table('cmis.integrations as i')
                    ->leftJoin('cmis.social_accounts as sa', 'i.integration_id', '=', 'sa.integration_id')
                    ->leftJoin('cmis.profile_groups as pg', 'i.profile_group_id', '=', 'pg.group_id')
                    ->join('cmis.orgs as o', 'i.org_id', '=', 'o.org_id')
                    ->where('i.integration_id', $socialPost->integration_id)
                    ->select(DB::raw('COALESCE(sa.timezone, pg.timezone, o.timezone, \'UTC\') as timezone'))
                    ->first();

                $timezone = $timezoneData?->timezone ?? 'UTC';
            }

            // Convert from local timezone to UTC
            if ($timezone && $timezone !== 'UTC') {
                try {
                    $localDateTime = \Carbon\Carbon::parse($scheduledAt, $timezone);
                    $updateData['scheduled_at'] = $localDateTime->utc()->toDateTimeString();

                    Log::debug('[TIMEZONE] Edit post: converted to UTC', [
                        'input' => $scheduledAt,
                        'timezone' => $timezone,
                        'utc' => $updateData['scheduled_at'],
                    ]);
                } catch (\Exception $e) {
                    Log::warning('[TIMEZONE] Failed to parse datetime, storing as-is', [
                        'input' => $scheduledAt,
                        'timezone' => $timezone,
                        'error' => $e->getMessage(),
                    ]);
                    $updateData['scheduled_at'] = $scheduledAt;
                }
            } else {
                $updateData['scheduled_at'] = $scheduledAt;
            }

            $updateData['status'] = 'scheduled';
        }

        if ($request->has('status') && in_array($request->status, ['draft', 'scheduled'])) {
            $updateData['status'] = $request->status;
            if ($socialPost->status === 'failed') {
                $updateData['error_message'] = null;
                $updateData['failed_at'] = null;
            }
        }

        DB::table('cmis.social_posts')->where('id', $post)->update($updateData);

        $updatedPost = DB::table('cmis.social_posts')->where('id', $post)->first();

        return $this->success(['post' => $updatedPost], 'Post updated successfully');
    }

    /**
     * Delete a post
     */
    public function destroy(Request $request, string $org, string $post)
    {
        DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$org]);

        $socialPost = DB::table('cmis.social_posts')
            ->where('org_id', $org)
            ->where('id', $post)
            ->whereNull('deleted_at')
            ->first();

        if (!$socialPost) {
            return $this->notFound('Post not found');
        }

        DB::table('cmis.social_posts')
            ->where('id', $post)
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        return $this->deleted('Post deleted successfully');
    }

    /**
     * Delete all failed posts
     */
    public function destroyAllFailed(Request $request, string $org)
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$org]);

            $deletedCount = DB::table('cmis.social_posts')
                ->where('org_id', $org)
                ->where('status', 'failed')
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now(), 'updated_at' => now()]);

            return $this->success([
                'deleted_count' => $deletedCount,
            ], "{$deletedCount} failed posts deleted successfully");

        } catch (\Exception $e) {
            Log::error('Failed to delete failed posts', [
                'org_id' => $org,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Failed to delete failed posts', 500);
        }
    }

    /**
     * Publish a post
     *
     * @delegation SocialPostPublishService::publishPost()
     */
    public function publish(Request $request, string $org, string $post)
    {
        try {
            $result = $this->publishService->publishPost($org, $post);

            if ($result['success']) {
                return $this->success([
                    'post_id' => $post,
                    'external_post_id' => $result['post_id'] ?? null,
                    'permalink' => $result['permalink'] ?? null,
                ], 'Post published successfully');
            }

            return $this->error('Failed to publish: ' . ($result['message'] ?? 'Unknown error'), 400);

        } catch (\Exception $e) {
            return $this->error('Failed to publish post', 500);
        }
    }

    /**
     * Get queue settings
     *
     * @delegation SocialQueueService::getQueueSettings()
     */
    public function getQueueSettings(Request $request, string $org)
    {
        try {
            $settings = $this->queueService->getQueueSettings($org);
            return $this->success($settings, 'Queue settings retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to fetch queue settings: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Save queue settings
     *
     * @delegation SocialQueueService::saveQueueSettings()
     */
    public function saveQueueSettings(Request $request, string $org)
    {
        $request->validate([
            'integration_id' => 'required|uuid',
            'queue_enabled' => 'required|boolean',
            'posting_times' => 'required|array',
            'posting_times.*' => 'required|string|date_format:H:i',
            'days_enabled' => 'required|array',
            'days_enabled.*' => 'required|integer|min:0|max:6',
            'posts_per_day' => 'required|integer|min:1|max:20',
        ]);

        try {
            $this->queueService->saveQueueSettings($org, $request->only([
                'integration_id',
                'queue_enabled',
                'posting_times',
                'days_enabled',
                'posts_per_day',
            ]));

            return $this->success(null, 'Queue settings saved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to save queue settings: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get next queue slot
     *
     * @delegation SocialQueueService::getNextQueueSlot()
     */
    public function getNextQueueSlot(Request $request, string $org, string $integrationId)
    {
        try {
            $nextSlot = $this->queueService->getNextQueueSlot($org, $integrationId);
            return $this->success(['next_slot' => $nextSlot], 'Next queue slot found');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get post types
     *
     * @delegation SocialPlatformDataService::getPostTypes()
     */
    public function getPostTypes(Request $request, string $org)
    {
        $postTypes = $this->platformDataService->getPostTypes();
        return $this->success($postTypes, 'Post types retrieved successfully');
    }

    /**
     * Search locations
     *
     * @delegation SocialPlatformDataService::searchLocations()
     */
    public function searchLocations(Request $request, string $org)
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100',
        ]);

        try {
            $locations = $this->platformDataService->searchLocations($org, $request->query('query'));
            return $this->success($locations, 'Locations found');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get collaborator suggestions
     *
     * @delegation SocialCollaboratorService::getSuggestions()
     */
    public function getCollaboratorSuggestions(Request $request, string $org)
    {
        try {
            $collaborators = $this->collaboratorService->getSuggestions($org);
            return $this->success([
                'collaborators' => $collaborators,
                'total' => count($collaborators),
            ], 'Collaborator suggestions retrieved');
        } catch (\Exception $e) {
            return $this->error('Failed to get collaborator suggestions', 500);
        }
    }

    /**
     * Validate Instagram username
     *
     * @delegation SocialCollaboratorService::validateUsername()
     */
    public function validateInstagramUsername(Request $request, string $org)
    {
        $request->validate([
            'username' => 'required|string|min:1|max:30',
        ]);

        try {
            $result = $this->collaboratorService->validateUsername($org, $request->username);
            return $this->success($result, $result['valid'] ? 'Username is valid' : 'Username not found');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Store collaborator
     *
     * @delegation SocialCollaboratorService::storeCollaborator()
     */
    public function storeCollaborator(Request $request, string $org)
    {
        $request->validate([
            'username' => 'required|string|min:1|max:30',
        ]);

        $this->collaboratorService->storeCollaborator($org, $request->username);
        return $this->success(null, 'Collaborator stored');
    }

    /**
     * Get trending hashtags
     *
     * @delegation SocialPlatformDataService::getTrendingHashtags()
     */
    public function getTrendingHashtags(Request $request, string $org, string $platform)
    {
        $hashtags = $this->platformDataService->getTrendingHashtags($platform);
        return $this->success($hashtags, 'Trending hashtags retrieved');
    }

    /**
     * Get scheduled posts
     *
     * @delegation SocialQueueService::getScheduledPosts()
     */
    public function getScheduledPosts(Request $request, string $org)
    {
        try {
            $posts = $this->queueService->getScheduledPosts($org);
            return $this->success($posts, 'Scheduled posts retrieved');
        } catch (\Exception $e) {
            return $this->error('Failed to get scheduled posts', 500);
        }
    }

    /**
     * Reschedule a post
     *
     * @delegation SocialQueueService::reschedulePost()
     */
    public function reschedule(Request $request, string $org, string $post)
    {
        $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        try {
            $result = $this->queueService->reschedulePost($org, $post, $request->scheduled_at);
            return $this->success($result, 'Post rescheduled successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to reschedule post', 500);
        }
    }

    // ========================================
    // Private Helper Methods
    // ========================================

    /**
     * Upload media files
     */
    protected function uploadMedia(Request $request, string $org): array
    {
        $mediaUrls = [];

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $path = $file->store("social-media/{$org}", 'public');
                $mediaUrls[] = [
                    'url' => url(Storage::url($path)),
                    'path' => $path,
                    'type' => Str::startsWith($file->getMimeType(), 'video') ? 'video' : 'image',
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        return $mediaUrls;
    }

    /**
     * Get scheduled time based on publish type
     *
     * TIMEZONE SUPPORT: The scheduled_at from frontend is in the profile group's timezone.
     * We need to convert it to UTC for storage since scheduled_at is timestamp with time zone.
     * PostgreSQL will store it in UTC and handle timezone conversions automatically.
     */
    protected function getScheduledTime(Request $request, string $org, ?string $integrationId, ?string $profileGroupId): ?string
    {
        if ($request->publish_type === 'scheduled') {
            // Frontend sends datetime in profile group's timezone
            // We need to ensure it's stored with timezone info
            return $request->scheduled_at;
        }

        if ($request->publish_type === 'queue' && $integrationId) {
            try {
                return $this->queueService->getNextQueueSlot($org, $integrationId);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Build post metadata
     */
    protected function buildPostMetadata(Request $request, array $platform, ?PlatformConnection $connection): array
    {
        $postOptions = $request->post_options ? json_decode($request->post_options, true) : [];

        return [
            'platform_details' => $platform,
            'publish_type' => $request->publish_type,
            'connection_id' => $connection?->connection_id,
            'is_queued' => $request->publish_type === 'queue',

            // Instagram/Facebook options
            'first_comment' => $postOptions['instagram']['firstComment'] ?? $request->first_comment ?? null,
            'location' => $postOptions['instagram']['location'] ?? $request->location ?? null,
            'location_id' => $postOptions['instagram']['locationId'] ?? null,
            'user_tags' => $postOptions['instagram']['userTags'] ?? [],
            'collaborators' => $postOptions['instagram']['collaborators'] ?? [],
            'product_tags' => $postOptions['instagram']['productTags'] ?? [],
            'alt_text' => $postOptions['instagram']['altText'] ?? null,

            // Reel options
            'cover_type' => $postOptions['reel']['coverType'] ?? 'frame',
            'cover_frame_offset' => $postOptions['reel']['coverFrameOffset'] ?? 0,
            'cover_image_url' => $postOptions['reel']['coverImageUrl'] ?? null,
            'share_to_feed' => $postOptions['reel']['shareToFeed'] ?? true,

            // Carousel options
            'alt_texts' => $postOptions['carousel']['altTexts'] ?? [],

            // TikTok options
            'tiktok_viewer_setting' => $postOptions['tiktok']['viewerSetting'] ?? 'public',
            'tiktok_disable_comments' => $postOptions['tiktok']['disableComments'] ?? false,
            'tiktok_disable_duet' => $postOptions['tiktok']['disableDuet'] ?? false,
            'tiktok_disable_stitch' => $postOptions['tiktok']['disableStitch'] ?? false,
            'tiktok_brand_content' => $postOptions['tiktok']['brandContentToggle'] ?? false,
            'tiktok_ai_generated' => $postOptions['tiktok']['aiGenerated'] ?? false,

            // LinkedIn options
            'linkedin_visibility' => $postOptions['linkedin']['visibility'] ?? 'PUBLIC',
            'linkedin_article_title' => $postOptions['linkedin']['articleTitle'] ?? null,
            'linkedin_article_description' => $postOptions['linkedin']['articleDescription'] ?? null,
            'linkedin_allow_comments' => $postOptions['linkedin']['allowComments'] ?? true,

            // Twitter/X options
            'twitter_reply_restriction' => $postOptions['twitter']['replyRestriction'] ?? 'everyone',
            'twitter_thread_tweets' => $postOptions['twitter']['threadTweets'] ?? [],
            'twitter_alt_text' => $postOptions['twitter']['altText'] ?? null,

            // Product details (DM-based)
            'product_enabled' => $postOptions['product']['enabled'] ?? false,
            'product_title' => $postOptions['product']['title'] ?? null,
            'product_price' => $postOptions['product']['price'] ?? null,
            'product_currency' => $postOptions['product']['currency'] ?? 'SAR',
            'product_description' => $postOptions['product']['description'] ?? null,
            'product_order_message' => $postOptions['product']['orderMessage'] ?? null,
        ];
    }
}
