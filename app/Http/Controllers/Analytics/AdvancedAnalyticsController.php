<?php

namespace App\Http\Controllers\Analytics;
use App\Http\Controllers\Concerns\ApiResponse;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AIInsightsService;
use App\Services\Analytics\DashboardCustomizationService;
use App\Services\Analytics\ReportGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Advanced Analytics Controller (Phase 11)
 *
 * Provides API endpoints for advanced analytics features:
 * - AI-powered insights and recommendations
 * - Report generation and export
 * - Dashboard customization
 * - Campaign comparison
 */
class AdvancedAnalyticsController extends Controller
{
    use ApiResponse;

    protected AIInsightsService $insightsService;
    protected ReportGeneratorService $reportService;
    protected DashboardCustomizationService $customizationService;

    public function __construct(
        AIInsightsService $insightsService,
        ReportGeneratorService $reportService,
        DashboardCustomizationService $customizationService
    ) {
        $this->middleware('auth:sanctum');
        $this->insightsService = $insightsService;
        $this->reportService = $reportService;
        $this->customizationService = $customizationService;
    }

    /**
     * Get AI-powered insights for campaign
     *
     * GET /api/orgs/{org_id}/analytics/campaigns/{campaign_id}/insights
     *
     * @param string $orgId Organization UUID
     * @param string $campaignId Campaign UUID
     * @param Request $request
     * @return JsonResponse
     */
    public function getCampaignInsights(string $orgId, string $campaignId, Request $request): JsonResponse
    {
        try {
            $insights = $this->insightsService->generateCampaignInsights($campaignId, [
                'days' => $request->input('days', 30)
            ]);

            return $this->success(['insights' => $insights], 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate and export campaign report
     *
     * POST /api/orgs/{org_id}/analytics/campaigns/{campaign_id}/export
     *
     * @param string $orgId Organization UUID
     * @param string $campaignId Campaign UUID
     * @param Request $request
     * @return JsonResponse
     */
    public function exportCampaignReport(string $orgId, string $campaignId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'format' => 'required|in:pdf,xlsx,csv,json',
            'include_insights' => 'boolean',
            'date_range' => 'array',
            'date_range.start' => 'date',
            'date_range.end' => 'date|after_or_equal:date_range.start'
        ]);

        try {
            $report = $this->reportService->generateCampaignReport($campaignId, $validated);

            return $this->success($report, 'Retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate organization-wide report
     *
     * POST /api/orgs/{org_id}/analytics/export
     *
     * @param string $orgId Organization UUID
     * @param Request $request
     * @return JsonResponse
     */
    public function exportOrganizationReport(string $orgId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'format' => 'required|in:pdf,xlsx,csv,json',
            'date_range' => 'array',
            'date_range.start' => 'date',
            'date_range.end' => 'date|after_or_equal:date_range.start'
        ]);

        try {
            $report = $this->reportService->generateOrganizationReport($orgId, $validated);

            return $this->success($report, 'Retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate campaign comparison report
     *
     * POST /api/orgs/{org_id}/analytics/compare
     *
     * @param string $orgId Organization UUID
     * @param Request $request
     * @return JsonResponse
     */
    public function compareCampaigns(string $orgId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaign_ids' => 'required|array|min:2',
            'campaign_ids.*' => 'uuid',
            'format' => 'sometimes|in:pdf,xlsx,csv,json',
            'date_range' => 'array'
        ]);

        try {
            $report = $this->reportService->generateComparisonReport(
                $validated['campaign_ids'],
                [
                    'format' => $validated['format'] ?? 'json',
                    'date_range' => $validated['date_range'] ?? null
                ]
            );

            return $this->success($report, 'Retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get user's dashboard configuration
     *
     * GET /api/user/dashboard/{dashboard_type}/config
     *
     * @param string $dashboardType Dashboard type
     * @param Request $request
     * @return JsonResponse
     */
    public function getDashboardConfig(string $dashboardType, Request $request): JsonResponse
    {
        try {
            $config = $this->customizationService->getUserDashboard(
                $request->user()->user_id,
                $dashboardType
            );

            return $this->success(['configuration' => $config], 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Save user's dashboard configuration
     *
     * PUT /api/user/dashboard/{dashboard_type}/config
     *
     * @param string $dashboardType Dashboard type
     * @param Request $request
     * @return JsonResponse
     */
    public function saveDashboardConfig(string $dashboardType, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'configuration' => 'required|array'
        ]);

        try {
            $success = $this->customizationService->saveDashboard(
                $request->user()->user_id,
                $dashboardType,
                $validated['configuration']
            );

            return $this->success(['success' => $success,
                'message' => 'Dashboard configuration saved successfully'
            ], 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get saved filters
     *
     * GET /api/user/filters/{context}
     *
     * @param string $context Filter context
     * @param Request $request
     * @return JsonResponse
     */
    public function getSavedFilters(string $context, Request $request): JsonResponse
    {
        try {
            $filters = $this->customizationService->getSavedFilters(
                $request->user()->user_id,
                $context
            );

            return $this->success(['filters' => $filters], 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Save filter preset
     *
     * POST /api/user/filters/{context}
     *
     * @param string $context Filter context
     * @param Request $request
     * @return JsonResponse
     */
    public function saveFilter(string $context, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'filters' => 'required|array'
        ]);

        try {
            $filterId = $this->customizationService->saveFilter(
                $request->user()->user_id,
                $validated['name'],
                $context,
                $validated['filters']
            );

            return $this->success(['filter_id' => $filterId,
                'message' => 'Filter saved successfully'], 'Operation completed successfully');
        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }
}
