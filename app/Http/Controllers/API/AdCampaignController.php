<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
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
                return $this->error($result['error'], 400);
            }

            return $this->created([
                'campaign' => $result['campaign'],
                'platform_campaign_id' => $result['external_id'],
            ], 'تم إنشاء الحملة الإعلانية بنجاح');
        } catch (\Exception $e) {
            Log::error("Failed to create campaign: {$e->getMessage()}");
            return $this->serverError('فشل إنشاء الحملة الإعلانية: ' . $e->getMessage());
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

            $campaign = AdCampaign::where('id', $campaignId)
                ->where('org_id', $orgId)
                ->firstOrFail();

            $integration = Integration::where('integration_id', $campaign->integration_id)
                ->firstOrFail();

            // Update via service
            $result = $this->adCampaignService->updateCampaign($campaign, $integration, $validated);

            if (!$result['success']) {
                return $this->error($result['error'], 400);
            }

            return $this->success([
                'campaign' => $result['campaign'],
            ], 'تم تحديث الحملة الإعلانية بنجاح');
        } catch (\Exception $e) {
            Log::error("Failed to update campaign: {$e->getMessage()}");
            return $this->serverError('فشل تحديث الحملة: ' . $e->getMessage());
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
                ->with(['integration'])
                ->where('org_id', $orgId);

            if ($platform) {
                $query->where('provider', $platform);
            }

            if ($status) {
                $query->where('status', $status);
            }

            $campaigns = $query->orderBy('created_at', 'desc')->get();

            return $this->success([
                'campaigns' => $campaigns,
                'total' => $campaigns->count(),
            ], 'Campaigns retrieved successfully');
        } catch (\Exception $e) {
            Log::error("Failed to get campaigns: {$e->getMessage()}");
            return $this->serverError('فشل جلب الحملات: ' . $e->getMessage());
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

            $campaign = AdCampaign::with(['integration', 'adSets', 'metrics'])
                ->where('id', $campaignId)
                ->where('org_id', $orgId)
                ->firstOrFail();

            return $this->success([
                'campaign' => $campaign,
            ], 'Campaign retrieved successfully');
        } catch (\Exception $e) {
            Log::error("Failed to get campaign: {$e->getMessage()}");
            return $this->notFound('لم يتم العثور على الحملة');
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

            $campaign = AdCampaign::where('id', $campaignId)
                ->where('org_id', $orgId)
                ->firstOrFail();

            $integration = Integration::where('integration_id', $campaign->integration_id)
                ->where('platform', $campaign->provider)
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

            return $this->success([
                'campaign_id' => $campaignId,
                'platform' => $campaign->provider,
                'live_metrics' => $liveMetrics,
                'stored_metrics' => $storedMetrics,
            ], 'Campaign metrics retrieved successfully');
        } catch (\Exception $e) {
            Log::error("Failed to get campaign metrics: {$e->getMessage()}");
            return $this->serverError('فشل جلب مقاييس الحملة: ' . $e->getMessage());
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

            if (!$result['success']) {
                return $this->error('فشلت مزامنة الحملات', 400);
            }

            return $this->success([
                'synced_count' => $result['synced_count'] ?? 0,
            ], 'تمت مزامنة الحملات بنجاح');
        } catch (\Exception $e) {
            Log::error("Failed to sync campaigns: {$e->getMessage()}");
            return $this->serverError('فشلت مزامنة الحملات: ' . $e->getMessage());
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

            $campaign = AdCampaign::where('id', $campaignId)
                ->where('org_id', $orgId)
                ->firstOrFail();

            $integration = Integration::where('integration_id', $campaign->integration_id)
                ->where('platform', $campaign->provider)
                ->firstOrFail();

            // Update via service
            $result = $this->adCampaignService->updateCampaign($campaign, $integration, [
                'status' => $status,
            ]);

            if (!$result['success']) {
                return $this->error($result['error'], 400);
            }

            $statusMessages = [
                'ACTIVE' => 'تم تفعيل الحملة بنجاح',
                'PAUSED' => 'تم إيقاف الحملة مؤقتاً بنجاح',
                'DELETED' => 'تم حذف الحملة بنجاح',
            ];

            return $this->success([
                'campaign' => $result['campaign'],
            ], $statusMessages[$status] ?? 'تم تحديث الحملة بنجاح');
        } catch (\Exception $e) {
            Log::error("Failed to update campaign status: {$e->getMessage()}");
            return $this->serverError('فشل تحديث حالة الحملة: ' . $e->getMessage());
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

        return $this->success([
            'platform' => $platform,
            'objectives' => $objectives[$platform] ?? [],
        ], 'Campaign objectives retrieved successfully');
    }
}
