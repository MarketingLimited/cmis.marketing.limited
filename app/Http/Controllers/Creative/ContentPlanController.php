<?php

namespace App\Http\Controllers\Creative;

use App\Http\Controllers\Controller;
use App\Models\Creative\ContentPlan;
use App\Services\ContentPlanService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Controllers\Concerns\ApiResponse;

class ContentPlanController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ContentPlanService $contentPlanService
    ) {
        // Apply authorization to all actions
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of content plans.
     */
    public function index(Request $request): JsonResponse
    {
        // Check authorization for viewing any content plans
        $this->authorize('viewAny', ContentPlan::class);

        $query = ContentPlan::query()
            ->where('org_id', $request->user()->current_org_id)
            ->with(['campaign', 'creator', 'items']);

        // Filter by campaign
        if ($request->has('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'ILIKE', '%' . $request->search . '%');
        }

        // Date range filter
        if ($request->has('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('end_date', '<=', $request->end_date);
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $plans = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $plans->items(),
            'meta' => [
                'current_page' => $plans->currentPage(),
                'last_page' => $plans->lastPage(),
                'per_page' => $plans->perPage(),
                'total' => $plans->total(),
            ]
        ]);
    }

    /**
     * Show the form for creating a new content plan.
     */
    public function create(Request $request): JsonResponse
    {
        // Return metadata for creating content plan
        return response()->json([
            'success' => true,
            'data' => [
                'content_types' => [
                    'social_post' => 'Social Media Post',
                    'blog_article' => 'Blog Article',
                    'ad_copy' => 'Advertisement Copy',
                    'email' => 'Email Campaign',
                    'video_script' => 'Video Script',
                ],
                'platforms' => [
                    'facebook' => 'Facebook',
                    'instagram' => 'Instagram',
                    'twitter' => 'Twitter',
                    'linkedin' => 'LinkedIn',
                    'youtube' => 'YouTube',
                    'tiktok' => 'TikTok',
                ],
                'tones' => [
                    'professional' => 'Professional',
                    'casual' => 'Casual',
                    'friendly' => 'Friendly',
                    'authoritative' => 'Authoritative',
                    'playful' => 'Playful',
                    'inspirational' => 'Inspirational',
                ],
                'statuses' => [
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'completed' => 'Completed',
                    'archived' => 'Archived',
                ],
            ]
        ]);
    }

    /**
     * Store a newly created content plan.
     */
    public function store(Request $request): JsonResponse
    {
        // Check authorization for creating content plans
        $this->authorize('create', ContentPlan::class);

        $validator = Validator::make($request->all(), [
            'campaign_id' => 'required|uuid|exists:cmis.campaigns,campaign_id',
            'name' => 'required|string|max:255',
            'timeframe_daterange' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'strategy' => 'nullable|array',
            'channels' => 'nullable|array',
            'themes' => 'nullable|array',
            'objectives' => 'nullable|array',
            'brief_id' => 'nullable|uuid',
            'creative_context_id' => 'nullable|uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()
            , 'Validation failed');
        }

        $data = $validator->validated();
        $data['plan_id'] = Str::uuid();
        $data['org_id'] = $request->user()->current_org_id;
        $data['created_by'] = $request->user()->user_id;
        $data['status'] = 'draft';

        $contentPlan = ContentPlan::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Content plan created successfully',
            'data' => $contentPlan->load(['campaign', 'creator'])
        ], 201);
    }

    /**
     * Display the specified content plan.
     */
    public function show(Request $request, string $plan_id): JsonResponse
    {
        $contentPlan = ContentPlan::with(['campaign', 'creator', 'items'])
            ->where('plan_id', $plan_id)
            ->where('org_id', $request->user()->current_org_id)
            ->firstOrFail();

        // Check authorization for viewing this content plan
        $this->authorize('view', $contentPlan);

        return $this->success($contentPlan
        );
    }

    /**
     * Show the form for editing the specified content plan.
     */
    public function edit(Request $request, string $plan_id): JsonResponse
    {
        $contentPlan = ContentPlan::where('plan_id', $plan_id)
            ->where('org_id', $request->user()->current_org_id)
            ->firstOrFail();

        // Check authorization for editing this content plan
        $this->authorize('update', $contentPlan);

        // Return plan data along with options
        return response()->json([
            'success' => true,
            'data' => [
                'plan' => $contentPlan,
                'options' => [
                    'platforms' => [
                        'facebook' => 'Facebook',
                        'instagram' => 'Instagram',
                        'twitter' => 'Twitter',
                        'linkedin' => 'LinkedIn',
                        'youtube' => 'YouTube',
                        'tiktok' => 'TikTok',
                    ],
                    'statuses' => [
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'archived' => 'Archived',
                    ],
                ]
            ]
        ]);
    }

    /**
     * Update the specified content plan.
     */
    public function update(Request $request, string $plan_id): JsonResponse
    {
        $contentPlan = ContentPlan::where('plan_id', $plan_id)
            ->where('org_id', $request->user()->current_org_id)
            ->firstOrFail();

        // Check authorization for updating this content plan
        $this->authorize('update', $contentPlan);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'campaign_id' => 'sometimes|uuid|exists:cmis.campaigns,campaign_id',
            'timeframe_daterange' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'sometimes|in:draft,active,completed,archived',
            'strategy' => 'nullable|array',
            'channels' => 'nullable|array',
            'themes' => 'nullable|array',
            'objectives' => 'nullable|array',
            'brief_id' => 'nullable|uuid',
            'creative_context_id' => 'nullable|uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()
            , 'Validation failed');
        }

        $contentPlan->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Content plan updated successfully',
            'data' => $contentPlan->fresh()->load(['campaign', 'creator'])
        ]);
    }

    /**
     * Remove the specified content plan.
     */
    public function destroy(Request $request, string $plan_id): JsonResponse
    {
        $contentPlan = ContentPlan::where('plan_id', $plan_id)
            ->where('org_id', $request->user()->current_org_id)
            ->firstOrFail();

        // Check authorization for deleting this content plan
        $this->authorize('delete', $contentPlan);

        $contentPlan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Content plan deleted successfully'
        ]);
    }

    /**
     * Generate AI content for the content plan
     */
    public function generateContent(Request $request, string $plan_id): JsonResponse
    {
        $contentPlan = ContentPlan::where('plan_id', $plan_id)
            ->where('org_id', $request->user()->current_org_id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'prompt' => 'nullable|string',
            'options' => 'nullable|array',
            'async' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()
            , 'Validation failed');
        }

        $async = $request->get('async', true);

        if ($async) {
            // Use queue for async generation
            $result = $this->contentPlanService->generateContent(
                $contentPlan,
                $request->input('prompt'),
                $request->input('options', [])
            );
        } else {
            // Generate synchronously
            $content = $this->contentPlanService->generateContentSync(
                $contentPlan,
                $request->input('prompt'),
                $request->input('options', [])
            );

            $result = [
                'content_plan_id' => $plan_id,
                'status' => $content ? 'generated' : 'failed',
                'content' => $content,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => $async ? 'Content generation started' : 'Content generated',
            'data' => $result
        ]);
    }

    /**
     * Approve content plan
     */
    public function approve(Request $request, string $plan_id): JsonResponse
    {
        $contentPlan = ContentPlan::where('plan_id', $plan_id)
            ->where('org_id', $request->user()->current_org_id)
            ->firstOrFail();

        $contentPlan = $this->contentPlanService->approve($contentPlan);

        return response()->json([
            'success' => true,
            'message' => 'Content plan approved',
            'data' => $contentPlan
        ]);
    }

    /**
     * Reject content plan
     */
    public function reject(Request $request, string $plan_id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()
            , 'Validation failed');
        }

        $contentPlan = ContentPlan::where('plan_id', $plan_id)
            ->where('org_id', $request->user()->current_org_id)
            ->firstOrFail();

        $contentPlan = $this->contentPlanService->reject($contentPlan, $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Content plan rejected',
            'data' => $contentPlan
        ]);
    }

    /**
     * Publish content plan
     */
    public function publish(Request $request, string $plan_id): JsonResponse
    {
        $contentPlan = ContentPlan::where('plan_id', $plan_id)
            ->where('org_id', $request->user()->current_org_id)
            ->firstOrFail();

        $contentPlan = $this->contentPlanService->publish($contentPlan);

        return response()->json([
            'success' => true,
            'message' => 'Content plan published',
            'data' => $contentPlan
        ]);
    }

    /**
     * Get content plan statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->contentPlanService->getStats($request->user()->current_org_id);

        return $this->success($stats
        );
    }
}
