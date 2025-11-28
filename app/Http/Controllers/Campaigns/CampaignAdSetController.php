<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Campaign;
use App\Models\Campaign\CampaignAdSet;
use App\Services\Platform\AudienceTargetingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CampaignAdSetController extends Controller
{
    use ApiResponse;

    protected AudienceTargetingService $audienceService;

    public function __construct(AudienceTargetingService $audienceService)
    {
        $this->audienceService = $audienceService;
    }

    /**
     * Display a listing of ad sets for a campaign.
     */
    public function index(Request $request, string $org, string $campaignId)
    {
        $campaign = Campaign::where('campaign_id', $campaignId)->firstOrFail();

        $adSets = CampaignAdSet::where('campaign_id', $campaignId)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        if ($request->wantsJson()) {
            return $this->paginated($adSets, 'Ad sets retrieved successfully');
        }

        return view('campaigns.ad-sets.index', [
            'campaign' => $campaign,
            'adSets' => $adSets,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show the form for creating a new ad set.
     */
    public function create(Request $request, string $org, string $campaignId)
    {
        $campaign = Campaign::where('campaign_id', $campaignId)->firstOrFail();

        // Get audience targeting options based on campaign platform
        $audienceOptions = $this->getAudienceOptionsForPlatform($campaign->platform ?? 'meta');

        return view('campaigns.ad-sets.create', [
            'campaign' => $campaign,
            'currentOrg' => $org,
            'audienceOptions' => $audienceOptions,
        ]);
    }

    /**
     * Store a newly created ad set in storage.
     */
    public function store(Request $request, string $org, string $campaignId)
    {
        $campaign = Campaign::where('campaign_id', $campaignId)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:draft,active,paused',
            'budget_type' => 'nullable|in:daily,lifetime',
            'daily_budget' => 'nullable|numeric|min:0',
            'lifetime_budget' => 'nullable|numeric|min:0',
            'bid_strategy' => 'nullable|string|max:50',
            'bid_amount' => 'nullable|numeric|min:0',
            'billing_event' => 'nullable|string|max:50',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'optimization_goal' => 'nullable|string|max:100',
            'conversion_event' => 'nullable|string|max:100',
            'pixel_id' => 'nullable|string|max:255',
            'app_id' => 'nullable|string|max:255',
            'targeting' => 'nullable|array',
            'locations' => 'nullable|array',
            'age_range' => 'nullable|array',
            'genders' => 'nullable|array',
            'interests' => 'nullable|array',
            'behaviors' => 'nullable|array',
            'custom_audiences' => 'nullable|array',
            'lookalike_audiences' => 'nullable|array',
            'excluded_audiences' => 'nullable|array',
            'placements' => 'nullable|array',
            'automatic_placements' => 'nullable|boolean',
            'device_platforms' => 'nullable|array',
            'publisher_platforms' => 'nullable|array',
            'platform_settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $adSet = CampaignAdSet::create([
                'org_id' => $request->attributes->get('org_id'),
                'campaign_id' => $campaignId,
                'created_by' => auth()->id(),
                ...$validator->validated(),
            ]);

            if ($request->wantsJson()) {
                return $this->created($adSet, 'Ad set created successfully');
            }

            return redirect()
                ->route('org.campaigns.ad-sets.show', [$org, $campaignId, $adSet->ad_set_id])
                ->with('success', __('campaignadset.created_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to create ad set: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to create ad set'])->withInput();
        }
    }

    /**
     * Display the specified ad set.
     */
    public function show(Request $request, string $org, string $campaignId, string $adSetId)
    {
        $campaign = Campaign::where('campaign_id', $campaignId)->firstOrFail();
        $adSet = CampaignAdSet::where('ad_set_id', $adSetId)
            ->where('campaign_id', $campaignId)
            ->with('ads')
            ->firstOrFail();

        if ($request->wantsJson()) {
            return $this->success($adSet, 'Ad set retrieved successfully');
        }

        return view('campaigns.ad-sets.show', [
            'campaign' => $campaign,
            'adSet' => $adSet,
            'currentOrg' => $org,
        ]);
    }

    /**
     * Show the form for editing the specified ad set.
     */
    public function edit(Request $request, string $org, string $campaignId, string $adSetId)
    {
        $campaign = Campaign::where('campaign_id', $campaignId)->firstOrFail();
        $adSet = CampaignAdSet::where('ad_set_id', $adSetId)
            ->where('campaign_id', $campaignId)
            ->firstOrFail();

        // Get audience targeting options based on campaign platform
        $audienceOptions = $this->getAudienceOptionsForPlatform($campaign->platform ?? 'meta');

        return view('campaigns.ad-sets.edit', [
            'campaign' => $campaign,
            'adSet' => $adSet,
            'currentOrg' => $org,
            'audienceOptions' => $audienceOptions,
        ]);
    }

    /**
     * Update the specified ad set in storage.
     */
    public function update(Request $request, string $org, string $campaignId, string $adSetId)
    {
        $adSet = CampaignAdSet::where('ad_set_id', $adSetId)
            ->where('campaign_id', $campaignId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:draft,active,paused,completed,archived',
            'budget_type' => 'nullable|in:daily,lifetime',
            'daily_budget' => 'nullable|numeric|min:0',
            'lifetime_budget' => 'nullable|numeric|min:0',
            'bid_strategy' => 'nullable|string|max:50',
            'bid_amount' => 'nullable|numeric|min:0',
            'billing_event' => 'nullable|string|max:50',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'optimization_goal' => 'nullable|string|max:100',
            'conversion_event' => 'nullable|string|max:100',
            'pixel_id' => 'nullable|string|max:255',
            'app_id' => 'nullable|string|max:255',
            'targeting' => 'nullable|array',
            'locations' => 'nullable|array',
            'age_range' => 'nullable|array',
            'genders' => 'nullable|array',
            'interests' => 'nullable|array',
            'behaviors' => 'nullable|array',
            'custom_audiences' => 'nullable|array',
            'lookalike_audiences' => 'nullable|array',
            'excluded_audiences' => 'nullable|array',
            'placements' => 'nullable|array',
            'automatic_placements' => 'nullable|boolean',
            'device_platforms' => 'nullable|array',
            'publisher_platforms' => 'nullable|array',
            'platform_settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return $this->validationError($validator->errors());
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $adSet->update($validator->validated());

            if ($request->wantsJson()) {
                return $this->success($adSet->fresh(), 'Ad set updated successfully');
            }

            return redirect()
                ->route('org.campaigns.ad-sets.show', [$org, $campaignId, $adSetId])
                ->with('success', __('campaignadset.updated_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to update ad set: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to update ad set'])->withInput();
        }
    }

    /**
     * Remove the specified ad set from storage.
     */
    public function destroy(Request $request, string $org, string $campaignId, string $adSetId)
    {
        $adSet = CampaignAdSet::where('ad_set_id', $adSetId)
            ->where('campaign_id', $campaignId)
            ->firstOrFail();

        try {
            $adSet->delete();

            if ($request->wantsJson()) {
                return $this->deleted('Ad set deleted successfully');
            }

            return redirect()
                ->route('org.campaigns.ad-sets.index', [$org, $campaignId])
                ->with('success', __('campaignadset.deleted_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to delete ad set: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to delete ad set']);
        }
    }

    /**
     * Duplicate an ad set.
     */
    public function duplicate(Request $request, string $org, string $campaignId, string $adSetId)
    {
        $adSet = CampaignAdSet::where('ad_set_id', $adSetId)
            ->where('campaign_id', $campaignId)
            ->firstOrFail();

        try {
            $newAdSet = $adSet->replicate();
            $newAdSet->name = $adSet->name . ' (Copy)';
            $newAdSet->status = CampaignAdSet::STATUS_DRAFT;
            $newAdSet->external_ad_set_id = null;
            $newAdSet->sync_status = 'pending';
            $newAdSet->last_synced_at = null;
            $newAdSet->created_by = auth()->id();
            $newAdSet->save();

            if ($request->wantsJson()) {
                return $this->created($newAdSet, 'Ad set duplicated successfully');
            }

            return redirect()
                ->route('org.campaigns.ad-sets.edit', [$org, $campaignId, $newAdSet->ad_set_id])
                ->with('success', __('campaignadset.ad_set_duplicated_successfully'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to duplicate ad set: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to duplicate ad set']);
        }
    }

    /**
     * Update ad set status (quick action).
     */
    public function updateStatus(Request $request, string $org, string $campaignId, string $adSetId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,active,paused,completed,archived',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $adSet = CampaignAdSet::where('ad_set_id', $adSetId)
            ->where('campaign_id', $campaignId)
            ->firstOrFail();

        try {
            $adSet->update(['status' => $request->status]);

            if ($request->wantsJson()) {
                return $this->success($adSet->fresh(), 'Ad set status updated successfully');
            }

            return back()->with('success', __('campaignadset.updated_success'));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return $this->serverError('Failed to update status: ' . $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to update status']);
        }
    }

    /**
     * Get audience targeting options for a platform.
     * Returns predefined options that don't require API credentials.
     */
    protected function getAudienceOptionsForPlatform(string $platform): array
    {
        // Demographics options (static, no API needed)
        $demographics = match ($platform) {
            'meta' => [
                'age_ranges' => [
                    ['min' => 13, 'max' => 17, 'label' => '13-17'],
                    ['min' => 18, 'max' => 24, 'label' => '18-24'],
                    ['min' => 25, 'max' => 34, 'label' => '25-34'],
                    ['min' => 35, 'max' => 44, 'label' => '35-44'],
                    ['min' => 45, 'max' => 54, 'label' => '45-54'],
                    ['min' => 55, 'max' => 64, 'label' => '55-64'],
                    ['min' => 65, 'max' => null, 'label' => '65+'],
                ],
                'genders' => [
                    ['id' => 1, 'name' => 'Male'],
                    ['id' => 2, 'name' => 'Female'],
                ],
                'relationship_statuses' => [
                    ['id' => 1, 'name' => 'Single'],
                    ['id' => 2, 'name' => 'In a relationship'],
                    ['id' => 3, 'name' => 'Married'],
                    ['id' => 4, 'name' => 'Engaged'],
                ],
                'education_levels' => [
                    ['id' => 'HIGH_SCHOOL', 'name' => 'High school'],
                    ['id' => 'SOME_COLLEGE', 'name' => 'Some college'],
                    ['id' => 'IN_COLLEGE', 'name' => 'In college'],
                    ['id' => 'COLLEGE_GRAD', 'name' => 'College grad'],
                    ['id' => 'MASTER_DEGREE', 'name' => 'Master degree'],
                    ['id' => 'DOCTORATE', 'name' => 'Doctorate degree'],
                ],
            ],
            'google' => [
                'age_ranges' => [
                    ['id' => 'AGE_RANGE_18_24', 'label' => '18-24'],
                    ['id' => 'AGE_RANGE_25_34', 'label' => '25-34'],
                    ['id' => 'AGE_RANGE_35_44', 'label' => '35-44'],
                    ['id' => 'AGE_RANGE_45_54', 'label' => '45-54'],
                    ['id' => 'AGE_RANGE_55_64', 'label' => '55-64'],
                    ['id' => 'AGE_RANGE_65_UP', 'label' => '65+'],
                ],
                'genders' => [
                    ['id' => 'MALE', 'name' => 'Male'],
                    ['id' => 'FEMALE', 'name' => 'Female'],
                ],
                'parental_status' => [
                    ['id' => 'PARENT', 'name' => 'Parent'],
                    ['id' => 'NOT_A_PARENT', 'name' => 'Not a Parent'],
                ],
                'household_income' => [
                    ['id' => 'TOP_10_PERCENT', 'name' => 'Top 10%'],
                    ['id' => '11_TO_20_PERCENT', 'name' => '11-20%'],
                    ['id' => '21_TO_30_PERCENT', 'name' => '21-30%'],
                    ['id' => '31_TO_40_PERCENT', 'name' => '31-40%'],
                    ['id' => '41_TO_50_PERCENT', 'name' => '41-50%'],
                    ['id' => 'LOWER_50_PERCENT', 'name' => 'Lower 50%'],
                ],
            ],
            'tiktok' => [
                'age_ranges' => [
                    ['id' => 'AGE_13_17', 'label' => '13-17'],
                    ['id' => 'AGE_18_24', 'label' => '18-24'],
                    ['id' => 'AGE_25_34', 'label' => '25-34'],
                    ['id' => 'AGE_35_44', 'label' => '35-44'],
                    ['id' => 'AGE_45_54', 'label' => '45-54'],
                    ['id' => 'AGE_55_PLUS', 'label' => '55+'],
                ],
                'genders' => [
                    ['id' => 'MALE', 'name' => 'Male'],
                    ['id' => 'FEMALE', 'name' => 'Female'],
                ],
            ],
            'linkedin' => [
                'age_ranges' => [
                    ['id' => '18-24', 'label' => '18-24'],
                    ['id' => '25-34', 'label' => '25-34'],
                    ['id' => '35-54', 'label' => '35-54'],
                    ['id' => '55+', 'label' => '55+'],
                ],
                'genders' => [
                    ['id' => 'MALE', 'name' => 'Male'],
                    ['id' => 'FEMALE', 'name' => 'Female'],
                ],
                'seniority' => [
                    ['id' => 'ENTRY', 'name' => 'Entry'],
                    ['id' => 'SENIOR', 'name' => 'Senior'],
                    ['id' => 'MANAGER', 'name' => 'Manager'],
                    ['id' => 'DIRECTOR', 'name' => 'Director'],
                    ['id' => 'VP', 'name' => 'VP'],
                    ['id' => 'CXO', 'name' => 'CXO'],
                ],
                'company_sizes' => [
                    ['id' => '1', 'name' => '1'],
                    ['id' => '2-10', 'name' => '2-10'],
                    ['id' => '11-50', 'name' => '11-50'],
                    ['id' => '51-200', 'name' => '51-200'],
                    ['id' => '201-500', 'name' => '201-500'],
                    ['id' => '501-1000', 'name' => '501-1000'],
                    ['id' => '1001-5000', 'name' => '1001-5000'],
                    ['id' => '5001+', 'name' => '5001+'],
                ],
                'industries' => [
                    ['id' => 'technology', 'name' => 'Technology'],
                    ['id' => 'finance', 'name' => 'Finance'],
                    ['id' => 'healthcare', 'name' => 'Healthcare'],
                    ['id' => 'education', 'name' => 'Education'],
                    ['id' => 'manufacturing', 'name' => 'Manufacturing'],
                    ['id' => 'retail', 'name' => 'Retail'],
                    ['id' => 'marketing', 'name' => 'Marketing & Advertising'],
                    ['id' => 'media', 'name' => 'Media & Entertainment'],
                    ['id' => 'real_estate', 'name' => 'Real Estate'],
                    ['id' => 'legal', 'name' => 'Legal Services'],
                ],
            ],
            default => [
                'age_ranges' => [
                    ['min' => 18, 'max' => 24, 'label' => '18-24'],
                    ['min' => 25, 'max' => 34, 'label' => '25-34'],
                    ['min' => 35, 'max' => 44, 'label' => '35-44'],
                    ['min' => 45, 'max' => 54, 'label' => '45-54'],
                    ['min' => 55, 'max' => 65, 'label' => '55-65'],
                    ['min' => 65, 'max' => null, 'label' => '65+'],
                ],
                'genders' => [
                    ['id' => 'MALE', 'name' => 'Male'],
                    ['id' => 'FEMALE', 'name' => 'Female'],
                ],
            ],
        };

        // Interest targeting options (commonly used categories)
        $interests = match ($platform) {
            'meta' => [
                ['id' => '6003139266461', 'name' => 'Technology', 'category' => 'Interest'],
                ['id' => '6003312784231', 'name' => 'Business', 'category' => 'Interest'],
                ['id' => '6003020834693', 'name' => 'Fitness and wellness', 'category' => 'Interest'],
                ['id' => '6003107902433', 'name' => 'Shopping', 'category' => 'Interest'],
                ['id' => '6002839660079', 'name' => 'Travel', 'category' => 'Interest'],
                ['id' => '6003348604891', 'name' => 'Entertainment', 'category' => 'Interest'],
                ['id' => '6003397425735', 'name' => 'Food and drink', 'category' => 'Interest'],
                ['id' => '6003355857769', 'name' => 'Sports', 'category' => 'Interest'],
                ['id' => '6003315347227', 'name' => 'Fashion', 'category' => 'Interest'],
                ['id' => '6003299098834', 'name' => 'Home and garden', 'category' => 'Interest'],
                ['id' => '6003237657449', 'name' => 'Gaming', 'category' => 'Interest'],
                ['id' => '6003277229371', 'name' => 'Music', 'category' => 'Interest'],
                ['id' => '6003384248805', 'name' => 'Education', 'category' => 'Interest'],
                ['id' => '6003317609399', 'name' => 'Automotive', 'category' => 'Interest'],
                ['id' => '6003382981177', 'name' => 'Beauty', 'category' => 'Interest'],
            ],
            'google' => [
                ['id' => 'affinity_auto', 'name' => 'Auto Enthusiasts', 'category' => 'Affinity'],
                ['id' => 'affinity_beauty', 'name' => 'Beauty Mavens', 'category' => 'Affinity'],
                ['id' => 'affinity_business', 'name' => 'Business Professionals', 'category' => 'Affinity'],
                ['id' => 'affinity_cooking', 'name' => 'Cooking Enthusiasts', 'category' => 'Affinity'],
                ['id' => 'affinity_diy', 'name' => 'DIY Enthusiasts', 'category' => 'Affinity'],
                ['id' => 'affinity_fashion', 'name' => 'Fashionistas', 'category' => 'Affinity'],
                ['id' => 'affinity_fitness', 'name' => 'Health & Fitness Buffs', 'category' => 'Affinity'],
                ['id' => 'affinity_foodies', 'name' => 'Foodies', 'category' => 'Affinity'],
                ['id' => 'affinity_gamers', 'name' => 'Gamers', 'category' => 'Affinity'],
                ['id' => 'affinity_music', 'name' => 'Music Lovers', 'category' => 'Affinity'],
                ['id' => 'affinity_outdoor', 'name' => 'Outdoor Enthusiasts', 'category' => 'Affinity'],
                ['id' => 'affinity_pet', 'name' => 'Pet Lovers', 'category' => 'Affinity'],
                ['id' => 'affinity_sports', 'name' => 'Sports Fans', 'category' => 'Affinity'],
                ['id' => 'affinity_tech', 'name' => 'Technophiles', 'category' => 'Affinity'],
                ['id' => 'affinity_travel', 'name' => 'Travel Buffs', 'category' => 'Affinity'],
            ],
            'tiktok' => [
                ['id' => 'tech_gadgets', 'name' => 'Tech & Gadgets', 'category' => 'Interest'],
                ['id' => 'beauty_personal_care', 'name' => 'Beauty & Personal Care', 'category' => 'Interest'],
                ['id' => 'food_beverage', 'name' => 'Food & Beverage', 'category' => 'Interest'],
                ['id' => 'gaming', 'name' => 'Gaming', 'category' => 'Interest'],
                ['id' => 'fashion_accessories', 'name' => 'Fashion & Accessories', 'category' => 'Interest'],
                ['id' => 'sports_outdoors', 'name' => 'Sports & Outdoors', 'category' => 'Interest'],
                ['id' => 'travel', 'name' => 'Travel', 'category' => 'Interest'],
                ['id' => 'news_entertainment', 'name' => 'News & Entertainment', 'category' => 'Interest'],
                ['id' => 'education', 'name' => 'Education', 'category' => 'Interest'],
                ['id' => 'pets', 'name' => 'Pets', 'category' => 'Interest'],
            ],
            'linkedin' => [
                ['id' => 'technology', 'name' => 'Technology', 'category' => 'Interest'],
                ['id' => 'marketing', 'name' => 'Marketing', 'category' => 'Interest'],
                ['id' => 'finance', 'name' => 'Finance', 'category' => 'Interest'],
                ['id' => 'healthcare', 'name' => 'Healthcare', 'category' => 'Interest'],
                ['id' => 'education', 'name' => 'Education', 'category' => 'Interest'],
                ['id' => 'sales', 'name' => 'Sales', 'category' => 'Interest'],
                ['id' => 'engineering', 'name' => 'Engineering', 'category' => 'Interest'],
                ['id' => 'hr', 'name' => 'Human Resources', 'category' => 'Interest'],
                ['id' => 'entrepreneurship', 'name' => 'Entrepreneurship', 'category' => 'Interest'],
                ['id' => 'leadership', 'name' => 'Leadership', 'category' => 'Interest'],
            ],
            default => [
                ['id' => 'technology', 'name' => 'Technology', 'category' => 'Interest'],
                ['id' => 'business', 'name' => 'Business', 'category' => 'Interest'],
                ['id' => 'entertainment', 'name' => 'Entertainment', 'category' => 'Interest'],
                ['id' => 'sports', 'name' => 'Sports', 'category' => 'Interest'],
                ['id' => 'lifestyle', 'name' => 'Lifestyle', 'category' => 'Interest'],
            ],
        };

        // Behavior targeting options
        $behaviors = match ($platform) {
            'meta' => [
                ['id' => 'engaged_shoppers', 'name' => 'Engaged Shoppers', 'category' => 'Purchase Behavior'],
                ['id' => 'frequent_travelers', 'name' => 'Frequent Travelers', 'category' => 'Travel'],
                ['id' => 'small_business_owners', 'name' => 'Small Business Owners', 'category' => 'Business'],
                ['id' => 'technology_early_adopters', 'name' => 'Technology Early Adopters', 'category' => 'Technology'],
                ['id' => 'mobile_gamers', 'name' => 'Mobile Gamers', 'category' => 'Digital Activities'],
                ['id' => 'online_spenders', 'name' => 'Online Spenders', 'category' => 'Purchase Behavior'],
                ['id' => 'fb_page_admins', 'name' => 'Facebook Page Admins', 'category' => 'Digital Activities'],
                ['id' => 'recent_home_buyers', 'name' => 'Recent Home Buyers', 'category' => 'Life Events'],
                ['id' => 'recent_movers', 'name' => 'Recent Movers', 'category' => 'Life Events'],
                ['id' => 'upcoming_anniversary', 'name' => 'Upcoming Anniversary', 'category' => 'Life Events'],
            ],
            'google' => [
                ['id' => 'inmarket_auto', 'name' => 'Autos & Vehicles', 'category' => 'In-Market'],
                ['id' => 'inmarket_baby', 'name' => 'Baby & Children Products', 'category' => 'In-Market'],
                ['id' => 'inmarket_beauty', 'name' => 'Beauty Products & Services', 'category' => 'In-Market'],
                ['id' => 'inmarket_business', 'name' => 'Business Services', 'category' => 'In-Market'],
                ['id' => 'inmarket_computers', 'name' => 'Computers & Peripherals', 'category' => 'In-Market'],
                ['id' => 'inmarket_consumer', 'name' => 'Consumer Electronics', 'category' => 'In-Market'],
                ['id' => 'inmarket_education', 'name' => 'Education', 'category' => 'In-Market'],
                ['id' => 'inmarket_employment', 'name' => 'Employment', 'category' => 'In-Market'],
                ['id' => 'inmarket_finance', 'name' => 'Financial Services', 'category' => 'In-Market'],
                ['id' => 'inmarket_home', 'name' => 'Home & Garden', 'category' => 'In-Market'],
                ['id' => 'inmarket_realestate', 'name' => 'Real Estate', 'category' => 'In-Market'],
                ['id' => 'inmarket_software', 'name' => 'Software', 'category' => 'In-Market'],
                ['id' => 'inmarket_travel', 'name' => 'Travel', 'category' => 'In-Market'],
            ],
            'tiktok' => [
                ['id' => 'video_watchers', 'name' => 'Video Watchers', 'category' => 'Engagement'],
                ['id' => 'commenters', 'name' => 'Active Commenters', 'category' => 'Engagement'],
                ['id' => 'sharers', 'name' => 'Content Sharers', 'category' => 'Engagement'],
                ['id' => 'creators', 'name' => 'Content Creators', 'category' => 'Activity'],
                ['id' => 'live_viewers', 'name' => 'Live Stream Viewers', 'category' => 'Engagement'],
                ['id' => 'shoppers', 'name' => 'TikTok Shoppers', 'category' => 'Commerce'],
            ],
            default => [],
        };

        return [
            'demographics' => $demographics,
            'interests' => $interests,
            'behaviors' => $behaviors,
            'platform' => $platform,
            // Note: Custom audiences and lookalike audiences require API credentials
            // and should be fetched dynamically via AJAX when ad account is connected
            'custom_audiences' => [],
            'lookalike_audiences' => [],
        ];
    }
}
