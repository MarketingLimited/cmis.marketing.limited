<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\RealTimeAnalyticsService;
use App\Services\Analytics\CustomMetricsService;
use App\Services\Analytics\ROICalculationEngine;
use App\Services\Analytics\AttributionModelingService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Validator;

/**
 * Analytics Controller (Phase 7)
 *
 * Unified API for advanced analytics, custom metrics, ROI, and attribution
 */
class AnalyticsController extends Controller
{
    protected RealTimeAnalyticsService $realtime;
    protected CustomMetricsService $customMetrics;
    protected ROICalculationEngine $roiEngine;
    protected AttributionModelingService $attribution;

    public function __construct(
        RealTimeAnalyticsService $realtime,
        CustomMetricsService $customMetrics,
        ROICalculationEngine $roiEngine,
        AttributionModelingService $attribution
    ) {
        $this->middleware('auth:sanctum');
        $this->realtime = $realtime;
        $this->customMetrics = $customMetrics;
        $this->roiEngine = $roiEngine;
        $this->attribution = $attribution;
    }

    // REAL-TIME ANALYTICS
    public function getRealtimeMetrics(Request $request, string $orgId, string $entityType, string $entityId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'metrics' => 'nullable|array',
            'window' => 'nullable|string|in:1m,5m,15m,1h'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->realtime->getRealtimeMetrics(
            $entityType,
            $entityId,
            $request->input('metrics', []),
            $request->input('window', '5m')
        );

        return response()->json($result);
    }

    public function getTimeSeries(Request $request, string $orgId, string $entityType, string $entityId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'metric' => 'required|string',
            'window' => 'nullable|string|in:1m,5m,15m,1h',
            'points' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->realtime->getTimeSeries(
            $entityType,
            $entityId,
            $request->input('metric'),
            $request->input('window', '5m'),
            $request->input('points', 12)
        );

        return response()->json($result);
    }

    public function getRealtimeDashboard(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'window' => 'nullable|string|in:1m,5m,15m,1h'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->realtime->getOrganizationDashboard(
            $orgId,
            $request->input('window', '5m')
        );

        return response()->json($result);
    }

    public function detectAnomalies(string $orgId, string $entityType, string $entityId, string $metric): JsonResponse
    {
        $result = $this->realtime->detectAnomalies($entityType, $entityId, $metric);
        return response()->json($result);
    }

    // CUSTOM METRICS & KPIS
    public function createMetric(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'calculation_type' => 'required|string|in:sum,average,count,ratio,formula',
            'source_metrics' => 'nullable|array',
            'formula' => 'nullable|string',
            'unit' => 'nullable|string',
            'decimal_places' => 'nullable|integer|min:0|max:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $definition = array_merge($request->all(), ['org_id' => $orgId]);
        $result = $this->customMetrics->createMetric($definition);

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    public function calculateMetric(Request $request, string $orgId, string $metricId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string',
            'entity_id' => 'required|string',
            'date_range' => 'nullable|array',
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date|after:date_range.start'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->customMetrics->calculateMetric(
            $metricId,
            $request->input('entity_type'),
            $request->input('entity_id'),
            $request->input('date_range', [])
        );

        return response()->json($result);
    }

    public function createKPI(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'metric_id' => 'nullable|uuid',
            'target_value' => 'required|numeric',
            'warning_threshold' => 'nullable|numeric',
            'critical_threshold' => 'nullable|numeric',
            'period' => 'nullable|string|in:daily,weekly,monthly,quarterly,yearly',
            'is_higher_better' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $definition = array_merge($request->all(), ['org_id' => $orgId]);
        $result = $this->customMetrics->createKPI($definition);

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    public function evaluateKPI(Request $request, string $orgId, string $kpiId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string',
            'entity_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->customMetrics->evaluateKPI(
            $kpiId,
            $request->input('entity_type'),
            $request->input('entity_id')
        );

        return response()->json($result);
    }

    public function getKPIDashboard(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string',
            'entity_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->customMetrics->getKPIDashboard(
            $orgId,
            $request->input('entity_type'),
            $request->input('entity_id')
        );

        return response()->json($result);
    }

    // ROI CALCULATION
    public function calculateCampaignROI(Request $request, string $orgId, string $campaignId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date_range' => 'nullable|array',
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date|after:date_range.start'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->roiEngine->calculateCampaignROI(
            $campaignId,
            $request->input('date_range', [])
        );

        return response()->json($result);
    }

    public function calculateOrganizationROI(Request $request, string $orgId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date_range' => 'nullable|array',
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date|after:date_range.start'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->roiEngine->calculateOrganizationROI(
            $orgId,
            $request->input('date_range', [])
        );

        return response()->json($result);
    }

    public function calculateLifetimeValue(string $orgId, string $campaignId): JsonResponse
    {
        $result = $this->roiEngine->calculateLifetimeValue($campaignId);
        return response()->json($result);
    }

    public function projectROI(Request $request, string $orgId, string $campaignId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days_to_project' => 'nullable|integer|min:1|max:365'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->roiEngine->projectROI(
            $campaignId,
            $request->input('days_to_project', 30)
        );

        return response()->json($result);
    }

    // ATTRIBUTION MODELING
    public function attributeConversions(Request $request, string $orgId, string $campaignId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model' => 'nullable|string|in:last_click,first_click,linear,time_decay,position_based,data_driven',
            'date_range' => 'nullable|array',
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date|after:date_range.start'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->attribution->attributeConversions(
            $campaignId,
            $request->input('model', 'last_click'),
            $request->input('date_range', [])
        );

        return response()->json($result);
    }

    public function compareAttributionModels(Request $request, string $orgId, string $campaignId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date_range' => 'nullable|array',
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date|after:date_range.start'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->attribution->compareAttributionModels(
            $campaignId,
            $request->input('date_range', [])
        );

        return response()->json($result);
    }

    public function getAttributionInsights(Request $request, string $orgId, string $campaignId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model' => 'nullable|string|in:last_click,first_click,linear,time_decay,position_based,data_driven',
            'date_range' => 'nullable|array',
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date|after:date_range.start'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->attribution->getAttributionInsights(
            $campaignId,
            $request->input('model', 'linear'),
            $request->input('date_range', [])
        );

        return response()->json($result);
    }

    public function deleteMetric(string $orgId, string $metricId): JsonResponse
    {
        $result = $this->customMetrics->deleteMetric($metricId);
        return response()->json($result);
    }

    public function deleteKPI(string $orgId, string $kpiId): JsonResponse
    {
        $result = $this->customMetrics->deleteKPI($kpiId);
        return response()->json($result);
    }
}
