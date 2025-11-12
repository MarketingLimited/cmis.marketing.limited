<?php

namespace App\Http\Controllers;

use App\Services\AdCampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdCampaignController extends Controller
{
    protected $campaignService;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get all ad campaigns for organization
     */
    public function index(Request $request, $orgId)
    {
        $this->campaignService = new AdCampaignService($orgId);

        try {
            $filters = [
                'platform' => $request->input('platform'),
                'status' => $request->input('status'),
            ];

            $campaigns = $this->campaignService->getCampaigns(array_filter($filters));

            return response()->json([
                'success' => true,
                'campaigns' => $campaigns
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get ad campaigns: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل في جلب الحملات الإعلانية'
            ], 500);
        }
    }

    /**
     * Create campaign on Meta (Facebook/Instagram)
     */
    public function createMetaCampaign(Request $request, $orgId)
    {
        $this->campaignService = new AdCampaignService($orgId);

        $validator = Validator::make($request->all(), [
            'campaign_name' => 'required|string|max:255',
            'objective' => 'required|string|in:OUTCOME_AWARENESS,OUTCOME_ENGAGEMENT,OUTCOME_TRAFFIC,OUTCOME_LEADS,OUTCOME_SALES',
            'daily_budget' => 'nullable|numeric|min:1',
            'lifetime_budget' => 'nullable|numeric|min:1',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'status' => 'nullable|string|in:ACTIVE,PAUSED',
            'targeting' => 'required|array',
            'creative' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->campaignService->createMetaCampaign($request->all());

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إنشاء الحملة الإعلانية بنجاح',
                    'campaign' => $result
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create Meta campaign: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل في إنشاء الحملة الإعلانية',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create campaign on Google Ads
     */
    public function createGoogleAdsCampaign(Request $request, $orgId)
    {
        $this->campaignService = new AdCampaignService($orgId);

        $validator = Validator::make($request->all(), [
            'campaign_name' => 'required|string|max:255',
            'campaign_type' => 'required|string|in:SEARCH,DISPLAY,VIDEO,SHOPPING,APP,PERFORMANCE_MAX',
            'budget' => 'required|numeric|min:1',
            'targeting' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->campaignService->createGoogleAdsCampaign($request->all());

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إنشاء حملة Google Ads بنجاح',
                    'campaign' => $result
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create Google Ads campaign: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل في إنشاء حملة Google Ads'
            ], 500);
        }
    }

    /**
     * Create campaign on TikTok
     */
    public function createTikTokAdsCampaign(Request $request, $orgId)
    {
        $this->campaignService = new AdCampaignService($orgId);

        $validator = Validator::make($request->all(), [
            'campaign_name' => 'required|string|max:255',
            'objective' => 'required|string',
            'budget' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->campaignService->createTikTokAdsCampaign($request->all());

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إنشاء حملة TikTok بنجاح',
                    'campaign' => $result
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create TikTok campaign: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل في إنشاء حملة TikTok'
            ], 500);
        }
    }

    /**
     * Create campaign on Snapchat
     */
    public function createSnapchatAdsCampaign(Request $request, $orgId)
    {
        $this->campaignService = new AdCampaignService($orgId);

        $validator = Validator::make($request->all(), [
            'campaign_name' => 'required|string|max:255',
            'objective' => 'required|string',
            'budget' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->campaignService->createSnapchatAdsCampaign($request->all());

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إنشاء حملة Snapchat بنجاح',
                    'campaign' => $result
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create Snapchat campaign: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل في إنشاء حملة Snapchat'
            ], 500);
        }
    }

    /**
     * Update campaign status
     */
    public function updateStatus(Request $request, $orgId, $campaignId)
    {
        $this->campaignService = new AdCampaignService($orgId);

        $validated = $request->validate([
            'status' => 'required|string|in:ACTIVE,PAUSED',
        ]);

        try {
            $result = $this->campaignService->updateCampaignStatus($campaignId, $validated['status']);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث حالة الحملة'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'الحملة غير موجودة'
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update campaign status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل في تحديث حالة الحملة'
            ], 500);
        }
    }

    /**
     * Get campaign metrics
     */
    public function metrics(Request $request, $orgId, $campaignId)
    {
        $this->campaignService = new AdCampaignService($orgId);

        try {
            $metrics = $this->campaignService->getCampaignMetrics($campaignId);

            return response()->json([
                'success' => true,
                'metrics' => $metrics
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get campaign metrics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'فشل في جلب إحصائيات الحملة'
            ], 500);
        }
    }
}
