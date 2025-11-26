<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Social\SocialPost;
use App\Models\Platform\PlatformConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SocialPostController extends Controller
{
    use ApiResponse;

    /**
     * Get connected social accounts (Facebook Pages & Instagram) with real names.
     * Only returns accounts that are selected in the platform connection's selected_assets.
     */
    public function getConnectedAccounts(Request $request, string $org)
    {
        try {
            // Set RLS context
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$org]);

            $connection = PlatformConnection::where('org_id', $org)
                ->where('platform', 'meta')
                ->where('status', 'active')
                ->first();

            if (!$connection) {
                return $this->success([
                    'accounts' => [],
                    'message' => 'No Meta connection found',
                ], 'No connected accounts');
            }

            $accessToken = $connection->access_token;
            $metadata = $connection->account_metadata ?? [];
            $selectedAssets = $metadata['selected_assets'] ?? [];
            $accounts = [];

            // Get the linked integration_id for FK references
            $integration = DB::table('cmis.integrations')
                ->where('platform_connection_id', $connection->connection_id)
                ->first();
            $integrationId = $integration?->integration_id;

            // Get selected page IDs and Instagram account IDs
            $selectedPageIds = $selectedAssets['pages'] ?? [];
            $selectedInstagramIds = $selectedAssets['instagram_accounts'] ?? [];

            // If no assets are selected, show a message
            if (empty($selectedPageIds) && empty($selectedInstagramIds)) {
                return $this->success([
                    'accounts' => [],
                    'message' => 'No assets selected. Please configure your Meta connection assets.',
                    'assets_url' => route('orgs.settings.platform-connections.meta.assets', [$org, $connection->connection_id]),
                ], 'No assets selected');
            }

            // Fetch selected Facebook Pages with real names
            foreach ($selectedPageIds as $pageId) {
                $pageResponse = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$pageId}", [
                    'access_token' => $accessToken,
                    'fields' => 'id,name,picture{url},category,instagram_business_account',
                ]);

                if ($pageResponse->successful()) {
                    $page = $pageResponse->json();
                    $accounts[] = [
                        'id' => 'facebook_' . $page['id'],
                        'type' => 'facebook',
                        'platformId' => $page['id'],
                        'name' => $page['name'] ?? 'Facebook Page',
                        'picture' => $page['picture']['data']['url'] ?? null,
                        'category' => $page['category'] ?? null,
                        'connectionId' => $connection->connection_id,
                        'integrationId' => $integrationId,
                    ];
                } else {
                    Log::warning('Failed to fetch page details', [
                        'page_id' => $pageId,
                        'error' => $pageResponse->json('error', []),
                    ]);
                }
            }

            // Fetch selected Instagram accounts with real names
            foreach ($selectedInstagramIds as $igId) {
                $igResponse = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$igId}", [
                    'access_token' => $accessToken,
                    'fields' => 'id,username,name,profile_picture_url,followers_count',
                ]);

                if ($igResponse->successful()) {
                    $igData = $igResponse->json();

                    // Find connected page if any
                    $connectedPage = null;
                    foreach ($selectedPageIds as $pageId) {
                        $pageCheck = Http::timeout(10)->get("https://graph.facebook.com/v21.0/{$pageId}", [
                            'access_token' => $accessToken,
                            'fields' => 'id,name,instagram_business_account',
                        ]);
                        if ($pageCheck->successful()) {
                            $pageData = $pageCheck->json();
                            if (($pageData['instagram_business_account']['id'] ?? null) === $igId) {
                                $connectedPage = $pageData;
                                break;
                            }
                        }
                    }

                    $accounts[] = [
                        'id' => 'instagram_' . $igId,
                        'type' => 'instagram',
                        'platformId' => $igId,
                        'name' => '@' . ($igData['username'] ?? 'instagram'),
                        'username' => $igData['username'] ?? null,
                        'picture' => $igData['profile_picture_url'] ?? null,
                        'followers' => $igData['followers_count'] ?? 0,
                        'connectedPageId' => $connectedPage['id'] ?? null,
                        'connectedPageName' => $connectedPage['name'] ?? null,
                        'connectionId' => $connection->connection_id,
                        'integrationId' => $integrationId,
                    ];
                } else {
                    Log::warning('Failed to fetch Instagram account details', [
                        'instagram_id' => $igId,
                        'error' => $igResponse->json('error', []),
                    ]);
                }
            }

            return $this->success([
                'accounts' => $accounts,
                'connection_id' => $connection->connection_id,
                'integration_id' => $integrationId,
                'selected_assets' => $selectedAssets,
            ], 'Connected accounts retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to fetch connected accounts', [
                'org_id' => $org,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Failed to fetch accounts: ' . $e->getMessage(), 500);
        }
    }

    /**
     * List all social posts for the organization.
     */
    public function index(Request $request, string $org)
    {
        try {
            // Set RLS context
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$org]);

            $query = DB::table('cmis.social_posts')
                ->where('org_id', $org)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc');

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by platform
            if ($request->has('platform') && $request->platform !== 'all') {
                $query->where('platform', $request->platform);
            }

            $posts = $query->paginate($request->get('per_page', 20));

            // Transform the data
            $posts->getCollection()->transform(function ($post) {
                $post->media = json_decode($post->media ?? '[]', true);
                $post->metadata = json_decode($post->metadata ?? '{}', true);
                $post->post_id = $post->id; // For frontend compatibility
                $post->post_text = $post->content; // For frontend compatibility
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
     * Create a new social post.
     */
    public function store(Request $request, string $org)
    {
        $request->validate([
            'content' => 'required|string|max:5000',
            'platforms' => 'required|string', // JSON string of platforms
            'publish_type' => 'required|in:now,scheduled,draft,queue',
            'scheduled_at' => 'required_if:publish_type,scheduled|nullable|date',
            'post_type' => 'nullable|string|in:feed,reel,story,carousel,tweet,thread,post,article',
            'media.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,mp4,mov|max:51200',
        ]);

        $platforms = json_decode($request->platforms, true);

        if (empty($platforms)) {
            return $this->error('At least one platform must be selected', 400);
        }

        // Check if Instagram is selected but no media provided
        $hasInstagram = collect($platforms)->contains(fn($p) => ($p['type'] ?? '') === 'instagram');
        $hasMedia = $request->hasFile('media');

        if ($hasInstagram && !$hasMedia) {
            return $this->error('Instagram posts require at least one image or video', 400);
        }

        try {
            // Set RLS context
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$org]);

            $createdPosts = [];
            $publishResults = [];

            // Handle media uploads
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

            // Get the connection to use for publishing
            $connection = PlatformConnection::where('org_id', $org)
                ->where('platform', 'meta')
                ->where('status', 'active')
                ->first();

            // Get the linked integration_id from integrations table
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

                // Determine initial status
                $status = match($request->publish_type) {
                    'now' => 'publishing',
                    'scheduled' => 'scheduled',
                    'queue' => 'scheduled', // Queue posts are scheduled
                    'draft' => 'draft',
                };

                // Get queue slot if publish_type is queue
                $scheduledAt = null;
                if ($request->publish_type === 'scheduled') {
                    $scheduledAt = $request->scheduled_at;
                } elseif ($request->publish_type === 'queue' && $integrationIdForPost) {
                    // Get next available queue slot
                    $queueSettings = DB::table('cmis.integration_queue_settings')
                        ->where('org_id', $org)
                        ->where('integration_id', $integrationIdForPost)
                        ->whereNull('deleted_at')
                        ->first();

                    if ($queueSettings && $queueSettings->queue_enabled) {
                        $postingTimes = json_decode($queueSettings->posting_times ?? '[]', true);
                        $daysEnabled = json_decode($queueSettings->days_enabled ?? '[1,2,3,4,5]', true);

                        if (!empty($postingTimes)) {
                            $now = now();
                            $currentTime = $now->format('H:i');
                            $currentDay = $now->dayOfWeek;

                            // Try to find a slot today
                            $slotFound = false;
                            foreach ($postingTimes as $time) {
                                if ($time > $currentTime && in_array($currentDay, $daysEnabled)) {
                                    $scheduledAt = $now->format('Y-m-d') . ' ' . $time;
                                    $slotFound = true;
                                    break;
                                }
                            }

                            // If no slot today, find next available day
                            if (!$slotFound) {
                                for ($i = 1; $i <= 7; $i++) {
                                    $nextDay = $now->copy()->addDays($i);
                                    $nextDayOfWeek = $nextDay->dayOfWeek;

                                    if (in_array($nextDayOfWeek, $daysEnabled)) {
                                        $scheduledAt = $nextDay->format('Y-m-d') . ' ' . $postingTimes[0];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }

                $postId = Str::uuid()->toString();

                // Determine post content type
                $contentType = $request->post_type ?? (!empty($mediaUrls) ? 'feed' : 'text');

                // Insert directly using DB query to avoid model issues
                DB::table('cmis.social_posts')->insert([
                    'id' => $postId,
                    'org_id' => $org,
                    'integration_id' => $integrationIdForPost, // Use correct integration_id from integrations table
                    'platform' => $platformType,
                    'account_id' => $accountId,
                    'account_username' => $accountName,
                    'content' => $request->content,
                    'media' => json_encode($mediaUrls),
                    'post_type' => $contentType,
                    'status' => $status,
                    'scheduled_at' => $scheduledAt,
                    'created_by' => auth()->id(),
                    'metadata' => json_encode([
                        'platform_details' => $platform,
                        'publish_type' => $request->publish_type,
                        'connection_id' => $connection?->connection_id, // Store for reference when publishing
                        'is_queued' => $request->publish_type === 'queue',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $createdPosts[] = [
                    'id' => $postId,
                    'platform' => $platformType,
                    'account_name' => $accountName,
                    'status' => $status,
                ];

                // If publishing immediately, attempt to publish now
                if ($request->publish_type === 'now' && $connection) {
                    $result = $this->publishToMeta($postId, $platformType, $accountId, $request->content, $mediaUrls, $connection);
                    $publishResults[] = [
                        'platform' => $platformType,
                        'account_id' => $accountId,
                        'success' => $result['success'],
                        'message' => $result['message'] ?? null,
                        'external_post_id' => $result['post_id'] ?? null,
                    ];

                    if ($result['success']) {
                        DB::table('cmis.social_posts')
                            ->where('id', $postId)
                            ->update([
                                'status' => 'published',
                                'published_at' => now(),
                                'post_external_id' => $result['post_id'] ?? null,
                                'permalink' => $result['permalink'] ?? null,
                                'updated_at' => now(),
                            ]);
                    } else {
                        DB::table('cmis.social_posts')
                            ->where('id', $postId)
                            ->update([
                                'status' => 'failed',
                                'failed_at' => now(),
                                'error_message' => $result['message'] ?? 'Unknown error',
                                'updated_at' => now(),
                            ]);
                    }
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
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error('Failed to create post: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Show a specific post.
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
     * Update a post.
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

        // Can only update draft, scheduled, or failed posts
        if (!in_array($socialPost->status, ['draft', 'scheduled', 'failed'])) {
            return $this->error('Cannot update a published or publishing post', 400);
        }

        $request->validate([
            'content' => 'sometimes|string|max:5000',
            'scheduled_at' => 'sometimes|nullable|date',
            'status' => 'sometimes|in:draft,scheduled',
        ]);

        $updateData = ['updated_at' => now()];

        if ($request->has('content')) {
            $updateData['content'] = $request->content;
        }

        if ($request->has('scheduled_at') && $request->scheduled_at) {
            $updateData['scheduled_at'] = $request->scheduled_at;
            $updateData['status'] = 'scheduled';
        }

        // Allow changing status from failed back to draft
        if ($request->has('status') && in_array($request->status, ['draft', 'scheduled'])) {
            $updateData['status'] = $request->status;
            // Clear error if moving from failed
            if ($socialPost->status === 'failed') {
                $updateData['error_message'] = null;
                $updateData['failed_at'] = null;
            }
        }

        DB::table('cmis.social_posts')
            ->where('id', $post)
            ->update($updateData);

        $updatedPost = DB::table('cmis.social_posts')
            ->where('id', $post)
            ->first();

        return $this->success([
            'post' => $updatedPost
        ], 'Post updated successfully');
    }

    /**
     * Delete a post.
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

        // Soft delete
        DB::table('cmis.social_posts')
            ->where('id', $post)
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        return $this->deleted('Post deleted successfully');
    }

    /**
     * Delete all failed posts for an organization.
     */
    public function destroyAllFailed(Request $request, string $org)
    {
        DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$org]);

        // Count failed posts before deletion
        $failedCount = DB::table('cmis.social_posts')
            ->where('org_id', $org)
            ->where('status', 'failed')
            ->whereNull('deleted_at')
            ->count();

        if ($failedCount === 0) {
            return $this->success(['deleted_count' => 0], 'No failed posts to delete');
        }

        // Soft delete all failed posts
        $deletedCount = DB::table('cmis.social_posts')
            ->where('org_id', $org)
            ->where('status', 'failed')
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        Log::info('Deleted all failed posts', [
            'org_id' => $org,
            'deleted_count' => $deletedCount,
        ]);

        return $this->success(
            ['deleted_count' => $deletedCount],
            "Successfully deleted {$deletedCount} failed posts"
        );
    }

    /**
     * Publish a scheduled or draft post immediately.
     */
    public function publish(Request $request, string $org, string $post)
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

        // Can only publish draft or scheduled posts
        if (!in_array($socialPost->status, ['draft', 'scheduled'])) {
            return $this->error('This post cannot be published', 400);
        }

        $connection = PlatformConnection::where('org_id', $org)
            ->where('platform', 'meta')
            ->where('status', 'active')
            ->first();

        if (!$connection) {
            return $this->error('No active Meta connection found', 400);
        }

        // Update status to publishing
        DB::table('cmis.social_posts')
            ->where('id', $post)
            ->update(['status' => 'publishing', 'updated_at' => now()]);

        $mediaUrls = json_decode($socialPost->media ?? '[]', true);

        $result = $this->publishToMeta(
            $post,
            $socialPost->platform,
            $socialPost->account_id,
            $socialPost->content,
            $mediaUrls,
            $connection
        );

        if ($result['success']) {
            DB::table('cmis.social_posts')
                ->where('id', $post)
                ->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'post_external_id' => $result['post_id'] ?? null,
                    'permalink' => $result['permalink'] ?? null,
                    'updated_at' => now(),
                ]);

            return $this->success([
                'post_id' => $post,
                'external_post_id' => $result['post_id'] ?? null,
                'permalink' => $result['permalink'] ?? null,
            ], 'Post published successfully');
        } else {
            DB::table('cmis.social_posts')
                ->where('id', $post)
                ->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'error_message' => $result['message'] ?? 'Unknown error',
                    'retry_count' => DB::raw('COALESCE(retry_count, 0) + 1'),
                    'updated_at' => now(),
                ]);

            return $this->error('Failed to publish: ' . ($result['message'] ?? 'Unknown error'), 400);
        }
    }

    /**
     * Publish content to Meta (Facebook/Instagram).
     * Uses the selected_assets from the platform connection.
     */
    private function publishToMeta(string $postId, string $platform, ?string $accountId, string $content, array $mediaUrls, PlatformConnection $connection): array
    {
        try {
            $accessToken = $connection->access_token;
            $metadata = $connection->account_metadata ?? [];
            $selectedAssets = $metadata['selected_assets'] ?? [];

            // Get selected page IDs and Instagram account IDs
            $selectedPageIds = $selectedAssets['pages'] ?? [];
            $selectedInstagramIds = $selectedAssets['instagram_accounts'] ?? [];

            // Determine the page/account to use
            $pageId = null;
            $instagramAccountId = null;

            if ($platform === 'facebook') {
                // Use provided accountId if it's in selected assets, otherwise use first selected page
                if ($accountId && in_array($accountId, $selectedPageIds)) {
                    $pageId = $accountId;
                } elseif (!empty($selectedPageIds)) {
                    $pageId = $selectedPageIds[0];
                }
            } elseif ($platform === 'instagram') {
                // Use provided accountId if it's in selected assets, otherwise use first selected instagram
                if ($accountId && in_array($accountId, $selectedInstagramIds)) {
                    $instagramAccountId = $accountId;
                } elseif (!empty($selectedInstagramIds)) {
                    $instagramAccountId = $selectedInstagramIds[0];
                }

                // Get connected page for Instagram publishing (required for API)
                if ($instagramAccountId && !empty($selectedPageIds)) {
                    $pageId = $selectedPageIds[0]; // Use first selected page
                }
            }

            Log::info('Publishing to Meta', [
                'post_id' => $postId,
                'platform' => $platform,
                'page_id' => $pageId,
                'instagram_id' => $instagramAccountId,
                'selected_pages' => $selectedPageIds,
                'selected_instagram' => $selectedInstagramIds,
            ]);

            if ($platform === 'facebook' && $pageId) {
                return $this->publishToFacebook($content, $mediaUrls, $pageId, $accessToken);
            } elseif ($platform === 'instagram' && $instagramAccountId) {
                return $this->publishToInstagram($content, $mediaUrls, $instagramAccountId, $pageId, $accessToken);
            } else {
                return [
                    'success' => false,
                    'message' => "No {$platform} account selected. Please configure your Meta connection assets at Settings > Platform Connections > Meta > Assets.",
                ];
            }
        } catch (\Exception $e) {
            Log::error('Meta publishing error', [
                'post_id' => $postId,
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Publish to Facebook Page.
     */
    private function publishToFacebook(string $content, array $mediaUrls, string $pageId, string $accessToken): array
    {
        try {
            Log::info('Publishing to Facebook', [
                'page_id' => $pageId,
                'has_media' => !empty($mediaUrls),
                'content_length' => strlen($content),
            ]);

            // First, get page access token
            $pageTokenResponse = Http::timeout(30)->get("https://graph.facebook.com/v21.0/{$pageId}", [
                'access_token' => $accessToken,
                'fields' => 'access_token,name',
            ]);

            if (!$pageTokenResponse->successful()) {
                $error = $pageTokenResponse->json('error.message', 'Failed to get page token');
                Log::error('Failed to get page token', [
                    'page_id' => $pageId,
                    'response' => $pageTokenResponse->json(),
                ]);
                return ['success' => false, 'message' => $error];
            }

            $pageToken = $pageTokenResponse->json('access_token');
            $pageName = $pageTokenResponse->json('name');

            Log::info('Got page token', ['page_name' => $pageName]);

            // Publish based on content type
            if (!empty($mediaUrls)) {
                $firstMedia = $mediaUrls[0];

                if ($firstMedia['type'] === 'video') {
                    Log::info('Publishing video to Facebook');
                    $response = Http::timeout(120)->post("https://graph.facebook.com/v21.0/{$pageId}/videos", [
                        'access_token' => $pageToken,
                        'file_url' => $firstMedia['url'],
                        'description' => $content,
                    ]);
                } else {
                    if (count($mediaUrls) === 1) {
                        Log::info('Publishing single photo to Facebook', ['url' => $firstMedia['url']]);
                        $response = Http::timeout(60)->post("https://graph.facebook.com/v21.0/{$pageId}/photos", [
                            'access_token' => $pageToken,
                            'url' => $firstMedia['url'],
                            'message' => $content,
                        ]);
                    } else {
                        // Multiple photos
                        Log::info('Publishing multiple photos to Facebook', ['count' => count($mediaUrls)]);
                        $photoIds = [];
                        foreach ($mediaUrls as $media) {
                            if ($media['type'] === 'image') {
                                $photoResponse = Http::timeout(60)->post("https://graph.facebook.com/v21.0/{$pageId}/photos", [
                                    'access_token' => $pageToken,
                                    'url' => $media['url'],
                                    'published' => false,
                                ]);
                                if ($photoResponse->successful()) {
                                    $photoIds[] = ['media_fbid' => $photoResponse->json('id')];
                                }
                            }
                        }

                        $response = Http::timeout(60)->post("https://graph.facebook.com/v21.0/{$pageId}/feed", [
                            'access_token' => $pageToken,
                            'message' => $content,
                            'attached_media' => json_encode($photoIds),
                        ]);
                    }
                }
            } else {
                // Text-only post to feed
                Log::info('Publishing text-only post to Facebook feed');
                $response = Http::timeout(30)
                    ->asForm()
                    ->post("https://graph.facebook.com/v21.0/{$pageId}/feed", [
                        'access_token' => $pageToken,
                        'message' => $content,
                    ]);
            }

            Log::info('Facebook API response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if ($response->successful()) {
                $postId = $response->json('id') ?? $response->json('post_id');
                return [
                    'success' => true,
                    'post_id' => $postId,
                    'permalink' => "https://facebook.com/{$postId}",
                    'message' => 'Published to Facebook successfully',
                ];
            } else {
                $error = $response->json('error.message', 'Unknown error');
                $errorCode = $response->json('error.code');
                Log::error('Facebook publish failed', [
                    'error' => $error,
                    'error_code' => $errorCode,
                    'response' => $response->json(),
                ]);
                return ['success' => false, 'message' => $error];
            }
        } catch (\Exception $e) {
            Log::error('Facebook publish exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Publish to Instagram.
     */
    private function publishToInstagram(string $content, array $mediaUrls, string $instagramAccountId, ?string $pageId, string $accessToken): array
    {
        try {
            // Instagram requires media for posts
            if (empty($mediaUrls)) {
                return [
                    'success' => false,
                    'message' => 'Instagram posts require at least one image or video',
                ];
            }

            $firstMedia = $mediaUrls[0];

            // Step 1: Create media container
            $containerData = [
                'access_token' => $accessToken,
                'caption' => $content,
            ];

            if ($firstMedia['type'] === 'video') {
                $containerData['media_type'] = 'VIDEO';
                $containerData['video_url'] = $firstMedia['url'];
            } else {
                if (count($mediaUrls) === 1) {
                    $containerData['image_url'] = $firstMedia['url'];
                } else {
                    // Carousel post
                    $containerData['media_type'] = 'CAROUSEL';
                    $children = [];

                    foreach ($mediaUrls as $media) {
                        $childData = [
                            'access_token' => $accessToken,
                            'is_carousel_item' => true,
                        ];

                        if ($media['type'] === 'video') {
                            $childData['media_type'] = 'VIDEO';
                            $childData['video_url'] = $media['url'];
                        } else {
                            $childData['image_url'] = $media['url'];
                        }

                        $childResponse = Http::timeout(60)->post("https://graph.facebook.com/v21.0/{$instagramAccountId}/media", $childData);

                        if ($childResponse->successful()) {
                            $children[] = $childResponse->json('id');
                        }
                    }

                    $containerData['children'] = implode(',', $children);
                }
            }

            $containerResponse = Http::timeout(60)->post("https://graph.facebook.com/v21.0/{$instagramAccountId}/media", $containerData);

            if (!$containerResponse->successful()) {
                $error = $containerResponse->json('error.message', 'Failed to create media container');
                return ['success' => false, 'message' => $error];
            }

            $containerId = $containerResponse->json('id');

            // Step 2: Wait for container to be ready (for videos)
            if ($firstMedia['type'] === 'video') {
                $maxAttempts = 30;
                $attempt = 0;

                while ($attempt < $maxAttempts) {
                    $statusResponse = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$containerId}", [
                        'access_token' => $accessToken,
                        'fields' => 'status_code',
                    ]);

                    $status = $statusResponse->json('status_code');

                    if ($status === 'FINISHED') {
                        break;
                    } elseif ($status === 'ERROR') {
                        return ['success' => false, 'message' => 'Video processing failed'];
                    }

                    sleep(2);
                    $attempt++;
                }
            }

            // Step 3: Publish the container
            $publishResponse = Http::timeout(60)->post("https://graph.facebook.com/v21.0/{$instagramAccountId}/media_publish", [
                'access_token' => $accessToken,
                'creation_id' => $containerId,
            ]);

            if ($publishResponse->successful()) {
                $postId = $publishResponse->json('id');

                // Get permalink
                $permalinkResponse = Http::timeout(15)->get("https://graph.facebook.com/v21.0/{$postId}", [
                    'access_token' => $accessToken,
                    'fields' => 'permalink',
                ]);

                $permalink = $permalinkResponse->json('permalink', "https://instagram.com/p/{$postId}");

                return [
                    'success' => true,
                    'post_id' => $postId,
                    'permalink' => $permalink,
                    'message' => 'Published to Instagram successfully',
                ];
            } else {
                $error = $publishResponse->json('error.message', 'Unknown error');
                return ['success' => false, 'message' => $error];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get queue settings for all connected integrations.
     */
    public function getQueueSettings(Request $request, string $org)
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$org]);

            // Get all connected integrations
            $integrations = DB::table('cmis.integrations')
                ->where('org_id', $org)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->get();

            $settings = [];

            foreach ($integrations as $integration) {
                // Check if queue settings exist
                $queueSettings = DB::table('cmis.integration_queue_settings')
                    ->where('org_id', $org)
                    ->where('integration_id', $integration->integration_id)
                    ->whereNull('deleted_at')
                    ->first();

                if (!$queueSettings) {
                    // Create default settings
                    $defaults = \App\Models\Social\IntegrationQueueSettings::getDefaultSettings($integration->platform);

                    $queueSettings = (object)[
                        'integration_id' => $integration->integration_id,
                        'queue_enabled' => false,
                        'posting_times' => $defaults['posting_times'],
                        'days_enabled' => [1, 2, 3, 4, 5], // Weekdays by default
                        'posts_per_day' => $defaults['posts_per_day'],
                    ];
                }

                $settings[] = [
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

            return $this->success($settings, 'Queue settings retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to fetch queue settings', [
                'org_id' => $org,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Failed to fetch queue settings: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Save queue settings for an integration.
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
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$org]);

            // Check if settings exist
            $exists = DB::table('cmis.integration_queue_settings')
                ->where('org_id', $org)
                ->where('integration_id', $request->integration_id)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                // Update existing settings
                DB::table('cmis.integration_queue_settings')
                    ->where('org_id', $org)
                    ->where('integration_id', $request->integration_id)
                    ->update([
                        'queue_enabled' => $request->queue_enabled,
                        'posting_times' => json_encode($request->posting_times),
                        'days_enabled' => json_encode($request->days_enabled),
                        'posts_per_day' => $request->posts_per_day,
                        'updated_at' => now(),
                    ]);
            } else {
                // Create new settings
                DB::table('cmis.integration_queue_settings')->insert([
                    'id' => Str::uuid()->toString(),
                    'org_id' => $org,
                    'integration_id' => $request->integration_id,
                    'queue_enabled' => $request->queue_enabled,
                    'posting_times' => json_encode($request->posting_times),
                    'days_enabled' => json_encode($request->days_enabled),
                    'posts_per_day' => $request->posts_per_day,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $this->success(null, 'Queue settings saved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to save queue settings', [
                'org_id' => $org,
                'integration_id' => $request->integration_id,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Failed to save queue settings: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get the next available time slot for queue scheduling.
     */
    public function getNextQueueSlot(Request $request, string $org, string $integrationId)
    {
        try {
            DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$org]);

            $queueSettings = DB::table('cmis.integration_queue_settings')
                ->where('org_id', $org)
                ->where('integration_id', $integrationId)
                ->whereNull('deleted_at')
                ->first();

            if (!$queueSettings || !$queueSettings->queue_enabled) {
                return $this->error('Queue is not enabled for this integration', 400);
            }

            $postingTimes = json_decode($queueSettings->posting_times ?? '[]', true);
            $daysEnabled = json_decode($queueSettings->days_enabled ?? '[1,2,3,4,5]', true);

            if (empty($postingTimes)) {
                return $this->error('No posting times configured', 400);
            }

            $now = now();
            $currentTime = $now->format('H:i');
            $currentDay = $now->dayOfWeek;

            // Try to find a slot today
            foreach ($postingTimes as $time) {
                if ($time > $currentTime && in_array($currentDay, $daysEnabled)) {
                    $nextSlot = $now->format('Y-m-d') . ' ' . $time;
                    return $this->success(['next_slot' => $nextSlot], 'Next queue slot found');
                }
            }

            // Find next available day
            for ($i = 1; $i <= 7; $i++) {
                $nextDay = $now->copy()->addDays($i);
                $nextDayOfWeek = $nextDay->dayOfWeek;

                if (in_array($nextDayOfWeek, $daysEnabled)) {
                    $nextSlot = $nextDay->format('Y-m-d') . ' ' . $postingTimes[0];
                    return $this->success(['next_slot' => $nextSlot], 'Next queue slot found');
                }
            }

            return $this->error('No available queue slots found', 400);

        } catch (\Exception $e) {
            Log::error('Failed to get next queue slot', [
                'org_id' => $org,
                'integration_id' => $integrationId,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Failed to get next queue slot: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get available post types for a platform.
     */
    public function getPostTypes(Request $request, string $org)
    {
        $postTypes = [
            'facebook' => [
                ['value' => 'feed', 'label' => 'منشور عادي (Feed Post)', 'icon' => 'fa-newspaper'],
                ['value' => 'reel', 'label' => 'ريل (Reel)', 'icon' => 'fa-video'],
                ['value' => 'story', 'label' => 'قصة (Story)', 'icon' => 'fa-circle'],
            ],
            'instagram' => [
                ['value' => 'feed', 'label' => 'منشور عادي (Feed Post)', 'icon' => 'fa-image'],
                ['value' => 'reel', 'label' => 'ريل (Reel)', 'icon' => 'fa-video'],
                ['value' => 'story', 'label' => 'قصة (Story)', 'icon' => 'fa-circle'],
                ['value' => 'carousel', 'label' => 'كاروسيل (Carousel)', 'icon' => 'fa-images'],
            ],
            'twitter' => [
                ['value' => 'tweet', 'label' => 'تغريدة (Tweet)', 'icon' => 'fa-comment'],
                ['value' => 'thread', 'label' => 'سلسلة (Thread)', 'icon' => 'fa-list'],
            ],
            'linkedin' => [
                ['value' => 'post', 'label' => 'منشور (Post)', 'icon' => 'fa-file-alt'],
                ['value' => 'article', 'label' => 'مقال (Article)', 'icon' => 'fa-newspaper'],
            ],
        ];

        return $this->success($postTypes, 'Post types retrieved successfully');
    }
}
