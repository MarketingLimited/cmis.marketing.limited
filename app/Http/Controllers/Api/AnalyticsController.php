<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AiAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Concerns\ApiResponse;

class AnalyticsController extends Controller
{
    use ApiResponse;

    private AiAnalyticsService $analyticsService;

    public function __construct(AiAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get AI usage summary
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUsageSummary(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date|before_or_equal:today',
                'end_date' => 'nullable|date|after_or_equal:start_date|before_or_equal:today'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $orgId = auth()->user()->org_id;

            $summary = $this->analyticsService->getUsageSummary(
                $orgId,
                $request->input('start_date'),
                $request->input('end_date')
            );

            return response()->json([
                'success' => true,
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch usage summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get daily usage trend
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDailyTrend(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'days' => 'nullable|integer|min:7|max:90'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $orgId = auth()->user()->org_id;
            $days = $request->input('days', 30);

            $trend = $this->analyticsService->getDailyTrend($orgId, $days);

            return response()->json([
                'success' => true,
                'trend' => $trend,
                'period' => $days
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch daily trend',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quota status
     *
     * @return JsonResponse
     */
    public function getQuotaStatus(): JsonResponse
    {
        try {
            $orgId = auth()->user()->org_id;

            $quota = $this->analyticsService->getQuotaStatus($orgId);

            return response()->json([
                'success' => true,
                'quota' => $quota
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch quota status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get cost breakdown by campaign
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCostByCampaign(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date|before_or_equal:today',
                'end_date' => 'nullable|date|after_or_equal:start_date|before_or_equal:today'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $orgId = auth()->user()->org_id;

            $costs = $this->analyticsService->getCostByCampaign(
                $orgId,
                $request->input('start_date'),
                $request->input('end_date')
            );

            return response()->json([
                'success' => true,
                'campaigns' => $costs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch campaign costs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get generated media statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMediaStats(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date|before_or_equal:today',
                'end_date' => 'nullable|date|after_or_equal:start_date|before_or_equal:today'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $orgId = auth()->user()->org_id;

            $stats = $this->analyticsService->getGeneratedMediaStats(
                $orgId,
                $request->input('start_date'),
                $request->input('end_date')
            );

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch media statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top performing generated media
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTopPerformingMedia(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'limit' => 'nullable|integer|min:5|max:50'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $orgId = auth()->user()->org_id;
            $limit = $request->input('limit', 10);

            $media = $this->analyticsService->getTopPerformingMedia($orgId, $limit);

            return response()->json([
                'success' => true,
                'media' => $media
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch top performing media',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly cost comparison
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMonthlyComparison(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'months' => 'nullable|integer|min:3|max:12'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $orgId = auth()->user()->org_id;
            $months = $request->input('months', 6);

            $comparison = $this->analyticsService->getMonthlyCostComparison($orgId, $months);

            return response()->json([
                'success' => true,
                'comparison' => $comparison,
                'period' => $months
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch monthly comparison',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comprehensive dashboard data
     *
     * @return JsonResponse
     */
    public function getDashboard(): JsonResponse
    {
        try {
            $orgId = auth()->user()->org_id;

            $dashboard = $this->analyticsService->getDashboardData($orgId);

            return response()->json([
                'success' => true,
                'dashboard' => $dashboard
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quota alerts
     *
     * @return JsonResponse
     */
    public function getQuotaAlerts(): JsonResponse
    {
        try {
            $orgId = auth()->user()->org_id;

            $alerts = $this->analyticsService->getQuotaAlerts($orgId);

            return response()->json([
                'success' => true,
                'alerts' => $alerts,
                'count' => count($alerts)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch quota alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export analytics data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportData(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:usage,daily_trend,campaigns,media,monthly',
                'start_date' => 'nullable|date|before_or_equal:today',
                'end_date' => 'nullable|date|after_or_equal:start_date|before_or_equal:today'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $orgId = auth()->user()->org_id;

            $data = $this->analyticsService->getExportData(
                $orgId,
                $request->input('type'),
                $request->input('start_date'),
                $request->input('end_date')
            );

            return response()->json([
                'success' => true,
                'data' => $data,
                'export_type' => $request->input('type')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear analytics cache
     *
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        try {
            $orgId = auth()->user()->org_id;

            $this->analyticsService->clearCache($orgId);

            return response()->json([
                'success' => true,
                'message' => 'Analytics cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
