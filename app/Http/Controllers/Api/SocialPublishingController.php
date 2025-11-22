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

        return response()->json([
            'success' => true,
            'posts' => $posts,
        ]);
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
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $post = $this->schedulingService->schedulePost(
                $request->user()->org_id,
                $request->user()->user_id,
                $request->all()
            );

            return response()->json([
                'success' => true,
                'post' => $post->load(['platformPosts', 'queueItems']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get post details.
     */
    public function show(Request $request, string $postId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $post = ScheduledPost::where('org_id', $orgId)
            ->where('post_id', $postId)
            ->with(['creator', 'platformPosts', 'queueItems', 'contentLibrary'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'post' => $post,
        ]);
    }

    /**
     * Update scheduled post.
     */
    public function update(Request $request, string $postId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $post = ScheduledPost::where('org_id', $orgId)
            ->where('post_id', $postId)
            ->firstOrFail();

        if ($post->isPublished()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update published post',
            ], 422);
        }

        $post->update($request->all());

        return response()->json([
            'success' => true,
            'post' => $post,
        ]);
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
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $orgId = $request->user()->org_id;

        $post = ScheduledPost::where('org_id', $orgId)
            ->where('post_id', $postId)
            ->firstOrFail();

        try {
            $this->schedulingService->reschedulePost($post, $request->scheduled_at);

            return response()->json([
                'success' => true,
                'message' => 'Post rescheduled successfully',
                'post' => $post->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel scheduled post.
     */
    public function cancel(Request $request, string $postId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $post = ScheduledPost::where('org_id', $orgId)
            ->where('post_id', $postId)
            ->firstOrFail();

        try {
            $this->schedulingService->cancelPost($post);

            return response()->json([
                'success' => true,
                'message' => 'Post cancelled successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publish post immediately.
     */
    public function publish(Request $request, string $postId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $post = ScheduledPost::where('org_id', $orgId)
            ->where('post_id', $postId)
            ->firstOrFail();

        try {
            $results = $this->publishingService->publishPost($post);

            return response()->json([
                'success' => true,
                'message' => 'Post published',
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
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

        $post = ScheduledPost::where('org_id', $orgId)
            ->where('post_id', $postId)
            ->firstOrFail();

        $post->approve($userId);

        return response()->json([
            'success' => true,
            'message' => 'Post approved',
            'post' => $post,
        ]);
    }

    /**
     * Reject post.
     */
    public function reject(Request $request, string $postId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $post = ScheduledPost::where('org_id', $orgId)
            ->where('post_id', $postId)
            ->firstOrFail();

        $post->reject();

        return response()->json([
            'success' => true,
            'message' => 'Post rejected',
        ]);
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

        return response()->json([
            'success' => true,
            'calendar' => $calendar,
        ]);
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

        return response()->json([
            'success' => true,
            'overview' => $overview,
        ]);
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

        return response()->json([
            'success' => true,
            'content' => $content,
        ]);
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
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $contentItem = ContentLibrary::create(array_merge($request->all(), [
            'org_id' => $request->user()->org_id,
            'created_by' => $request->user()->user_id,
        ]));

        return response()->json([
            'success' => true,
            'content' => $contentItem,
        ], 201);
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

        return response()->json([
            'success' => true,
            'best_times' => $bestTimes,
        ]);
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

        return response()->json([
            'success' => true,
            'suggested_time' => $suggestedTime->toISOString(),
        ]);
    }

    // ===== Analytics & Stats =====

    /**
     * Get publishing stats.
     */
    public function getStats(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $stats = $this->calendarService->getSummary($orgId);

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}
