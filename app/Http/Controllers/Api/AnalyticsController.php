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
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $orgId = auth()->user()->org_id;

            $summary = $this->analyticsService->getUsageSummary(
                $orgId,
                $request->input('start_date'),
                $request->input('end_date')
            );

            return $this->success(['summary' => $summary], 'Usage summary retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch usage summary: ' . $e->getMessage());
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
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $orgId = auth()->user()->org_id;
            $days = $request->input('days', 30);

            $trend = $this->analyticsService->getDailyTrend($orgId, $days);

            return $this->success([
                'trend' => $trend,
                'period' => $days
            ], 'Daily trend retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch daily trend: ' . $e->getMessage());
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

            return $this->success(['quota' => $quota], 'Quota status retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch quota status: ' . $e->getMessage());
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
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $orgId = auth()->user()->org_id;

            $costs = $this->analyticsService->getCostByCampaign(
                $orgId,
                $request->input('start_date'),
                $request->input('end_date')
            );

            return $this->success(['campaigns' => $costs], 'Campaign costs retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch campaign costs: ' . $e->getMessage());
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
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $orgId = auth()->user()->org_id;

            $stats = $this->analyticsService->getGeneratedMediaStats(
                $orgId,
                $request->input('start_date'),
                $request->input('end_date')
            );

            return $this->success(['stats' => $stats], 'Media statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch media statistics: ' . $e->getMessage());
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
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $orgId = auth()->user()->org_id;
            $limit = $request->input('limit', 10);

            $media = $this->analyticsService->getTopPerformingMedia($orgId, $limit);

            return $this->success(['media' => $media], 'Top performing media retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch top performing media: ' . $e->getMessage());
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
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $orgId = auth()->user()->org_id;
            $months = $request->input('months', 6);

            $comparison = $this->analyticsService->getMonthlyCostComparison($orgId, $months);

            return $this->success([
                'comparison' => $comparison,
                'period' => $months
            ], 'Monthly comparison retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch monthly comparison: ' . $e->getMessage());
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

            return $this->success(['dashboard' => $dashboard], 'Dashboard data retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch dashboard data: ' . $e->getMessage());
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

            return $this->success([
                'alerts' => $alerts,
                'count' => count($alerts)
            ], 'Quota alerts retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch quota alerts: ' . $e->getMessage());
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
                return $this->validationError($validator->errors(), 'Validation failed');
            }

            $orgId = auth()->user()->org_id;

            $data = $this->analyticsService->getExportData(
                $orgId,
                $request->input('type'),
                $request->input('start_date'),
                $request->input('end_date')
            );

            return $this->success([
                'data' => $data,
                'export_type' => $request->input('type')
            ], 'Data exported successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to export data: ' . $e->getMessage());
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

            return $this->success([], 'Analytics cache cleared successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to clear cache: ' . $e->getMessage());
        }
    }
}
