<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\Social\ScheduledSocialPost;
use App\Models\Social\SocialAccount;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Concerns\ApiResponse;

class SocialSchedulerController extends Controller
{
    use ApiResponse;

    /**
     * Constructor - Apply authentication middleware
     */
    public function __construct()
    {
        // Apply authentication to all social scheduling operations
        // Social posts are critical business content requiring authentication
        $this->middleware('auth:sanctum');
    }

    /**
     * Get dashboard overview with stats and scheduled posts
     */
    public function dashboard(Request $request, string $orgId): JsonResponse
    {
        $this->authorize('viewAnalytics', Channel::class);
        try {
            $stats = [
                'scheduled' => ScheduledSocialPost::forOrg($orgId)
                    ->where('status', ScheduledSocialPost::STATUS_SCHEDULED)
                    ->count(),
                'published_today' => ScheduledSocialPost::forOrg($orgId)
                    ->where('status', ScheduledSocialPost::STATUS_PUBLISHED)
                    ->whereDate('published_at', today())
                    ->count(),
                'drafts' => ScheduledSocialPost::forOrg($orgId)
                    ->where('status', ScheduledSocialPost::STATUS_DRAFT)
                    ->count(),
                'active_platforms' => SocialAccount::where('org_id', $orgId)->count(),
            ];

            $upcomingPosts = ScheduledSocialPost::forOrg($orgId)
                ->scheduled()
                ->with(['user:id,name', 'campaign:campaign_id,name'])
                ->orderBy('scheduled_at', 'asc')
                ->limit(10)
                ->get();

            return $this->success(['stats' => $stats,
                'upcoming' => $upcomingPosts,
            ], 'Operation completed successfully');

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch dashboard data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all scheduled posts
     */
    public function scheduled(Request $request, string $orgId): JsonResponse
    {
        $this->authorize('viewAny', Channel::class);
        try {
            $perPage = $request->input('per_page', 20);

            $posts = ScheduledSocialPost::forOrg($orgId)
                ->scheduled()
                ->with(['user:id,name', 'campaign:campaign_id,name'])
                ->orderBy('scheduled_at', 'asc')
                ->paginate($perPage);

            return $this->success($posts, 'Retrieved successfully');

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch scheduled posts',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all published posts with engagement metrics
     */
    public function published(Request $request, string $orgId): JsonResponse
    {
        $this->authorize('viewAny', Channel::class);
        try {
            $perPage = $request->input('per_page', 20);
            $platform = $request->input('platform');

            $query = ScheduledSocialPost::forOrg($orgId)
                ->published()
                ->with(['user:id,name', 'campaign:campaign_id,name'])
                ->orderBy('published_at', 'desc');

            if ($platform) {
                $query->whereJsonContains('platforms', $platform);
            }

            $posts = $query->paginate($perPage);

            return $this->success($posts, 'Retrieved successfully');

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch published posts',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all draft posts
     */
    public function drafts(Request $request, string $orgId): JsonResponse
    {
        $this->authorize('viewAny', Channel::class);
        try {
            $posts = ScheduledSocialPost::forOrg($orgId)
                ->drafts()
                ->with(['user:id,name', 'campaign:campaign_id,name'])
                ->orderBy('updated_at', 'desc')
                ->get();

            return $this->success($posts, 'Retrieved successfully');

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch draft posts',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Schedule a new post
     */
    public function schedule(Request $request, string $orgId): JsonResponse
    {
        $this->authorize('schedule', Channel::class);
        $validator = Validator::make($request->all(), [
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'required|string|in:facebook,instagram,twitter,linkedin,tiktok',
            'content' => 'required|string|max:5000',
            'media' => 'nullable|array',
            'media.*' => 'nullable|url',
            'scheduled_date' => 'nullable|date|after:now',
            'scheduled_time' => 'nullable|date_format:H:i',
            'campaign_id' => 'nullable|uuid|exists:cmis.campaigns,campaign_id',
            'status' => 'nullable|in:draft,scheduled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Combine scheduled_date and scheduled_time
            $scheduledAt = null;
            if ($request->has('scheduled_date')) {
                $date = $request->input('scheduled_date');
                $time = $request->input('scheduled_time', '00:00');
                $scheduledAt = \Carbon\Carbon::parse("$date $time");
            }

            // Determine status
            $status = $request->input('status', 'scheduled');
            if (!$scheduledAt) {
                $status = ScheduledSocialPost::STATUS_DRAFT;
            }

            $post = ScheduledSocialPost::create([
                'org_id' => $orgId,
                'user_id' => Auth::id(),
                'campaign_id' => $request->input('campaign_id'),
                'platforms' => $request->input('platforms'),
                'content' => $request->input('content'),
                'media' => $request->input('media', []),
                'scheduled_at' => $scheduledAt,
                'status' => $status,
            ]);

            return response()->json([
                'message' => $status === ScheduledSocialPost::STATUS_DRAFT
                    ? 'Post saved as draft'
                    : 'Post scheduled successfully',
                'post' => $post->fresh(['user', 'campaign'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to schedule post',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a scheduled or draft post
     */
    public function update(Request $request, string $orgId, string $postId): JsonResponse
    {
        $this->authorize('update', Channel::class);
        $validator = Validator::make($request->all(), [
            'platforms' => 'sometimes|array|min:1',
            'platforms.*' => 'sometimes|string|in:facebook,instagram,twitter,linkedin,tiktok',
            'content' => 'sometimes|string|max:5000',
            'media' => 'sometimes|array',
            'media.*' => 'nullable|url',
            'scheduled_date' => 'sometimes|nullable|date|after:now',
            'scheduled_time' => 'sometimes|nullable|date_format:H:i',
            'campaign_id' => 'sometimes|nullable|uuid|exists:cmis.campaigns,campaign_id',
            'status' => 'sometimes|in:draft,scheduled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $post = ScheduledSocialPost::forOrg($orgId)->findOrFail($postId);

            // Only allow updating draft and scheduled posts
            if (!in_array($post->status, [ScheduledSocialPost::STATUS_DRAFT, ScheduledSocialPost::STATUS_SCHEDULED])) {
                return response()->json([
                    'error' => 'Cannot update post',
                    'message' => 'Only draft and scheduled posts can be updated'
                ], 400);
            }

            $updateData = $request->only(['platforms', 'content', 'media', 'campaign_id', 'status']);

            // Handle scheduled_at update
            if ($request->has('scheduled_date')) {
                $date = $request->input('scheduled_date');
                $time = $request->input('scheduled_time', $post->scheduled_at?->format('H:i') ?? '00:00');
                $updateData['scheduled_at'] = $date ? \Carbon\Carbon::parse("$date $time") : null;
            }

            $post->update($updateData);

            return response()->json([
                'message' => 'Post updated successfully',
                'post' => $post->fresh(['user', 'campaign'])
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Post not found');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update post',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a post
     */
    public function destroy(Request $request, string $orgId, string $postId): JsonResponse
    {
        $this->authorize('delete', Channel::class);
        try {
            $post = ScheduledSocialPost::forOrg($orgId)->findOrFail($postId);

            // Only allow deleting draft, scheduled, and failed posts
            $allowedStatuses = [
                ScheduledSocialPost::STATUS_DRAFT,
                ScheduledSocialPost::STATUS_SCHEDULED,
                ScheduledSocialPost::STATUS_FAILED
            ];

            if (!in_array($post->status, $allowedStatuses)) {
                return response()->json([
                    'error' => 'Cannot delete post',
                    'message' => 'Only draft, scheduled, and failed posts can be deleted'
                ], 400);
            }

            $post->delete();

            return $this->success(['message' => 'Post deleted successfully'], 'Operation completed successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Post not found');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete post',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Publish a post immediately
     * FIXED: Now uses actual publishing job instead of simulation
     */
    public function publishNow(Request $request, string $orgId, string $postId): JsonResponse
    {
        $this->authorize('publish', Channel::class);
        try {
            $post = ScheduledSocialPost::forOrg($orgId)->findOrFail($postId);

            // Only allow publishing draft and scheduled posts
            if (!in_array($post->status, [ScheduledSocialPost::STATUS_DRAFT, ScheduledSocialPost::STATUS_SCHEDULED])) {
                return response()->json([
                    'error' => 'Cannot publish post',
                    'message' => 'Only draft and scheduled posts can be published'
                ], 400);
            }

            // Validate integration_ids exist
            if (empty($post->integration_ids)) {
                return response()->json([
                    'error' => 'No integrations selected',
                    'message' => 'Please select at least one social media platform'
                ], 400);
            }

            // Mark as publishing
            $post->markAsPublishing();

            // Set scheduled_at to now for immediate publishing
            $post->update(['scheduled_at' => now()]);

            // Dispatch actual publishing job
            \App\Jobs\PublishScheduledSocialPostJob::dispatch($post)
                ->onQueue('social-publishing');

            \Illuminate\Support\Facades\Log::info('Post queued for immediate publishing', [
                'post_id' => $post->id,
                'org_id' => $orgId,
                'integration_ids' => $post->integration_ids,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Post is being published. This may take a few moments.',
                'post' => $post->fresh(['user', 'campaign']),
                'note' => 'You will receive a notification once publishing is complete.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Post not found');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to queue post for publishing', [
                'post_id' => $postId,
                'org_id' => $orgId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to publish post',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reschedule a post
     */
    public function reschedule(Request $request, string $orgId, string $postId): JsonResponse
    {
        $this->authorize('schedule', Channel::class);
        $validator = Validator::make($request->all(), [
            'scheduled_date' => 'required|date|after:now',
            'scheduled_time' => 'required|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $post = ScheduledSocialPost::forOrg($orgId)->findOrFail($postId);

            // Only allow rescheduling scheduled and failed posts
            if (!in_array($post->status, [ScheduledSocialPost::STATUS_SCHEDULED, ScheduledSocialPost::STATUS_FAILED])) {
                return response()->json([
                    'error' => 'Cannot reschedule post',
                    'message' => 'Only scheduled and failed posts can be rescheduled'
                ], 400);
            }

            $date = $request->input('scheduled_date');
            $time = $request->input('scheduled_time');
            $scheduledAt = \Carbon\Carbon::parse("$date $time");

            $post->update([
                'scheduled_at' => $scheduledAt,
                'status' => ScheduledSocialPost::STATUS_SCHEDULED,
                'error_message' => null,
            ]);

            return response()->json([
                'message' => 'Post rescheduled successfully',
                'post' => $post->fresh(['user', 'campaign'])
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Post not found');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to reschedule post',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get post by ID
     */
    public function show(Request $request, string $orgId, string $postId): JsonResponse
    {
        $this->authorize('view', Channel::class);
        try {
            $post = ScheduledSocialPost::forOrg($orgId)
                ->with(['user:id,name', 'campaign:campaign_id,name'])
                ->findOrFail($postId);

            return $this->success(['post' => $post], 'Operation completed successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Post not found');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch post',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
