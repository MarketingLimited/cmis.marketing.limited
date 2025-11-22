<?php

namespace App\Http\Controllers;

use App\Services\ReportGenerationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * ReportsController
 *
 * Handles report generation and scheduling
 * Implements Sprint 3.4: PDF Reports
 *
 * Features:
 * - Performance reports (PDF/JSON/CSV)
 * - AI insights reports
 * - Organization overview reports
 * - Content analysis reports
 * - Report scheduling
 * - Export functionality
 */
class ReportsController extends Controller
{
    use ApiResponse;

    protected ReportGenerationService $reportService;

    public function __construct(ReportGenerationService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Generate performance report
     *
     * POST /api/orgs/{org_id}/reports/performance
     *
     * Request body:
     * {
     *   "account_id": "uuid",
     *   "start_date": "2025-01-01",
     *   "end_date": "2025-01-31",
     *   "format": "pdf|json|csv",
     *   "period": "daily|weekly|monthly"
     * }
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function generatePerformanceReport(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|uuid',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'nullable|in:pdf,json,csv',
            'period' => 'nullable|in:hourly,daily,weekly,monthly',
            'top_posts_limit' => 'nullable|integer|min:5|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $report = $this->reportService->generatePerformanceReport(
                $request->input('account_id'),
                $request->only(['start_date', 'end_date', 'format', 'period', 'top_posts_limit'])
            );

            if (!$report['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate report',
                    'error' => $report['error'] ?? 'Unknown error'
                ], 500);
            }

            return response()->json($report);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate performance report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate AI insights report
     *
     * POST /api/orgs/{org_id}/reports/ai-insights
     *
     * Request body:
     * {
     *   "account_id": "uuid",
     *   "start_date": "2025-01-01",
     *   "end_date": "2025-01-31",
     *   "format": "pdf|json"
     * }
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function generateAIInsightsReport(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|uuid',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'nullable|in:pdf,json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $report = $this->reportService->generateAIInsightsReport(
                $request->input('account_id'),
                $request->only(['start_date', 'end_date', 'format'])
            );

            if (!$report['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate AI insights report',
                    'error' => $report['error'] ?? 'Unknown error'
                ], 500);
            }

            return response()->json($report);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate AI insights report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate organization overview report
     *
     * POST /api/orgs/{org_id}/reports/organization
     *
     * Request body:
     * {
     *   "start_date": "2025-01-01",
     *   "end_date": "2025-01-31",
     *   "format": "pdf|json|csv"
     * }
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function generateOrgReport(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'nullable|in:pdf,json,csv'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $report = $this->reportService->generateOrgReport(
                $orgId,
                $request->only(['start_date', 'end_date', 'format'])
            );

            if (!$report['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate organization report',
                    'error' => $report['error'] ?? 'Unknown error'
                ], 500);
            }

            return response()->json($report);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate organization report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate content analysis report
     *
     * POST /api/orgs/{org_id}/reports/content-analysis
     *
     * Request body:
     * {
     *   "account_id": "uuid",
     *   "start_date": "2025-01-01",
     *   "end_date": "2025-01-31",
     *   "format": "pdf|json",
     *   "hashtag_limit": 30,
     *   "top_posts_limit": 20
     * }
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function generateContentAnalysisReport(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|uuid',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'nullable|in:pdf,json',
            'hashtag_limit' => 'nullable|integer|min:10|max:100',
            'top_posts_limit' => 'nullable|integer|min:5|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $report = $this->reportService->generateContentAnalysisReport(
                $request->input('account_id'),
                $request->only(['start_date', 'end_date', 'format', 'hashtag_limit', 'top_posts_limit'])
            );

            if (!$report['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate content analysis report',
                    'error' => $report['error'] ?? 'Unknown error'
                ], 500);
            }

            return response()->json($report);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate content analysis report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Schedule recurring report
     *
     * POST /api/orgs/{org_id}/reports/schedule
     *
     * Request body:
     * {
     *   "report_type": "performance|ai_insights|organization|content_analysis",
     *   "entity_id": "uuid",
     *   "frequency": "daily|weekly|monthly",
     *   "format": "pdf|csv",
     *   "delivery_method": "email|storage|both",
     *   "recipients": ["email1@example.com", "email2@example.com"],
     *   "config": {}
     * }
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function scheduleReport(string $orgId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:performance,ai_insights,organization,content_analysis',
            'entity_id' => 'required|uuid',
            'frequency' => 'required|in:daily,weekly,monthly',
            'format' => 'nullable|in:pdf,csv',
            'delivery_method' => 'nullable|in:email,storage,both',
            'recipients' => 'nullable|array',
            'recipients.*' => 'email',
            'config' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $schedule = $this->reportService->scheduleReport(
                $request->input('report_type'),
                $request->input('entity_id'),
                $request->only(['frequency', 'format', 'delivery_method', 'recipients', 'config'])
            );

            if (!$schedule['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to schedule report',
                    'error' => $schedule['error'] ?? 'Unknown error'
                ], 500);
            }

            return response()->json($schedule, 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get scheduled reports
     *
     * GET /api/orgs/{org_id}/reports/schedules?entity_id=uuid
     *
     * @param string $orgId
     * @param Request $request
     * @return JsonResponse
     */
    public function getScheduledReports(string $orgId, Request $request): JsonResponse
    {
        try {
            $entityId = $request->input('entity_id');

            $schedules = $this->reportService->getScheduledReports($entityId);

            return response()->json($schedules);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get scheduled reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel scheduled report
     *
     * DELETE /api/orgs/{org_id}/reports/schedule/{schedule_id}
     *
     * @param string $orgId
     * @param string $scheduleId
     * @return JsonResponse
     */
    public function cancelScheduledReport(string $orgId, string $scheduleId): JsonResponse
    {
        try {
            $success = $this->reportService->cancelScheduledReport($scheduleId);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Report schedule cancelled successfully'
                ]);
            }

            return $this->error('Failed to cancel report schedule or schedule not found', 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel scheduled report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available report types
     *
     * GET /api/orgs/{org_id}/reports/types
     *
     * @param string $orgId
     * @return JsonResponse
     */
    public function getReportTypes(string $orgId): JsonResponse
    {
        $reportTypes = [
            [
                'type' => 'performance',
                'name' => 'Performance Report',
                'description' => 'Comprehensive account performance analytics including engagement, reach, and top posts',
                'formats' => ['pdf', 'json', 'csv'],
                'requires' => ['account_id'],
                'schedulable' => true
            ],
            [
                'type' => 'ai_insights',
                'name' => 'AI Insights Report',
                'description' => 'AI-powered recommendations, anomaly detection, and predictive analytics',
                'formats' => ['pdf', 'json'],
                'requires' => ['account_id'],
                'schedulable' => true
            ],
            [
                'type' => 'organization',
                'name' => 'Organization Overview',
                'description' => 'Organization-wide metrics across all social accounts and platforms',
                'formats' => ['pdf', 'json', 'csv'],
                'requires' => ['org_id'],
                'schedulable' => true
            ],
            [
                'type' => 'content_analysis',
                'name' => 'Content Analysis Report',
                'description' => 'Deep dive into content performance, hashtags, and engagement patterns',
                'formats' => ['pdf', 'json'],
                'requires' => ['account_id'],
                'schedulable' => true
            ]
        ];

        return $this->success($reportTypes
        );
    }
}
