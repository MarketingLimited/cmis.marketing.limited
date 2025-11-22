<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Core\Integration;
use App\Models\AdPlatform\AdCampaign;
use App\Services\AdCampaigns\AdCampaignManagerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller for managing ad campaigns across all platforms
 * Supports: Meta, Google Ads, TikTok, Snapchat, Twitter, LinkedIn
 */
class AdCampaignController extends Controller
{
    use ApiResponse;

    protected AdCampaignManagerService $adCampaignService;

    public function __construct(AdCampaignManagerService $adCampaignService)
    {
        $this->adCampaignService = $adCampaignService;
    }

    /**
     * Create a new ad campaign
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createCampaign(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'integration_id' => 'required|string|exists:cmis.integrations,integration_id',
                'campaign_name' => 'required|string',
                'objective' => 'required|string',
                'status' => 'nullable|string|in:ACTIVE,PAUSED,DRAFT',
                'daily_budget' => 'nullable|numeric',
                'lifetime_budget' => 'nullable|numeric',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after:start_date',
                'targeting' => 'nullable|array',
                'placements' => 'nullable|array',
                'optimization_goal' => 'nullable|string',
            ]);

            $orgId = $request->user()->org_id;

            $integration = Integration::where('integration_id', $validated['integration_id'])
                ->where('org_id', $orgId)
                ->where('is_active', true)
                ->firstOrFail();

            // Create campaign via service
            $result = $this->adCampaignService->createCampaign($integration, $validated);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'campaign' => $result['campaign'],
                'platform_campaign_id' => $result['external_id'],
                'message' => 'تم إنشاء الحملة الإعلانية بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create campaign: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => 'فشل إنشاء الحملة الإعلانية: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an ad campaign
     *
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function updateCampaign(string $campaignId, Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'campaign_name' => 'nullable|string',
                'status' => 'nullable|string|in:ACTIVE,PAUSED,DELETED',
                'daily_budget' => 'nullable|numeric',
                'lifetime_budget' => 'nullable|numeric',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'targeting' => 'nullable|array',
            ]);

            $orgId = $request->user()->org_id;

            $campaign = AdCampaign::where('ad_campaign_id', $campaignId)
                ->whereHas('campaign', function ($query) use ($orgId) {
                    $query->where('org_id', $orgId);
                })
                ->firstOrFail();

            $integration = Integration::where('integration_id', $campaign->ad_account_id)
                ->firstOrFail();

            // Update via service
            $result = $this->adCampaignService->updateCampaign($campaign, $integration, $validated);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'campaign' => $result['campaign'],
                'message' => 'تم تحديث الحملة الإعلانية بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update campaign: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => 'فشل تحديث الحملة: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all campaigns for organization
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCampaigns(Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;
            $platform = $request->input('platform');
            $status = $request->input('status');

            $query = AdCampaign::query()
                ->with(['campaign', 'adAccount'])
                ->whereHas('campaign', function ($q) use ($orgId) {
                    $q->where('org_id', $orgId);
                });

            if ($platform) {
                $query->where('platform', $platform);
            }

            if ($status) {
                $query->where('campaign_status', $status);
            }

            $campaigns = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'campaigns' => $campaigns,
                'total' => $campaigns->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get campaigns: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => 'فشل جلب الحملات: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get campaign details
     *
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function getCampaign(string $campaignId, Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;

            $campaign = AdCampaign::with(['campaign', 'adAccount', 'adSets', 'metrics'])
                ->where('ad_campaign_id', $campaignId)
                ->whereHas('campaign', function ($query) use ($orgId) {
                    $query->where('org_id', $orgId);
                })
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'campaign' => $campaign,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get campaign: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => 'لم يتم العثور على الحملة',
            ], 404);
        }
    }

    /**
     * Get campaign metrics/performance
     *
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function getCampaignMetrics(string $campaignId, Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;

            $campaign = AdCampaign::where('ad_campaign_id', $campaignId)
                ->whereHas('campaign', function ($query) use ($orgId) {
                    $query->where('org_id', $orgId);
                })
                ->firstOrFail();

            $integration = Integration::where('account_id', $campaign->ad_account_id)
                ->where('platform', $campaign->platform)
                ->firstOrFail();

            // Get metrics from platform via service
            $liveMetrics = $this->adCampaignService->getCampaignMetrics(
                $campaign,
                $integration,
                $request->all()
            );

            // Get stored metrics
            $storedMetrics = $campaign->metrics()
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get();

            return response()->json([
                'success' => true,
                'campaign_id' => $campaignId,
                'platform' => $campaign->platform,
                'live_metrics' => $liveMetrics,
                'stored_metrics' => $storedMetrics,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to get campaign metrics: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => 'فشل جلب مقاييس الحملة: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync campaigns from platform
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function syncCampaigns(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'integration_id' => 'required|string|exists:cmis.integrations,integration_id',
            ]);

            $orgId = $request->user()->org_id;

            $integration = Integration::where('integration_id', $validated['integration_id'])
                ->where('org_id', $orgId)
                ->where('is_active', true)
                ->firstOrFail();

            $result = $this->adCampaignService->syncCampaigns($integration);

            return response()->json([
                'success' => $result['success'],
                'synced_count' => $result['synced_count'] ?? 0,
                'message' => $result['success']
                    ? 'تمت مزامنة الحملات بنجاح'
                    : 'فشلت مزامنة الحملات',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to sync campaigns: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => 'فشلت مزامنة الحملات: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pause a campaign
     *
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function pauseCampaign(string $campaignId, Request $request): JsonResponse
    {
        return $this->updateCampaignStatus($campaignId, 'PAUSED', $request);
    }

    /**
     * Activate a campaign
     *
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function activateCampaign(string $campaignId, Request $request): JsonResponse
    {
        return $this->updateCampaignStatus($campaignId, 'ACTIVE', $request);
    }

    /**
     * Delete a campaign
     *
     * @param string $campaignId
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteCampaign(string $campaignId, Request $request): JsonResponse
    {
        return $this->updateCampaignStatus($campaignId, 'DELETED', $request);
    }

    /**
     * Update campaign status
     *
     * @param string $campaignId
     * @param string $status
     * @param Request $request
     * @return JsonResponse
     */
    protected function updateCampaignStatus(string $campaignId, string $status, Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;

            $campaign = AdCampaign::where('ad_campaign_id', $campaignId)
                ->whereHas('campaign', function ($query) use ($orgId) {
                    $query->where('org_id', $orgId);
                })
                ->firstOrFail();

            $integration = Integration::where('account_id', $campaign->ad_account_id)
                ->where('platform', $campaign->platform)
                ->firstOrFail();

            // Update via service
            $result = $this->adCampaignService->updateCampaign($campaign, $integration, [
                'campaign_status' => $status,
            ]);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                ], 400);
            }

            $statusMessages = [
                'ACTIVE' => 'تم تفعيل الحملة بنجاح',
                'PAUSED' => 'تم إيقاف الحملة مؤقتاً بنجاح',
                'DELETED' => 'تم حذف الحملة بنجاح',
            ];

            return response()->json([
                'success' => true,
                'campaign' => $result['campaign'],
                'message' => $statusMessages[$status] ?? 'تم تحديث الحملة بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update campaign status: {$e->getMessage()}");
            return response()->json([
                'success' => false,
                'error' => 'فشل تحديث حالة الحملة: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get campaign objectives for a platform
     *
     * @param string $platform
     * @return JsonResponse
     */
    public function getCampaignObjectives(string $platform): JsonResponse
    {
        $objectives = [
            'meta' => [
                'OUTCOME_AWARENESS' => 'الوعي بالعلامة التجارية',
                'OUTCOME_ENGAGEMENT' => 'التفاعل',
                'OUTCOME_LEADS' => 'جذب العملاء المحتملين',
                'OUTCOME_SALES' => 'المبيعات والتحويلات',
                'OUTCOME_TRAFFIC' => 'زيارات الموقع',
                'OUTCOME_APP_PROMOTION' => 'الترويج للتطبيق',
            ],
            'google' => [
                'SEARCH' => 'حملة البحث',
                'DISPLAY' => 'الحملة الإعلانية المرئية',
                'SHOPPING' => 'حملة التسوق',
                'VIDEO' => 'حملة الفيديو',
                'SMART' => 'الحملة الذكية',
                'PERFORMANCE_MAX' => 'الأداء الأقصى',
            ],
            'tiktok' => [
                'REACH' => 'الوصول',
                'TRAFFIC' => 'الزيارات',
                'VIDEO_VIEWS' => 'مشاهدات الفيديو',
                'LEAD_GENERATION' => 'جذب العملاء المحتملين',
                'APP_PROMOTION' => 'الترويج للتطبيق',
                'CONVERSIONS' => 'التحويلات',
            ],
            'linkedin' => [
                'BRAND_AWARENESS' => 'الوعي بالعلامة التجارية',
                'WEBSITE_VISITS' => 'زيارات الموقع',
                'ENGAGEMENT' => 'التفاعل',
                'VIDEO_VIEWS' => 'مشاهدات الفيديو',
                'LEAD_GENERATION' => 'جذب العملاء المحتملين',
                'WEBSITE_CONVERSIONS' => 'تحويلات الموقع',
            ],
            'twitter' => [
                'REACH' => 'الوصول',
                'VIDEO_VIEWS' => 'مشاهدات الفيديو',
                'WEBSITE_CLICKS' => 'نقرات الموقع',
                'FOLLOWERS' => 'المتابعون',
                'ENGAGEMENTS' => 'التفاعلات',
                'APP_INSTALLS' => 'تثبيتات التطبيق',
            ],
            'snapchat' => [
                'AWARENESS' => 'الوعي',
                'APP_INSTALLS' => 'تثبيتات التطبيق',
                'DRIVE_TRAFFIC' => 'زيادة الزيارات للموقع',
                'ENGAGEMENT' => 'التفاعل',
                'VIDEO_VIEWS' => 'مشاهدات الفيديو',
                'CONVERSIONS' => 'التحويلات',
            ],
        ];

        return response()->json([
            'success' => true,
            'platform' => $platform,
            'objectives' => $objectives[$platform] ?? [],
        ]);
    }
}
