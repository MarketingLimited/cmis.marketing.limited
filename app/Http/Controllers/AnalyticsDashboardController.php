<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * AnalyticsDashboardController
 *
 * Provides Hootsuite-style dashboard analytics for social media
 * Implements Sprint 3.1: Analytics Dashboard
 *
 * Features:
 * - Account-level analytics with period-over-period comparison
 * - Organization-wide overview
 * - Content performance analysis
 * - Platform comparison
 * - Real-time metrics with caching
 */
class AnalyticsDashboardController extends Controller
{
    use ApiResponse;

    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->middleware('auth:sanctum');
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get account-level dashboard
     *
     * GET /api/orgs/{org_id}/analytics/dashboard/account/{social_account_id}?start_date=2025-01-01&end_date=2025-01-31&period=daily
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function accountDashboard(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'period' => 'nullable|in:hourly,daily,weekly,monthly'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'period' => $request->input('period', 'daily')
        ];

        try {
            $dashboard = $this->dashboardService->getAccountDashboard($socialAccountId, $filters);

            return $this->success($dashboard
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get organization-wide overview
     *
     * GET /api/orgs/{org_id}/analytics/dashboard/overview?start_date=2025-01-01&end_date=2025-01-31
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function orgOverview(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date')
        ];

        try {
            $overview = $this->dashboardService->getOrgOverview($orgId, $filters);

            return $this->success($overview
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load organization overview',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get content performance analysis
     *
     * GET /api/orgs/{org_id}/analytics/dashboard/account/{social_account_id}/content?start_date=2025-01-01&end_date=2025-01-31
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function contentPerformance(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date')
        ];

        try {
            $performance = $this->dashboardService->getContentPerformance($socialAccountId, $filters);

            return $this->success($performance
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load content performance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get platform comparison
     *
     * GET /api/orgs/{org_id}/analytics/dashboard/platforms?start_date=2025-01-01&end_date=2025-01-31
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function platformComparison(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date')
        ];

        try {
            $comparison = $this->dashboardService->getPlatformComparison($orgId, $filters);

            return $this->success($comparison
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load platform comparison',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time dashboard snapshot
     *
     * GET /api/orgs/{org_id}/analytics/dashboard/snapshot
     *
     * Returns current day metrics without caching for real-time updates
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function snapshot(string $orgId, Request $request): JsonResponse
    {
        try {
            $today = now()->toDateString();

            $filters = [
                'start_date' => $today,
                'end_date' => $today
            ];

            $overview = $this->dashboardService->getOrgOverview($orgId, $filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'timestamp' => now()->toIso8601String(),
                    'metrics' => $overview['summary'] ?? [],
                    'top_accounts' => $overview['top_performing_accounts'] ?? []
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load snapshot',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get engagement trends over time
     *
     * GET /api/orgs/{org_id}/analytics/dashboard/account/{social_account_id}/trends?start_date=2025-01-01&end_date=2025-01-31&metric=engagement_rate
     *
     * @param string $orgId
     * @param string $socialAccountId
     * @param Request $request
     * @return JsonResponse
     */
    public function trends(string $orgId, string $socialAccountId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'metric' => 'nullable|in:engagement_rate,followers,reach,impressions,posts'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $filters = [
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'period' => 'daily'
        ];

        try {
            $dashboard = $this->dashboardService->getAccountDashboard($socialAccountId, $filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'trends' => $dashboard['trends'] ?? [],
                    'metric' => $request->input('metric', 'engagement_rate'),
                    'period' => $dashboard['period'] ?? []
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load trends',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
