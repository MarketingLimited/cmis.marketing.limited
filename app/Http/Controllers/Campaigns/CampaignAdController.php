<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Campaign;
use App\Models\Campaign\CampaignAdSet;
use App\Models\Campaign\CampaignAd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CampaignAdController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of ads for an ad set.
     */
    public function index(Request $request, string $org, string $campaignId, string $adSetId)
    {
        $campaign = Campaign::where('campaign_id', $campaignId)->firstOrFail();
        $adSet = CampaignAdSet::where('ad_set_id', $adSetId)
            ->where('campaign_id', $campaignId)
            ->firstOrFail();

        $ads = CampaignAd::where('ad_set_id', $adSetId)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        if ($request->wantsJson()) {
            return $this->paginated($ads, 'Ads retrieved successfully');
        }

        return view('campaigns.ads.index', [
            'campaign' => $campaign,
            'adSet' => $adSet,
            'ads' => $ads,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show the form for creating a new ad.
     */
    public function create(Request $request, string $org, string $campaignId, string $adSetId)
    {
        $campaign = Campaign::where('campaign_id', $campaignId)->firstOrFail();
        $adSet = CampaignAdSet::where('ad_set_id', $adSetId)
            ->where('campaign_id', $campaignId)
            ->firstOrFail();

        return view('campaigns.ads.create', [
            'campaign' => $campaign,
            'adSet' => $adSet,
            'currentOrg' => $org,
            'adFormats' => CampaignAd::getFormatOptions(),
            'callToActions' => CampaignAd::getCallToActionOptions(),
        ]);
    }

    /**
     * Store a newly created ad in storage.
     */
    public function store(Request $request, string $org, string $campaignId, string $adSetId)
    {
        $campaign = Campaign::where('campaign_id', $campaignId)->firstOrFail();
        $adSet = CampaignAdSet::where('ad_set_id', $adSetId)
            ->where('campaign_id', $campaignId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:draft,active,paused',
            'ad_format' => 'nullable|string|max:50',
            'primary_text' => 'nullable|string|max:5000',
            'headline' => 'nullable|string|max:255',
            'description_text' => 'nullable|string|max:1000',
            'call_to_action' => 'nullable|string|max:50',
            'media' => 'nullable|array',
            'image_url' => 'nullable|url|max:2048',
            'video_url' => 'nullable|url|max:2048',
            'thumbnail_url' => 'nullable|url|max:2048',
            'carousel_cards' => 'nullable|array',
            'destination_url' => 'nullable|url|max:2048',
            'display_url' => 'nullable|string|max:255',
            'url_parameters' => 'nullable|array',
            'tracking_pixel_id' => 'nullable|string|max:255',
            'tracking_specs' => 'nullable|array',
            'is_dynamic_creative' => 'nullable|boolean',
            'dynamic_creative_assets' => 'nullable|array',
            'platform_settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $ad = CampaignAd::create([
                'org_id' => $request->attributes->get('org_id'),
                'campaign_id' => $campaignId,
                'ad_set_id' => $adSetId,
                'created_by' => auth()->id(),
                'review_status' => CampaignAd::REVIEW_PENDING,
                ...$validator->validated(),
            ]);

            if ($request->wantsJson()) {
                return $this->created($ad, 'Ad created successfully');
            }

            return redirect()
                ->route('org.campaigns.ad-sets.ads.show', [$org, $campaignId, $adSetId, $ad->ad_id])
                ->with('success', __('campaignad.created_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to create ad: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to create ad'])->withInput();
        }
    }

    /**
     * Display the specified ad.
     */
    public function show(Request $request, string $org, string $campaignId, string $adSetId, string $adId)
    {
        $campaign = Campaign::where('campaign_id', $campaignId)->firstOrFail();
        $adSet = CampaignAdSet::where('ad_set_id', $adSetId)
            ->where('campaign_id', $campaignId)
            ->firstOrFail();
        $ad = CampaignAd::where('ad_id', $adId)
            ->where('ad_set_id', $adSetId)
            ->firstOrFail();

        if ($request->wantsJson()) {
            return $this->success($ad, 'Ad retrieved successfully');
        }

        return view('campaigns.ads.show', [
            'campaign' => $campaign,
            'adSet' => $adSet,
            'ad' => $ad,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show the form for editing the specified ad.
     */
    public function edit(Request $request, string $org, string $campaignId, string $adSetId, string $adId)
    {
        $campaign = Campaign::where('campaign_id', $campaignId)->firstOrFail();
        $adSet = CampaignAdSet::where('ad_set_id', $adSetId)
            ->where('campaign_id', $campaignId)
            ->firstOrFail();
        $ad = CampaignAd::where('ad_id', $adId)
            ->where('ad_set_id', $adSetId)
            ->firstOrFail();

        return view('campaigns.ads.edit', [
            'campaign' => $campaign,
            'adSet' => $adSet,
            'ad' => $ad,
            'currentOrg' => $org,
            'adFormats' => CampaignAd::getFormatOptions(),
            'callToActions' => CampaignAd::getCallToActionOptions(),
        ]);
    }

    /**
     * Update the specified ad in storage.
     */
    public function update(Request $request, string $org, string $campaignId, string $adSetId, string $adId)
    {
        $ad = CampaignAd::where('ad_id', $adId)
            ->where('ad_set_id', $adSetId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:draft,active,paused,completed,archived',
            'ad_format' => 'nullable|string|max:50',
            'primary_text' => 'nullable|string|max:5000',
            'headline' => 'nullable|string|max:255',
            'description_text' => 'nullable|string|max:1000',
            'call_to_action' => 'nullable|string|max:50',
            'media' => 'nullable|array',
            'image_url' => 'nullable|url|max:2048',
            'video_url' => 'nullable|url|max:2048',
            'thumbnail_url' => 'nullable|url|max:2048',
            'carousel_cards' => 'nullable|array',
            'destination_url' => 'nullable|url|max:2048',
            'display_url' => 'nullable|string|max:255',
            'url_parameters' => 'nullable|array',
            'tracking_pixel_id' => 'nullable|string|max:255',
            'tracking_specs' => 'nullable|array',
            'is_dynamic_creative' => 'nullable|boolean',
            'dynamic_creative_assets' => 'nullable|array',
            'platform_settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $ad->update($validator->validated());

            if ($request->wantsJson()) {
                return $this->success($ad->fresh(), 'Ad updated successfully');
            }

            return redirect()
                ->route('org.campaigns.ad-sets.ads.show', [$org, $campaignId, $adSetId, $adId])
                ->with('success', __('campaignad.updated_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to update ad: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to update ad'])->withInput();
        }
    }

    /**
     * Remove the specified ad from storage.
     */
    public function destroy(Request $request, string $org, string $campaignId, string $adSetId, string $adId)
    {
        $ad = CampaignAd::where('ad_id', $adId)
            ->where('ad_set_id', $adSetId)
            ->firstOrFail();

        try {
            $ad->delete();

            if ($request->wantsJson()) {
                return $this->deleted('Ad deleted successfully');
            }

            return redirect()
                ->route('org.campaigns.ad-sets.ads.index', [$org, $campaignId, $adSetId])
                ->with('success', __('campaignad.deleted_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to delete ad: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to delete ad']);
        }
    }

    /**
     * Duplicate an ad.
     */
    public function duplicate(Request $request, string $org, string $campaignId, string $adSetId, string $adId)
    {
        $ad = CampaignAd::where('ad_id', $adId)
            ->where('ad_set_id', $adSetId)
            ->firstOrFail();

        try {
            $newAd = $ad->replicate();
            $newAd->name = $ad->name . ' (Copy)';
            $newAd->status = CampaignAd::STATUS_DRAFT;
            $newAd->external_ad_id = null;
            $newAd->external_creative_id = null;
            $newAd->sync_status = 'pending';
            $newAd->last_synced_at = null;
            $newAd->review_status = CampaignAd::REVIEW_PENDING;
            $newAd->review_feedback = null;
            $newAd->created_by = auth()->id();
            $newAd->save();

            if ($request->wantsJson()) {
                return $this->created($newAd, 'Ad duplicated successfully');
            }

            return redirect()
                ->route('org.campaigns.ad-sets.ads.edit', [$org, $campaignId, $adSetId, $newAd->ad_id])
                ->with('success', __('campaignad.ad_duplicated_successfully'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to duplicate ad: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to duplicate ad']);
        }
    }

    /**
     * Update ad status (quick action).
     */
    public function updateStatus(Request $request, string $org, string $campaignId, string $adSetId, string $adId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,active,paused,completed,archived',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $ad = CampaignAd::where('ad_id', $adId)
            ->where('ad_set_id', $adSetId)
            ->firstOrFail();

        try {
            $ad->update(['status' => $request->status]);

            if ($request->wantsJson()) {
                return $this->success($ad->fresh(), 'Ad status updated successfully');
            }

            return back()->with('success', __('campaignad.updated_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to update status: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to update status']);
        }
    }

    /**
     * Preview ad creative.
     */
    public function preview(Request $request, string $org, string $campaignId, string $adSetId, string $adId)
    {
        $campaign = Campaign::where('campaign_id', $campaignId)->firstOrFail();
        $adSet = CampaignAdSet::where('ad_set_id', $adSetId)
            ->where('campaign_id', $campaignId)
            ->firstOrFail();
        $ad = CampaignAd::where('ad_id', $adId)
            ->where('ad_set_id', $adSetId)
            ->firstOrFail();

        // Get platform-specific preview
        $previewData = $this->generatePreview($campaign, $ad);

        if ($request->wantsJson()) {
            return $this->success($previewData, 'Preview generated successfully');
        }

        return view('campaigns.ads.preview', [
            'campaign' => $campaign,
            'adSet' => $adSet,
            'ad' => $ad,
            'previewData' => $previewData,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Generate preview data based on platform.
     */
    private function generatePreview(Campaign $campaign, CampaignAd $ad): array
    {
        $preview = [
            'platform' => $campaign->platform,
            'format' => $ad->ad_format,
            'headline' => $ad->headline,
            'primary_text' => $ad->primary_text,
            'description' => $ad->description_text,
            'call_to_action' => $ad->call_to_action,
            'destination_url' => $ad->getFullDestinationUrl(),
            'media_url' => $ad->getPrimaryMediaUrl(),
        ];

        // Add platform-specific preview settings
        switch ($campaign->platform) {
            case Campaign::PLATFORM_META:
                $preview['placements'] = ['feed', 'stories', 'reels', 'right_column'];
                break;
            case Campaign::PLATFORM_GOOGLE:
                $preview['placements'] = ['search', 'display', 'youtube', 'discovery'];
                break;
            case Campaign::PLATFORM_TIKTOK:
                $preview['placements'] = ['for_you', 'in_feed'];
                break;
            case Campaign::PLATFORM_SNAPCHAT:
                $preview['placements'] = ['snap_ads', 'story_ads', 'spotlight'];
                break;
            case Campaign::PLATFORM_TWITTER:
                $preview['placements'] = ['timeline', 'search', 'profile'];
                break;
            case Campaign::PLATFORM_LINKEDIN:
                $preview['placements'] = ['feed', 'right_rail', 'messaging'];
                break;
        }

        return $preview;
    }
}
