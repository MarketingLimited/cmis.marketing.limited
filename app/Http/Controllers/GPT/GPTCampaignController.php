<?php

namespace App\Http\Controllers\GPT;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * GPT Campaign Controller
 *
 * Handles campaign-related operations for GPT/ChatGPT integration
 */
class GPTCampaignController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CampaignService $campaignService
    ) {}

    /**
     * List campaigns
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $limit = min($request->query('limit', 20), 100);

        $query = Campaign::where('org_id', $request->user()->current_org_id)
            ->with(['contentPlans'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $campaigns = $query->limit($limit)->get();

        return $this->success($campaigns->map(fn($c) => $this->formatCampaign($c)));
    }

    /**
     * Get single campaign
     */
    public function show(Request $request, string $campaignId): JsonResponse
    {
        $campaign = Campaign::with(['contentPlans', 'adAccounts'])
            ->findOrFail($campaignId);

        $this->authorize('view', $campaign);

        return $this->success($this->formatCampaign($campaign, true));
    }

    /**
     * Create campaign
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'target_audience' => 'nullable|array',
            'objectives' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $data = $validator->validated();
        $data['org_id'] = $request->user()->current_org_id;
        $data['status'] = 'draft';
        $data['created_by'] = $request->user()->user_id;

        $campaign = $this->campaignService->create($data);

        return $this->created($this->formatCampaign($campaign), 'Campaign created successfully');
    }

    /**
     * Update campaign
     */
    public function update(Request $request, string $campaignId): JsonResponse
    {
        $campaign = Campaign::findOrFail($campaignId);
        $this->authorize('update', $campaign);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:draft,active,paused,completed,archived',
            'budget' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $campaign = $this->campaignService->update($campaign, $validator->validated());

        return $this->success($this->formatCampaign($campaign), 'Campaign updated successfully');
    }

    /**
     * Publish campaign
     *
     * Complete workflow: Validation → Approval → Platform API submission
     */
    public function publish(Request $request, string $campaignId): JsonResponse
    {
        try {
            $campaign = Campaign::with(['contentPlans', 'adAccounts', 'integrations'])->findOrFail($campaignId);
            $this->authorize('update', $campaign);

            // Step 1: Validate campaign is ready to publish
            $validationErrors = $this->campaignService->validateForPublish($campaign);
            if (!empty($validationErrors)) {
                return $this->error(
                    'Campaign cannot be published. Please fix the following issues:',
                    422,
                    ['validation_errors' => $validationErrors]
                );
            }

            // Step 2: Check platform integrations are connected
            if ($campaign->integrations->isEmpty()) {
                return $this->error(
                    'No platform integrations connected. Please connect at least one platform (Meta, Google, TikTok, etc.) before publishing.',
                    400
                );
            }

            // Step 3: Publish to all connected platforms
            $publishResults = [];
            $failures = [];

            foreach ($campaign->integrations as $integration) {
                try {
                    $connector = \App\Services\Connectors\ConnectorFactory::make($integration->platform);
                    $result = $connector->publishCampaign($campaign, $integration);
                    $publishResults[$integration->platform] = [
                        'status' => 'success',
                        'platform_campaign_id' => $result['campaign_id'] ?? null,
                    ];
                } catch (\Exception $e) {
                    \Log::error("Failed to publish campaign {$campaignId} to {$integration->platform}: {$e->getMessage()}");
                    $failures[$integration->platform] = $e->getMessage();
                    $publishResults[$integration->platform] = [
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Step 4: Update campaign status
            if (empty($failures)) {
                // All platforms succeeded
                $campaign->update([
                    'status' => 'active',
                    'published_at' => now(),
                ]);

                return $this->success([
                    'campaign' => $this->formatCampaign($campaign),
                    'publish_results' => $publishResults,
                    'status' => 'published',
                ], 'Campaign published successfully to all platforms');
            } else {
                // Some platforms failed
                $campaign->update([
                    'status' => 'partially_published',
                    'published_at' => now(),
                ]);

                $successCount = count($publishResults) - count($failures);
                return $this->error(
                    "Campaign published to {$successCount} platform(s), but failed on " . count($failures) . " platform(s). See details below.",
                    207, // 207 Multi-Status
                    [
                        'publish_results' => $publishResults,
                        'failures' => $failures,
                    ]
                );
            }
        } catch (\Exception $e) {
            \Log::error("Campaign publish workflow failed for {$campaignId}: {$e->getMessage()}");
            return $this->serverError('Failed to publish campaign. Please try again or contact support.');
        }
    }

    /**
     * Format campaign for GPT response
     */
    private function formatCampaign($campaign, bool $detailed = false): array
    {
        $data = [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'description' => $campaign->description,
            'status' => $campaign->status,
            'start_date' => $campaign->start_date?->toDateString(),
            'end_date' => $campaign->end_date?->toDateString(),
            'budget' => $campaign->budget,
            'spent' => $campaign->spent,
            'created_at' => $campaign->created_at?->toISOString(),
        ];

        if ($detailed) {
            $data['content_plans_count'] = $campaign->contentPlans?->count() ?? 0;
            $data['ad_accounts_count'] = $campaign->adAccounts?->count() ?? 0;
        }

        return $data;
    }
}
