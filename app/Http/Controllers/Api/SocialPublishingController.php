<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Social\SchedulingService;
use App\Services\Social\PublishingService;
use App\Services\Social\ContentCalendarService;
use App\Models\Social\ScheduledPost;
use App\Models\Social\ContentLibrary;
use App\Models\Social\BestTimeRecommendation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Http\Controllers\Concerns\ApiResponse;

class SocialPublishingController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SchedulingService $schedulingService,
        protected PublishingService $publishingService,
        protected ContentCalendarService $calendarService
    ) {}

    // ===== Scheduled Posts =====

    /**
     * List scheduled posts.
     */
    public function index(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $query = ScheduledPost::where('org_id', $orgId)
            ->with(['creator', 'platformPosts']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('platform')) {
            $query->whereJsonContains('platforms', $request->platform);
        }

        $posts = $query->orderByDesc('scheduled_at')->get();

        return $this->success(['posts' => $posts], 'Scheduled posts retrieved successfully');
    }

    /**
     * Create and schedule a post.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'required|in:facebook,instagram,twitter,linkedin,tiktok,youtube,snapchat',
            'post_type' => 'required|in:text,image,video,link,carousel,story,reel',
            'scheduled_at' => 'nullable|date|after:now',
            'media_urls' => 'nullable|array',
            'hashtags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $post = $this->schedulingService->schedulePost(
                $request->user()->org_id,
                $request->user()->user_id,
                $request->all()
            );

            return $this->created(
                ['post' => $post->load(['platformPosts', 'queueItems'])],
                'Post scheduled successfully'
            );

        } catch (\Exception $e) {
            return $this->serverError('Failed to schedule post: ' . $e->getMessage());
        }
    }

    /**
     * Get post details.
     */
    public function show(Request $request, string $postId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        try {
            $post = ScheduledPost::where('org_id', $orgId)
                ->where('post_id', $postId)
                ->with(['creator', 'platformPosts', 'queueItems', 'contentLibrary'])
                ->firstOrFail();

            return $this->success(['post' => $post], 'Post retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Post not found');
        }
    }

    /**
     * Update scheduled post.
     */
    public function update(Request $request, string $postId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        try {
            $post = ScheduledPost::where('org_id', $orgId)
                ->where('post_id', $postId)
                ->firstOrFail();

            if ($post->isPublished()) {
                return $this->error('Cannot update published post', 422);
            }

            $post->update($request->all());

            return $this->success(['post' => $post], 'Post updated successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Post not found');
        }
    }

    /**
     * Reschedule post.
     */
    public function reschedule(Request $request, string $postId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'scheduled_at' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $orgId = $request->user()->org_id;

        try {
            $post = ScheduledPost::where('org_id', $orgId)
                ->where('post_id', $postId)
                ->firstOrFail();

            $this->schedulingService->reschedulePost($post, $request->scheduled_at);

            return $this->success(
                ['post' => $post->fresh()],
                'Post rescheduled successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Post not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to reschedule post: ' . $e->getMessage());
        }
    }

    /**
     * Cancel scheduled post.
     */
    public function cancel(Request $request, string $postId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        try {
            $post = ScheduledPost::where('org_id', $orgId)
                ->where('post_id', $postId)
                ->firstOrFail();

            $this->schedulingService->cancelPost($post);

            return $this->deleted('Post cancelled successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Post not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to cancel post: ' . $e->getMessage());
        }
    }

    /**
     * Publish post immediately.
     */
    public function publish(Request $request, string $postId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        try {
            $post = ScheduledPost::where('org_id', $orgId)
                ->where('post_id', $postId)
                ->firstOrFail();

            $results = $this->publishingService->publishPost($post);

            return $this->success(
                ['results' => $results],
                'Post published successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Post not found');
        } catch (\Exception $e) {
            return $this->serverError('Failed to publish post: ' . $e->getMessage());
        }
    }

    // ===== Approval Workflow =====

    /**
     * Approve post.
     */
    public function approve(Request $request, string $postId): JsonResponse
    {
        $orgId = $request->user()->org_id;
        $userId = $request->user()->user_id;

        try {
            $post = ScheduledPost::where('org_id', $orgId)
                ->where('post_id', $postId)
                ->firstOrFail();

            $post->approve($userId);

            return $this->success(
                ['post' => $post],
                'Post approved successfully'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Post not found');
        }
    }

    /**
     * Reject post.
     */
    public function reject(Request $request, string $postId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        try {
            $post = ScheduledPost::where('org_id', $orgId)
                ->where('post_id', $postId)
                ->firstOrFail();

            $post->reject();

            return $this->success(null, 'Post rejected successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Post not found');
        }
    }

    // ===== Content Calendar =====

    /**
     * Get calendar view.
     */
    public function getCalendar(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()));
        $endDate = Carbon::parse($request->input('end_date', now()->endOfMonth()));

        $calendar = $this->calendarService->getCalendar($orgId, $startDate, $endDate);

        return $this->success(['calendar' => $calendar,], 'Operation completed successfully');
    }

    /**
     * Get monthly overview.
     */
    public function getMonthlyOverview(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $overview = $this->calendarService->getMonthlyOverview($orgId, $year, $month);

        return $this->success(['overview' => $overview,], 'Operation completed successfully');
    }

    // ===== Content Library =====

    /**
     * Get content library.
     */
    public function getContentLibrary(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $query = ContentLibrary::where('org_id', $orgId);

        if ($request->has('content_type')) {
            $query->where('content_type', $request->content_type);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $content = $query->orderByDesc('created_at')->get();

        return $this->success(['content' => $content,], 'Operation completed successfully');
    }

    /**
     * Add to content library.
     */
    public function addToLibrary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content_type' => 'required|in:text,image,video,template,hashtag_set',
            'content' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $contentItem = ContentLibrary::create(array_merge($request->all(), [
            'org_id' => $request->user()->org_id,
            'created_by' => $request->user()->user_id,
        ]));

        return $this->created(
            ['content' => $contentItem],
            'Content added to library successfully'
        );
    }

    // ===== Best Time Recommendations =====

    /**
     * Get best times to post.
     */
    public function getBestTimes(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;
        $platform = $request->input('platform', 'facebook');

        $bestTimes = BestTimeRecommendation::where('org_id', $orgId)
            ->where('platform', $platform)
            ->topTimes(10)
            ->get();

        return $this->success(['best_times' => $bestTimes,], 'Operation completed successfully');
    }

    /**
     * Suggest posting time.
     */
    public function suggestTime(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;
        $platform = $request->input('platform', 'facebook');
        $preferredDate = $request->has('preferred_date')
            ? Carbon::parse($request->preferred_date)
            : null;

        $suggestedTime = $this->schedulingService->suggestPostingTime($orgId, $platform, $preferredDate);

        return $this->success(['suggested_time' => $suggestedTime->toISOString(),], 'Operation completed successfully');
    }

    // ===== Analytics & Stats =====

    /**
     * Get publishing stats.
     */
    public function getStats(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $stats = $this->calendarService->getSummary($orgId);

        return $this->success(['stats' => $stats,], 'Operation completed successfully');
    }
}
