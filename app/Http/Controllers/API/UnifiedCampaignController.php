<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Core\Org;
use App\Models\Campaign;
use App\Services\Campaign\UnifiedCampaignService;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\Validator;

class UnifiedCampaignController extends Controller
{
    public function __construct(
        private UnifiedCampaignService $campaignService
    ) {}

    /**
     * Create integrated campaign (Ads + Content + Scheduling)
     */
    public function store(Request $request, Org $org): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'total_budget' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'description' => 'nullable|string',
            'activate' => 'boolean',

            // Ad campaigns configuration
            'ads' => 'sometimes|array',
            'ads.*.platform' => 'required_with:ads|in:google,meta,tiktok,linkedin,twitter',
            'ads.*.name' => 'nullable|string',
            'ads.*.budget' => 'required_with:ads|numeric|min:0',
            'ads.*.objective' => 'nullable|string',

            // Content configuration
            'content.posts' => 'sometimes|array',
            'content.posts.*.content' => 'required_with:content.posts|string',
            'content.posts.*.platforms' => 'required_with:content.posts|array',
            'content.posts.*.scheduled_for' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $campaign = $this->campaignService->createIntegratedCampaign(
                $org,
                $validator->validated()
            );

            return response()->json([
                'message' => 'Integrated campaign created successfully',
                'data' => $this->campaignService->getCampaignWithComponents($campaign)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create campaign',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get campaign with all components
     */
    public function show(Org $org, Campaign $campaign): JsonResponse
    {
        if ($campaign->org_id !== $org->org_id) {
            return response()->json(['error' => 'Campaign not found'], 404);
        }

        return response()->json(
            $this->campaignService->getCampaignWithComponents($campaign)
        );
    }

    /**
     * List all campaigns for organization
     */
    public function index(Request $request, Org $org): JsonResponse
    {
        $campaigns = Campaign::where('org_id', $org->org_id)
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->with(['adCampaigns', 'socialPosts'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json($campaigns);
    }
}
