<?php

namespace App\Http\Controllers\Influencer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Influencer\Influencer;
use App\Models\Influencer\InfluencerCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InfluencerCampaignController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of influencer campaigns
     */
    public function index(Request $request)
    {
        $orgId = session('current_org_id');

        $campaigns = InfluencerCampaign::where('org_id', $orgId)
            ->when($request->influencer_id, fn($q) => $q->where('influencer_id', $request->influencer_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->campaign_type, fn($q) => $q->where('campaign_type', $request->campaign_type))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->with(['influencer', 'content', 'metrics'])
            ->latest('created_at')
            ->paginate($request->get('per_page', 20));

        if ($request->expectsJson()) {
            return $this->paginated($campaigns, 'Influencer campaigns retrieved successfully');
        }

        return view('influencer.campaigns.index', compact('campaigns'));
    }

    /**
     * Store a newly created influencer campaign
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), InfluencerCampaign::createRules(), InfluencerCampaign::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $campaign = InfluencerCampaign::create(array_merge($request->all(), [
            'org_id' => session('current_org_id'),
        ]));

        if ($request->expectsJson()) {
            return $this->created($campaign, 'Influencer campaign created successfully');
        }

        return redirect()->route('influencer.campaigns.show', $campaign->campaign_id)
            ->with('success', 'Influencer campaign created successfully');
    }

    /**
     * Display the specified influencer campaign
     */
    public function show(string $id)
    {
        $campaign = InfluencerCampaign::with(['influencer', 'content', 'metrics', 'payment'])
            ->findOrFail($id);

        if (request()->expectsJson()) {
            return $this->success($campaign, 'Influencer campaign retrieved successfully');
        }

        return view('influencer.campaigns.show', compact('campaign'));
    }

    /**
     * Update the specified influencer campaign
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), InfluencerCampaign::updateRules(), InfluencerCampaign::validationMessages());

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->validationError($validator->errors(), 'Validation failed');
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $campaign = InfluencerCampaign::findOrFail($id);
        $campaign->update($request->all());

        if ($request->expectsJson()) {
            return $this->success($campaign, 'Influencer campaign updated successfully');
        }

        return redirect()->route('influencer.campaigns.show', $campaign->campaign_id)
            ->with('success', 'Influencer campaign updated successfully');
    }

    /**
     * Remove the specified influencer campaign
     */
    public function destroy(string $id)
    {
        $campaign = InfluencerCampaign::findOrFail($id);
        $campaign->delete();

        if (request()->expectsJson()) {
            return $this->deleted('Influencer campaign deleted successfully');
        }

        return redirect()->route('influencer.campaigns.index')
            ->with('success', 'Influencer campaign deleted successfully');
    }

    /**
     * Get campaign performance metrics
     */
    public function performance(string $id)
    {
        $campaign = InfluencerCampaign::with('metrics')->findOrFail($id);

        $performance = [
            'campaign_id' => $campaign->campaign_id,
            'total_reach' => $campaign->metrics->sum('reach'),
            'total_impressions' => $campaign->metrics->sum('impressions'),
            'total_engagement' => $campaign->metrics->sum('engagement'),
            'total_clicks' => $campaign->metrics->sum('clicks'),
            'total_conversions' => $campaign->metrics->sum('conversions'),
            'engagement_rate' => $campaign->getEngagementRate(),
            'cost_per_engagement' => $campaign->getCostPerEngagement(),
            'roi' => $campaign->calculateROI(),
        ];

        return $this->success($performance, 'Campaign performance retrieved successfully');
    }

    /**
     * Update campaign status
     */
    public function updateStatus(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,pending,active,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $campaign = InfluencerCampaign::findOrFail($id);
        $campaign->update(['status' => $request->status]);

        if ($request->expectsJson()) {
            return $this->success($campaign, 'Campaign status updated successfully');
        }

        return redirect()->route('influencer.campaigns.show', $campaign->campaign_id)
            ->with('success', 'Campaign status updated successfully');
    }

    /**
     * Approve campaign content
     */
    public function approveContent(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'content_id' => 'required|uuid|exists:cmis_influencer.influencer_content,content_id',
            'approved' => 'required|boolean',
            'feedback' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $campaign = InfluencerCampaign::findOrFail($id);

        // Update content approval status
        $content = $campaign->content()->where('content_id', $request->content_id)->first();

        if (!$content) {
            return $this->error('Content not found for this campaign', 404);
        }

        $content->update([
            'approval_status' => $request->approved ? 'approved' : 'rejected',
            'approval_feedback' => $request->feedback,
            'approved_at' => $request->approved ? now() : null,
            'approved_by' => auth()->id(),
        ]);

        return $this->success($content, 'Content approval updated successfully');
    }

    /**
     * Get campaign analytics
     */
    public function analytics(Request $request)
    {
        $orgId = session('current_org_id');

        $campaigns = InfluencerCampaign::where('org_id', $orgId)->get();

        $totalCampaigns = $campaigns->count();
        $activeCampaigns = $campaigns->where('status', 'active')->count();
        $completedCampaigns = $campaigns->where('status', 'completed')->count();
        $totalSpend = $campaigns->sum('budget');
        $avgEngagement = $campaigns->avg('total_engagement');

        $analytics = [
            'summary' => [
                'total_campaigns' => $totalCampaigns,
                'active_campaigns' => $activeCampaigns,
                'completed_campaigns' => $completedCampaigns,
                'total_spend' => $totalSpend,
                'avg_engagement' => round($avgEngagement, 2),
            ],
            'by_type' => $campaigns->groupBy('campaign_type')->map->count(),
            'by_status' => $campaigns->groupBy('status')->map->count(),
        ];

        if ($request->expectsJson()) {
            return $this->success($analytics, 'Campaign analytics retrieved successfully');
        }

        return view('influencer.campaigns.analytics', compact('analytics'));
    }

    /**
     * Bulk update campaigns
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'campaign_ids' => 'required|array',
            'campaign_ids.*' => 'uuid',
            'status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $orgId = session('current_org_id');

        $updated = InfluencerCampaign::where('org_id', $orgId)
            ->whereIn('campaign_id', $request->campaign_ids)
            ->update(array_filter([
                'status' => $request->status,
            ]));

        return $this->success([
            'updated_count' => $updated,
        ], 'Campaigns updated successfully');
    }

    /**
     * Export campaign report
     */
    public function export(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'nullable|in:csv,json,pdf',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $campaign = InfluencerCampaign::with(['influencer', 'content', 'metrics', 'payment'])
            ->findOrFail($id);

        $format = $request->format ?? 'json';

        $export = [
            'campaign' => [
                'name' => $campaign->name,
                'type' => $campaign->campaign_type,
                'status' => $campaign->status,
                'budget' => $campaign->budget,
                'start_date' => $campaign->start_date,
                'end_date' => $campaign->end_date,
            ],
            'influencer' => [
                'name' => $campaign->influencer->name,
                'platform' => $campaign->influencer->platform,
                'username' => $campaign->influencer->username,
            ],
            'performance' => [
                'total_reach' => $campaign->metrics->sum('reach'),
                'total_engagement' => $campaign->metrics->sum('engagement'),
                'engagement_rate' => $campaign->getEngagementRate(),
                'roi' => $campaign->calculateROI(),
            ],
            'content_count' => $campaign->content->count(),
            'exported_at' => now()->toIso8601String(),
        ];

        return $this->success($export, 'Campaign report exported successfully');
    }
}
