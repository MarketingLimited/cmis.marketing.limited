<?php

namespace App\Http\Controllers\Intelligence;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Intelligence\TrendAnalysis;
use App\Services\Intelligence\TrendAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrendAnalysisController extends Controller
{
    use ApiResponse;

    protected TrendAnalysisService $trendAnalysisService;

    public function __construct(TrendAnalysisService $trendAnalysisService)
    {
        $this->trendAnalysisService = $trendAnalysisService;
    }

    /**
     * Display a listing of trend analyses
     */
    public function index(Request $request)
    {
        $orgId = session('current_org_id');

        $trends = TrendAnalysis::where('org_id', $orgId)
            ->when($request->metric, fn($q) => $q->byMetric($request->metric))
            ->when($request->entity_type, fn($q) => $q->where('entity_type', $request->entity_type))
            ->when($request->direction, fn($q) => $q->byDirection($request->direction))
            ->when($request->pattern, fn($q) => $q->byPattern($request->pattern))
            ->when($request->significant_only, fn($q) => $q->significant())
            ->with(['creator', 'entity'])
            ->latest('analysis_date')
            ->paginate($request->get('per_page', 20));

        if ($request->expectsJson()) {
            return $this->paginated($trends, 'Trend analyses retrieved successfully');
        }

        return view('intelligence.trends.index', compact('trends'));
    }

    /**
     * Display the specified trend analysis
     */
    public function show(string $id)
    {
        $trend = TrendAnalysis::with(['creator', 'entity'])
            ->findOrFail($id);

        if (request()->expectsJson()) {
            return $this->success($trend, 'Trend analysis retrieved successfully');
        }

        return view('intelligence.trends.show', compact('trend'));
    }

    /**
     * Analyze trends for an entity
     */
    public function analyze(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string',
            'entity_id' => 'required|uuid',
            'metrics' => 'required|array|min:1',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after:date_from',
            'analysis_window' => 'nullable|integer|min:7|max:365',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $analyses = $this->trendAnalysisService->analyzeTrends(
            $request->entity_type,
            $request->entity_id,
            $request->metrics,
            $request->date_from,
            $request->date_to,
            $request->analysis_window ?? 30
        );

        return $this->success($analyses, 'Trend analysis completed successfully');
    }

    /**
     * Compare trends across multiple entities
     */
    public function compare(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entities' => 'required|array|min:2',
            'entities.*.type' => 'required|string',
            'entities.*.id' => 'required|uuid',
            'metric' => 'required|string',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after:date_from',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $comparison = $this->trendAnalysisService->compareEntities(
            $request->entities,
            $request->metric,
            $request->date_from,
            $request->date_to
        );

        if ($request->expectsJson()) {
            return $this->success($comparison, 'Trend comparison completed successfully');
        }

        return view('intelligence.trends.compare', compact('comparison'));
    }

    /**
     * Detect patterns in trend data
     */
    public function detectPatterns(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string',
            'entity_id' => 'required|uuid',
            'metric' => 'required|string',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after:date_from',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $patterns = $this->trendAnalysisService->detectPatterns(
            $request->entity_type,
            $request->entity_id,
            $request->metric,
            $request->date_from,
            $request->date_to
        );

        return $this->success($patterns, 'Pattern detection completed successfully');
    }

    /**
     * Get seasonality analysis
     */
    public function seasonality(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string',
            'entity_id' => 'required|uuid',
            'metric' => 'required|string',
            'periods' => 'nullable|integer|min:2|max:24', // Number of seasonal periods (e.g., 12 for monthly)
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $seasonality = $this->trendAnalysisService->analyzeSeasonality(
            $request->entity_type,
            $request->entity_id,
            $request->metric,
            $request->periods ?? 12
        );

        return $this->success($seasonality, 'Seasonality analysis completed successfully');
    }

    /**
     * Get trend analytics dashboard data
     */
    public function analytics(Request $request)
    {
        $orgId = session('current_org_id');

        $analytics = $this->trendAnalysisService->getAnalytics($orgId);

        if ($request->expectsJson()) {
            return $this->success($analytics, 'Trend analytics retrieved successfully');
        }

        return view('intelligence.trends.analytics', compact('analytics'));
    }

    /**
     * Get trending metrics summary
     */
    public function insights(Request $request)
    {
        $orgId = session('current_org_id');

        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:365',
            'entity_type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $insights = $this->trendAnalysisService->getInsights(
            $orgId,
            $request->days ?? 30,
            $request->entity_type
        );

        return $this->success($insights, 'Trend insights retrieved successfully');
    }

    /**
     * Get significant trends
     */
    public function significant(Request $request)
    {
        $orgId = session('current_org_id');

        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:90',
            'alpha' => 'nullable|numeric|min:0.001|max:0.1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $significantTrends = TrendAnalysis::where('org_id', $orgId)
            ->where('analysis_date', '>=', now()->subDays($request->days ?? 30))
            ->where('statistical_significance', '<=', $request->alpha ?? 0.05)
            ->with(['entity'])
            ->orderBy('statistical_significance', 'asc')
            ->get();

        return $this->success($significantTrends, 'Significant trends retrieved successfully');
    }

    /**
     * Export trend data
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:csv,json,xlsx',
            'trend_ids' => 'nullable|array',
            'trend_ids.*' => 'uuid',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $export = $this->trendAnalysisService->exportTrends(
            $request->trend_ids,
            $request->format
        );

        return response()->download($export['path'], $export['filename']);
    }
}
