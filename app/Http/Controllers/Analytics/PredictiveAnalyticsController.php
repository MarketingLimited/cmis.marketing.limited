<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Analytics\Forecast;
use App\Models\Analytics\Anomaly;
use App\Models\Analytics\Recommendation;
use App\Models\Analytics\TrendAnalysis;
use App\Services\Analytics\ForecastingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Predictive Analytics Controller (Phase 16)
 *
 * Manages forecasting, anomaly detection, trend analysis,
 * and intelligent recommendations
 */
class PredictiveAnalyticsController extends Controller
{
    use ApiResponse;

    public function __construct(protected ForecastingService $forecastingService)
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Generate forecast for entity
     * POST /api/orgs/{org_id}/analytics/forecasts
     */
    public function generateForecast(string $orgId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => 'required|string|max:50',
            'entity_id' => 'required|uuid',
            'metric' => 'required|string|max:100',
            'days' => 'sometimes|integer|min:1|max:90',
            'forecast_type' => 'sometimes|in:moving_average,linear_regression,weighted_average'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        try {
            $forecasts = $this->forecastingService->generateForecast(
                $orgId,
                $validated['entity_type'],
                $validated['entity_id'],
                $validated['metric'],
                $validated['days'] ?? 30,
                $validated['forecast_type'] ?? 'moving_average'
            );

            return response()->json([
                'success' => true,
                'forecasts' => $forecasts,
                'count' => count($forecasts),
                'message' => 'Forecasts generated successfully'
            ], 201);
        } catch (\RuntimeException $e) {
            return $this->validationError(, $e->getMessage()
            );
        }
    }

    /**
     * List forecasts
     * GET /api/orgs/{org_id}/analytics/forecasts
     */
    public function listForecasts(string $orgId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $query = Forecast::where('org_id', $orgId);

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        if ($request->has('entity_id')) {
            $query->where('entity_id', $request->input('entity_id'));
        }

        if ($request->has('metric')) {
            $query->where('metric', $request->input('metric'));
        }

        if ($request->has('forecast_type')) {
            $query->where('forecast_type', $request->input('forecast_type'));
        }

        if ($request->has('from_date')) {
            $query->where('forecast_date', '>=', $request->input('from_date'));
        }

        if ($request->has('to_date')) {
            $query->where('forecast_date', '<=', $request->input('to_date'));
        }

        $forecasts = $query->latest('generated_at')
            ->paginate($request->input('per_page', 30));

        return $this->success(['forecasts' => $forecasts], 'Operation completed successfully');
    }

    /**
     * Get forecast details
     * GET /api/orgs/{org_id}/analytics/forecasts/{forecast_id}
     */
    public function getForecast(string $orgId, string $forecastId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $forecast = Forecast::findOrFail($forecastId);

        return response()->json([
            'success' => true,
            'forecast' => $forecast,
            'accuracy' => $forecast->actual_value ? [
                'is_accurate' => $forecast->isAccurate(),
                'accuracy_percentage' => $forecast->getAccuracyPercentage()
            ] : null
        ]);
    }

    /**
     * Update forecast with actual value
     * PUT /api/orgs/{org_id}/analytics/forecasts/{forecast_id}
     */
    public function updateForecast(string $orgId, string $forecastId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'actual_value' => 'required|numeric'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $forecast = Forecast::findOrFail($forecastId);
        $forecast->updateActual($validated['actual_value']);

        return response()->json([
            'success' => true,
            'forecast' => $forecast->fresh(),
            'accuracy' => [
                'is_accurate' => $forecast->isAccurate(),
                'accuracy_percentage' => $forecast->getAccuracyPercentage()
            ],
            'message' => 'Forecast updated with actual value'
        ]);
    }

    /**
     * Detect anomalies for entity
     * POST /api/orgs/{org_id}/analytics/anomalies/detect
     */
    public function detectAnomalies(string $orgId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => 'required|string|max:50',
            'entity_id' => 'required|uuid',
            'metric' => 'required|string|max:100',
            'days' => 'sometimes|integer|min:7|max:90'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $anomalies = $this->forecastingService->detectAnomalies(
            $orgId,
            $validated['entity_type'],
            $validated['entity_id'],
            $validated['metric'],
            $validated['days'] ?? 30
        );

        return response()->json([
            'success' => true,
            'anomalies' => $anomalies,
            'count' => count($anomalies),
            'message' => count($anomalies) > 0 ? 'Anomalies detected' : 'No anomalies detected'
        ], 201);
    }

    /**
     * List anomalies
     * GET /api/orgs/{org_id}/analytics/anomalies
     */
    public function listAnomalies(string $orgId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $query = Anomaly::where('org_id', $orgId);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        if ($request->has('anomaly_type')) {
            $query->where('anomaly_type', $request->input('anomaly_type'));
        }

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        if ($request->has('entity_id')) {
            $query->where('entity_id', $request->input('entity_id'));
        }

        if ($request->has('metric')) {
            $query->where('metric', $request->input('metric'));
        }

        $anomalies = $query->latest('detected_date')
            ->paginate($request->input('per_page', 15));

        return $this->success(['anomalies' => $anomalies], 'Operation completed successfully');
    }

    /**
     * Get anomaly details
     * GET /api/orgs/{org_id}/analytics/anomalies/{anomaly_id}
     */
    public function getAnomaly(string $orgId, string $anomalyId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $anomaly = Anomaly::findOrFail($anomalyId);

        return $this->success(['anomaly' => $anomaly], 'Operation completed successfully');
    }

    /**
     * Acknowledge anomaly
     * POST /api/orgs/{org_id}/analytics/anomalies/{anomaly_id}/acknowledge
     */
    public function acknowledgeAnomaly(string $orgId, string $anomalyId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'sometimes|string|max:1000'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $anomaly = Anomaly::findOrFail($anomalyId);
        $anomaly->acknowledge($user->user_id, $validated['notes'] ?? null);

        return $this->success(['anomaly' => $anomaly->fresh(),
            'message' => 'Anomaly acknowledged'], 'Operation completed successfully');
    }

    /**
     * Resolve anomaly
     * POST /api/orgs/{org_id}/analytics/anomalies/{anomaly_id}/resolve
     */
    public function resolveAnomaly(string $orgId, string $anomalyId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resolution_notes' => 'required|string|max:1000'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $anomaly = Anomaly::findOrFail($anomalyId);
        $anomaly->resolve($validated['resolution_notes']);

        return $this->success(['anomaly' => $anomaly->fresh(),
            'message' => 'Anomaly resolved'], 'Operation completed successfully');
    }

    /**
     * Mark anomaly as false positive
     * POST /api/orgs/{org_id}/analytics/anomalies/{anomaly_id}/false-positive
     */
    public function markFalsePositive(string $orgId, string $anomalyId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $anomaly = Anomaly::findOrFail($anomalyId);
        $anomaly->markFalsePositive();

        return $this->success(['anomaly' => $anomaly->fresh(),
            'message' => 'Anomaly marked as false positive'], 'Operation completed successfully');
    }

    /**
     * Analyze trends for entity
     * POST /api/orgs/{org_id}/analytics/trends
     */
    public function analyzeTrends(string $orgId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => 'required|string|max:50',
            'entity_id' => 'required|uuid',
            'metric' => 'required|string|max:100',
            'days' => 'sometimes|integer|min:7|max:90'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        try {
            $trend = $this->forecastingService->analyzeTrends(
                $orgId,
                $validated['entity_type'],
                $validated['entity_id'],
                $validated['metric'],
                $validated['days'] ?? 30
            );

            return response()->json([
                'success' => true,
                'trend' => $trend,
                'message' => 'Trend analysis completed'
            ], 201);
        } catch (\RuntimeException $e) {
            return $this->validationError(, $e->getMessage()
            );
        }
    }

    /**
     * List trend analyses
     * GET /api/orgs/{org_id}/analytics/trends
     */
    public function listTrends(string $orgId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $query = TrendAnalysis::where('org_id', $orgId);

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        if ($request->has('entity_id')) {
            $query->where('entity_id', $request->input('entity_id'));
        }

        if ($request->has('metric')) {
            $query->where('metric', $request->input('metric'));
        }

        if ($request->has('trend_type')) {
            $query->where('trend_type', $request->input('trend_type'));
        }

        $trends = $query->latest('period_end')
            ->paginate($request->input('per_page', 15));

        return $this->success(['trends' => $trends], 'Operation completed successfully');
    }

    /**
     * Generate recommendations for entity
     * POST /api/orgs/{org_id}/analytics/recommendations/generate
     */
    public function generateRecommendations(string $orgId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entity_type' => 'required|string|max:50',
            'entity_id' => 'required|uuid'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $recommendations = $this->forecastingService->generateRecommendations(
            $orgId,
            $validated['entity_type'],
            $validated['entity_id']
        );

        return response()->json([
            'success' => true,
            'recommendations' => $recommendations,
            'count' => count($recommendations),
            'message' => count($recommendations) > 0 ? 'Recommendations generated' : 'No recommendations at this time'
        ], 201);
    }

    /**
     * List recommendations
     * GET /api/orgs/{org_id}/analytics/recommendations
     */
    public function listRecommendations(string $orgId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $query = Recommendation::where('org_id', $orgId);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->has('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        if ($request->has('entity_id')) {
            $query->where('entity_id', $request->input('entity_id'));
        }

        // Exclude expired recommendations by default
        if (!$request->has('include_expired') || !$request->boolean('include_expired')) {
            $query->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
        }

        $recommendations = $query->latest('created_at')
            ->paginate($request->input('per_page', 15));

        return $this->success(['recommendations' => $recommendations], 'Operation completed successfully');
    }

    /**
     * Get recommendation details
     * GET /api/orgs/{org_id}/analytics/recommendations/{recommendation_id}
     */
    public function getRecommendation(string $orgId, string $recommendationId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $recommendation = Recommendation::findOrFail($recommendationId);

        return $this->success(['recommendation' => $recommendation,
            'is_expired' => $recommendation->isExpired()], 'Operation completed successfully');
    }

    /**
     * Accept recommendation
     * POST /api/orgs/{org_id}/analytics/recommendations/{recommendation_id}/accept
     */
    public function acceptRecommendation(string $orgId, string $recommendationId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $recommendation = Recommendation::findOrFail($recommendationId);
        $recommendation->accept($user->user_id);

        return $this->success(['recommendation' => $recommendation->fresh(),
            'message' => 'Recommendation accepted'], 'Operation completed successfully');
    }

    /**
     * Reject recommendation
     * POST /api/orgs/{org_id}/analytics/recommendations/{recommendation_id}/reject
     */
    public function rejectRecommendation(string $orgId, string $recommendationId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => 'sometimes|string|max:500'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $recommendation = Recommendation::findOrFail($recommendationId);
        $recommendation->reject($user->user_id, $validated['rejection_reason'] ?? null);

        return $this->success(['recommendation' => $recommendation->fresh(),
            'message' => 'Recommendation rejected'], 'Operation completed successfully');
    }

    /**
     * Mark recommendation as implemented
     * POST /api/orgs/{org_id}/analytics/recommendations/{recommendation_id}/implement
     */
    public function implementRecommendation(string $orgId, string $recommendationId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'implementation_notes' => 'sometimes|string|max:1000'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $recommendation = Recommendation::findOrFail($recommendationId);
        $recommendation->implement($validated['implementation_notes'] ?? null);

        return $this->success(['recommendation' => $recommendation->fresh(),
            'message' => 'Recommendation marked as implemented'], 'Operation completed successfully');
    }

    /**
     * Get predictive analytics statistics
     * GET /api/orgs/{org_id}/analytics/stats
     */
    public function stats(string $orgId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $stats = [
            'forecasts' => [
                'total' => Forecast::where('org_id', $orgId)->count(),
                'with_actuals' => Forecast::where('org_id', $orgId)
                    ->whereNotNull('actual_value')
                    ->count(),
                'accuracy_rate' => $this->calculateForecastAccuracy($orgId)
            ],
            'anomalies' => [
                'total' => Anomaly::where('org_id', $orgId)->count(),
                'new' => Anomaly::where('org_id', $orgId)->where('status', 'new')->count(),
                'acknowledged' => Anomaly::where('org_id', $orgId)->where('status', 'acknowledged')->count(),
                'resolved' => Anomaly::where('org_id', $orgId)->where('status', 'resolved')->count(),
                'false_positives' => Anomaly::where('org_id', $orgId)->where('status', 'false_positive')->count()
            ],
            'recommendations' => [
                'total' => Recommendation::where('org_id', $orgId)->count(),
                'pending' => Recommendation::where('org_id', $orgId)->where('status', 'pending')->count(),
                'accepted' => Recommendation::where('org_id', $orgId)->where('status', 'accepted')->count(),
                'implemented' => Recommendation::where('org_id', $orgId)->where('status', 'implemented')->count(),
                'rejected' => Recommendation::where('org_id', $orgId)->where('status', 'rejected')->count()
            ],
            'trends' => [
                'total' => TrendAnalysis::where('org_id', $orgId)->count(),
                'upward' => TrendAnalysis::where('org_id', $orgId)->where('trend_type', 'upward')->count(),
                'downward' => TrendAnalysis::where('org_id', $orgId)->where('trend_type', 'downward')->count(),
                'stable' => TrendAnalysis::where('org_id', $orgId)->where('trend_type', 'stable')->count()
            ]
        ];

        // Recent items
        $stats['recent'] = [
            'anomalies' => Anomaly::where('org_id', $orgId)
                ->latest('detected_date')
                ->limit(5)
                ->get(),
            'recommendations' => Recommendation::where('org_id', $orgId)
                ->where('status', 'pending')
                ->orderBy('priority', 'desc')
                ->orderBy('confidence_score', 'desc')
                ->limit(5)
                ->get(),
            'trends' => TrendAnalysis::where('org_id', $orgId)
                ->latest('period_end')
                ->limit(5)
                ->get()
        ];

        return $this->success(['stats' => $stats], 'Operation completed successfully');
    }

    /**
     * Calculate forecast accuracy rate
     */
    protected function calculateForecastAccuracy(string $orgId): float
    {
        $forecasts = Forecast::where('org_id', $orgId)
            ->whereNotNull('actual_value')
            ->whereNotNull('confidence_lower')
            ->whereNotNull('confidence_upper')
            ->get();

        if ($forecasts->isEmpty()) {
            return 0.0;
        }

        $accurate = $forecasts->filter(fn($f) => $f->isAccurate())->count();

        return round(($accurate / $forecasts->count()) * 100, 2);
    }
}
