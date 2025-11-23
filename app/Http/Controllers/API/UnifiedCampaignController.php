<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Concerns\ApiResponse;

use App\Http\Controllers\Controller;
use App\Models\Core\Org;
use App\Models\Campaign;
use App\Services\Campaign\UnifiedCampaignService;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

/**
 * @group Unified Campaigns
 *
 * Create complex marketing campaigns with ads and content in a single API call.
 * This unified approach ensures transaction safety and automatic event firing.
 */
class UnifiedCampaignController extends Controller
{
    use ApiResponse;

    public function __construct(
        private UnifiedCampaignService $campaignService
    ) {}

    /**
     * Create integrated campaign
     *
     * Creates a complete marketing campaign with ad campaigns across multiple platforms
     * and scheduled social media content in a single, transaction-safe operation.
     *
     * **Features:**
     * - Single API call for complex campaign setup
     * - Transaction-safe (rollback on any failure)
     * - Automatic event firing
     * - Multi-platform ad campaign creation
     * - Content scheduling across social platforms
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @bodyParam name string required Campaign name. Example: Summer 2024 Campaign
     * @bodyParam total_budget numeric required Total campaign budget. Example: 10000
     * @bodyParam start_date date required Campaign start date. Example: 2024-06-01
     * @bodyParam end_date date required Campaign end date (must be after start_date). Example: 2024-08-31
     * @bodyParam description string Campaign description. Example: Summer promotional campaign
     * @bodyParam activate boolean Whether to activate the campaign immediately. Example: true
     *
     * @bodyParam ads array Ad campaigns configuration (optional).
     * @bodyParam ads[].platform string required Platform for the ad campaign. Must be one of: google, meta, tiktok, linkedin, twitter. Example: google
     * @bodyParam ads[].name string Ad campaign name. Example: Google Search Campaign
     * @bodyParam ads[].budget numeric required Budget for this ad campaign. Example: 5000
     * @bodyParam ads[].objective string Campaign objective. Example: conversions
     *
     * @bodyParam content.posts array Social media posts configuration (optional).
     * @bodyParam content.posts[].content string required Post content. Example: Check out our summer sale!
     * @bodyParam content.posts[].platforms array required Platforms to publish on. Example: ["facebook", "instagram"]
     * @bodyParam content.posts[].scheduled_for date Post schedule date/time. Example: 2024-06-01T10:00:00Z
     *
     * @response 201 {
     *   "message": "Integrated campaign created successfully",
     *   "data": {
     *     "campaign": {
     *       "id": "uuid",
     *       "name": "Summer 2024 Campaign",
     *       "total_budget": 10000,
     *       "start_date": "2024-06-01",
     *       "end_date": "2024-08-31",
     *       "status": "active"
     *     },
     *     "ad_campaigns": [
     *       {
     *         "id": "uuid",
     *         "platform": "google",
     *         "name": "Google Search Campaign",
     *         "budget": 5000,
     *         "status": "active"
     *       }
     *     ],
     *     "social_posts": [
     *       {
     *         "id": "uuid",
     *         "content": "Check out our summer sale!",
     *         "platforms": ["facebook", "instagram"],
     *         "scheduled_for": "2024-06-01T10:00:00Z",
     *         "status": "scheduled"
     *       }
     *     ],
     *     "metrics": {
     *       "total_spend": 0,
     *       "total_impressions": 0,
     *       "total_clicks": 0
     *     }
     *   }
     * }
     *
     * @response 422 {
     *   "error": "Validation failed",
     *   "messages": {
     *     "name": ["The name field is required."]
     *   }
     * }
     *
     * @response 500 {
     *   "error": "Failed to create campaign",
     *   "message": "Error details"
     * }
     *
     * @authenticated
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
     * Get campaign details
     *
     * Retrieves a campaign with all its components including ad campaigns,
     * social posts, and aggregated metrics.
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     * @urlParam campaign string required Campaign UUID. Example: 770e8400-e29b-41d4-a716-446655440002
     *
     * @response 200 {
     *   "campaign": {
     *     "id": "770e8400-e29b-41d4-a716-446655440002",
     *     "name": "Summer 2024 Campaign",
     *     "total_budget": 10000,
     *     "status": "active"
     *   },
     *   "ad_campaigns": [],
     *   "social_posts": [],
     *   "metrics": {
     *     "total_spend": 5000,
     *     "total_impressions": 100000
     *   }
     * }
     *
     * @response 404 {
     *   "error": "Campaign not found"
     * }
     *
     * @authenticated
     */
    public function show(Org $org, Campaign $campaign): JsonResponse
    {
        if ($campaign->org_id !== $org->org_id) {
            return $this->notFound('Campaign not found');
        }

        return response()->json(
            $this->campaignService->getCampaignWithComponents($campaign)
        );
    }

    /**
     * List campaigns
     *
     * Returns a paginated list of all campaigns for the organization.
     * Supports filtering by type and status.
     *
     * @urlParam org string required Organization UUID. Example: 550e8400-e29b-41d4-a716-446655440000
     *
     * @queryParam type string Filter by campaign type. Example: integrated
     * @queryParam status string Filter by campaign status. Example: active
     * @queryParam per_page integer Number of results per page. Default: 20. Example: 10
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": "uuid",
     *       "name": "Summer Campaign",
     *       "total_budget": 10000,
     *       "status": "active",
     *       "ad_campaigns_count": 3,
     *       "social_posts_count": 5
     *     }
     *   ],
     *   "current_page": 1,
     *   "per_page": 20,
     *   "total": 45
     * }
     *
     * @authenticated
     */
    public function index(Request $request, Org $org): JsonResponse
    {
        $campaigns = Campaign::where('org_id', $org->org_id)
            ->when($request->type, fn($q, $type) => $q->where('type', $type))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->with(['adCampaigns', 'socialPosts'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return $this->success($campaigns, 'Retrieved successfully');
    }
}
