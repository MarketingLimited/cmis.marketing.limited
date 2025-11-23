<?php

namespace App\Http\Controllers\GPT;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Creative\ContentPlan;
use App\Services\ContentPlanService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * GPT Content Controller
 *
 * Handles content plan operations for GPT/ChatGPT integration
 */
class GPTContentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ContentPlanService $contentPlanService
    ) {}

    /**
     * List content plans
     */
    public function index(Request $request): JsonResponse
    {
        $campaignId = $request->query('campaign_id');
        $status = $request->query('status');

        $query = ContentPlan::where('org_id', $request->user()->current_org_id)
            ->with(['campaign'])
            ->latest();

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $plans = $query->limit(50)->get();

        return $this->success($plans->map(fn($p) => $this->formatContentPlan($p)));
    }

    /**
     * Create content plan
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'campaign_id' => 'required|uuid|exists:cmis.campaigns,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content_type' => 'required|in:social_post,blog_article,ad_copy,email,video_script',
            'target_platforms' => 'required|array',
            'tone' => 'nullable|string',
            'key_messages' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $plan = $this->contentPlanService->create($validator->validated());

        return $this->created($this->formatContentPlan($plan), 'Content plan created successfully');
    }

    /**
     * Generate content for a content plan
     */
    public function generate(Request $request, string $contentPlanId): JsonResponse
    {
        $contentPlan = ContentPlan::findOrFail($contentPlanId);
        $this->authorize('update', $contentPlan);

        $validator = Validator::make($request->all(), [
            'prompt' => 'nullable|string',
            'options' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $result = $this->contentPlanService->generateContent(
            $contentPlan,
            $request->input('prompt'),
            $request->input('options', [])
        );

        return $this->success($result, 'Content generation started');
    }

    /**
     * Format content plan for GPT response
     */
    private function formatContentPlan($plan): array
    {
        return [
            'id' => $plan->id,
            'campaign_id' => $plan->campaign_id,
            'name' => $plan->name,
            'description' => $plan->description,
            'content_type' => $plan->content_type,
            'target_platforms' => $plan->target_platforms,
            'status' => $plan->status,
            'generated_content' => $plan->generated_content,
            'created_at' => $plan->created_at?->toISOString(),
        ];
    }
}
