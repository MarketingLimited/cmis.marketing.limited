<?php

namespace App\Http\Controllers\Enterprise;
use App\Http\Controllers\Concerns\ApiResponse;

use App\Http\Controllers\Controller;
use App\Services\Enterprise\PerformanceMonitoringService;
use App\Services\Enterprise\AdvancedReportingService;
use App\Services\Enterprise\WebhookManagementService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

/**
 * Enterprise Features Controller (Phase 5)
 *
 * Unified API for performance monitoring, advanced reporting, and webhook management
 */
class EnterpriseController extends Controller
{
    use ApiResponse;

    protected PerformanceMonitoringService $monitoring;
    protected AdvancedReportingService $reporting;
    protected WebhookManagementService $webhooks;

    public function __construct(
        PerformanceMonitoringService $monitoring,
        AdvancedReportingService $reporting,
        WebhookManagementService $webhooks
    ) {
        $this->middleware('auth:sanctum');
        $this->monitoring = $monitoring;
        $this->reporting = $reporting;
        $this->webhooks = $webhooks;
    }

    // =========================================================================
    // PERFORMANCE MONITORING ENDPOINTS
    // =========================================================================

    /**
     * Monitor campaign performance and detect anomalies
     *
     * POST /api/orgs/{org_id}/enterprise/monitor/campaign/{campaign_id}
     */
    public function monitorCampaign(string $orgId, string $campaignId): JsonResponse
    {
        try {
            $result = $this->monitoring->monitorCampaignPerformance($campaignId);

            return $this->success($result, 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Monitor all campaigns for an organization
     *
     * POST /api/orgs/{org_id}/enterprise/monitor/organization
     */
    public function monitorOrganization(string $orgId): JsonResponse
    {
        try {
            $result = $this->monitoring->monitorOrganizationPerformance($orgId);

            return $this->success($result, 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get active alerts for organization
     *
     * GET /api/orgs/{org_id}/enterprise/alerts
     */
    public function getAlerts(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'severity' => 'nullable|string|in:critical,high,medium,low',
            'type' => 'nullable|string|in:budget_exceeded,performance_drop,anomaly_detected,spend_spike,zero_conversions,impression_drop',
            'status' => 'nullable|string|in:active,acknowledged,resolved',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $filters = $request->only(['severity', 'type', 'status']);
            $limit = $request->input('limit', 50);

            $alerts = $this->monitoring->getActiveAlerts($orgId, $filters, $limit);

            return $this->success(['alerts' => $alerts,
                'count' => count($alerts)], 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Acknowledge an alert
     *
     * POST /api/orgs/{org_id}/enterprise/alerts/{alert_id}/acknowledge
     */
    public function acknowledgeAlert(Request $request, string $orgId, string $alertId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'acknowledged_by' => 'required|uuid',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->monitoring->acknowledgeAlert(
                $alertId,
                $request->input('acknowledged_by'),
                $request->input('notes')
            );

            return $this->success($result, 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Resolve an alert
     *
     * POST /api/orgs/{org_id}/enterprise/alerts/{alert_id}/resolve
     */
    public function resolveAlert(Request $request, string $orgId, string $alertId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resolved_by' => 'required|uuid',
            'resolution_notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->monitoring->resolveAlert(
                $alertId,
                $request->input('resolved_by'),
                $request->input('resolution_notes')
            );

            return $this->success($result, 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get alert statistics
     *
     * GET /api/orgs/{org_id}/enterprise/alerts/statistics
     */
    public function getAlertStatistics(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:365'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $days = $request->input('days', 30);
            $stats = $this->monitoring->getAlertStatistics($orgId, $days);

            return $this->success(['statistics' => $stats], 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // ADVANCED REPORTING ENDPOINTS
    // =========================================================================

    /**
     * Generate a campaign report
     *
     * POST /api/orgs/{org_id}/enterprise/reports/campaign/{campaign_id}
     */
    public function generateCampaignReport(Request $request, string $orgId, string $campaignId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'format' => 'nullable|string|in:pdf,excel,csv,json',
            'date_range' => 'nullable|array',
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date|after:date_range.start',
            'metrics' => 'nullable|array',
            'include_charts' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $options = [
                'format' => $request->input('format', 'pdf'),
                'date_range' => $request->input('date_range'),
                'metrics' => $request->input('metrics', []),
                'include_charts' => $request->input('include_charts', true)
            ];

            $result = $this->reporting->generateCampaignReport($campaignId, $options);

            return $this->success($result, 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate an organization report
     *
     * POST /api/orgs/{org_id}/enterprise/reports/organization
     */
    public function generateOrganizationReport(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'format' => 'nullable|string|in:pdf,excel,csv,json',
            'date_range' => 'nullable|array',
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date|after:date_range.start',
            'include_campaigns' => 'nullable|boolean',
            'include_charts' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $options = [
                'format' => $request->input('format', 'pdf'),
                'date_range' => $request->input('date_range'),
                'include_campaigns' => $request->input('include_campaigns', true),
                'include_charts' => $request->input('include_charts', true)
            ];

            $result = $this->reporting->generateOrganizationReport($orgId, $options);

            return $this->success($result, 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Schedule a report
     *
     * POST /api/orgs/{org_id}/enterprise/reports/schedule
     */
    public function scheduleReport(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|string|in:campaign,organization,custom',
            'frequency' => 'required|string|in:daily,weekly,monthly,quarterly',
            'format' => 'nullable|string|in:pdf,excel,csv,json',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'required|email',
            'entity_id' => 'nullable|uuid',
            'options' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $schedule = [
                'org_id' => $orgId,
                'report_type' => $request->input('report_type'),
                'frequency' => $request->input('frequency'),
                'format' => $request->input('format', 'pdf'),
                'recipients' => $request->input('recipients'),
                'entity_id' => $request->input('entity_id'),
                'options' => $request->input('options', [])
            ];

            $result = $this->reporting->scheduleReport($schedule);

            return $this->created($result, 'Created successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get scheduled reports
     *
     * GET /api/orgs/{org_id}/enterprise/reports/schedules
     */
    public function getScheduledReports(string $orgId): JsonResponse
    {
        try {
            $schedules = $this->reporting->getScheduledReports($orgId);

            return $this->success(['schedules' => $schedules,
                'count' => count($schedules)], 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete a scheduled report
     *
     * DELETE /api/orgs/{org_id}/enterprise/reports/schedules/{schedule_id}
     */
    public function deleteScheduledReport(string $orgId, string $scheduleId): JsonResponse
    {
        try {
            $result = $this->reporting->deleteSchedule($scheduleId);

            return $this->success($result, 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get report history
     *
     * GET /api/orgs/{org_id}/enterprise/reports/history
     */
    public function getReportHistory(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100',
            'report_type' => 'nullable|string|in:campaign,organization,custom'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $limit = $request->input('limit', 50);
            $reportType = $request->input('report_type');

            $history = $this->reporting->getReportHistory($orgId, $limit, $reportType);

            return $this->success(['reports' => $history,
                'count' => count($history)], 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Download a generated report
     *
     * GET /api/orgs/{org_id}/enterprise/reports/{report_id}/download
     */
    public function downloadReport(string $orgId, string $reportId): JsonResponse
    {
        try {
            $result = $this->reporting->downloadReport($reportId);

            if (!$result['success']) {
                return response()->json($result, 404);
            }

            return $this->success($result, 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // WEBHOOK MANAGEMENT ENDPOINTS
    // =========================================================================

    /**
     * Register a new webhook
     *
     * POST /api/orgs/{org_id}/enterprise/webhooks
     */
    public function registerWebhook(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'required|string|in:campaign.created,campaign.updated,campaign.paused,budget.exhausted,alert.triggered,report.generated,optimization.completed',
            'secret' => 'required|string|min:32|max:128',
            'description' => 'nullable|string|max:500',
            'active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $webhook = [
                'org_id' => $orgId,
                'url' => $request->input('url'),
                'events' => $request->input('events'),
                'secret' => $request->input('secret'),
                'description' => $request->input('description'),
                'active' => $request->input('active', true)
            ];

            $result = $this->webhooks->registerWebhook($webhook);

            return $this->created($result, 'Created successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get all webhooks for organization
     *
     * GET /api/orgs/{org_id}/enterprise/webhooks
     */
    public function getWebhooks(string $orgId): JsonResponse
    {
        try {
            $webhooks = $this->webhooks->getWebhooks($orgId);

            return $this->success(['webhooks' => $webhooks,
                'count' => count($webhooks)], 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Update a webhook
     *
     * PUT /api/orgs/{org_id}/enterprise/webhooks/{webhook_id}
     */
    public function updateWebhook(Request $request, string $orgId, string $webhookId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'nullable|url|max:500',
            'events' => 'nullable|array|min:1',
            'events.*' => 'nullable|string|in:campaign.created,campaign.updated,campaign.paused,budget.exhausted,alert.triggered,report.generated,optimization.completed',
            'secret' => 'nullable|string|min:32|max:128',
            'description' => 'nullable|string|max:500',
            'active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $updates = $request->only(['url', 'events', 'secret', 'description', 'active']);
            $result = $this->webhooks->updateWebhook($webhookId, $updates);

            return $this->success($result, 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete a webhook
     *
     * DELETE /api/orgs/{org_id}/enterprise/webhooks/{webhook_id}
     */
    public function deleteWebhook(string $orgId, string $webhookId): JsonResponse
    {
        try {
            $result = $this->webhooks->deleteWebhook($webhookId);

            return $this->success($result, 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Trigger a webhook event
     *
     * POST /api/orgs/{org_id}/enterprise/webhooks/trigger
     */
    public function triggerWebhook(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'event' => 'required|string|in:campaign.created,campaign.updated,campaign.paused,budget.exhausted,alert.triggered,report.generated,optimization.completed',
            'data' => 'required|array'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $result = $this->webhooks->triggerEvent(
                $orgId,
                $request->input('event'),
                $request->input('data')
            );

            return $this->success($result, 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get webhook delivery history
     *
     * GET /api/orgs/{org_id}/enterprise/webhooks/{webhook_id}/deliveries
     */
    public function getWebhookDeliveries(Request $request, string $orgId, string $webhookId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|string|in:pending,delivered,failed,retrying'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $limit = $request->input('limit', 50);
            $status = $request->input('status');

            $deliveries = $this->webhooks->getDeliveries($webhookId, $limit, $status);

            return $this->success(['deliveries' => $deliveries,
                'count' => count($deliveries)], 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Get webhook statistics
     *
     * GET /api/orgs/{org_id}/enterprise/webhooks/{webhook_id}/statistics
     */
    public function getWebhookStatistics(Request $request, string $orgId, string $webhookId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:365'
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $days = $request->input('days', 30);
            $stats = $this->webhooks->getStatistics($webhookId, $days);

            return $this->success(['statistics' => $stats], 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Retry a failed webhook delivery
     *
     * POST /api/orgs/{org_id}/enterprise/webhooks/deliveries/{delivery_id}/retry
     */
    public function retryWebhookDelivery(string $orgId, string $deliveryId): JsonResponse
    {
        try {
            $result = $this->webhooks->retryDelivery($deliveryId);

            return $this->success($result, 'Operation completed successfully');

        } catch (\Exception $e) {
            return $this->serverError('Operation failed: ' . $e->getMessage());
        }
    }
}
