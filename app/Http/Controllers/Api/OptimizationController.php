<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Optimization\BudgetOptimizer;
use App\Services\Optimization\AudienceAnalyzer;
use App\Services\Optimization\AttributionEngine;
use App\Services\Optimization\CreativeAnalyzer;
use App\Services\Optimization\InsightGenerator;
use App\Models\Optimization\OptimizationModel;
use App\Models\Optimization\OptimizationRun;
use App\Models\Optimization\BudgetAllocation;
use App\Models\Optimization\AudienceOverlap;
use App\Models\Optimization\AttributionModel;
use App\Models\Optimization\CreativePerformance;
use App\Models\Optimization\OptimizationInsight;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Concerns\ApiResponse;

class OptimizationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected BudgetOptimizer $budgetOptimizer,
        protected AudienceAnalyzer $audienceAnalyzer,
        protected AttributionEngine $attributionEngine,
        protected CreativeAnalyzer $creativeAnalyzer,
        protected InsightGenerator $insightGenerator
    ) {}

    // ===== Budget Optimization =====

    /**
     * Optimize budget allocation across campaigns.
     */
    public function optimizeBudget(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'campaign_ids' => 'required|array|min:2',
            'campaign_ids.*' => 'required|uuid|exists:cmis.campaigns,campaign_id',
            'total_budget' => 'required|numeric|min:0',
            'objective' => 'required|in:maximize_roas,maximize_conversions,maximize_revenue,minimize_cpa',
            'constraints' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $orgId = $request->user()->org_id;

            $run = $this->budgetOptimizer->optimizeBudgetAllocation(
                $orgId,
                $request->campaign_ids,
                $request->total_budget,
                $request->objective,
                $request->constraints ?? []
            );

            return response()->json([
                'success' => true,
                'run' => $run->load('budgetAllocations'),
                'message' => 'Budget optimization completed successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Budget optimization failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->user_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Budget optimization failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get budget allocation recommendations.
     */
    public function getBudgetAllocations(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $allocations = BudgetAllocation::where('org_id', $orgId)
            ->where('status', 'pending')
            ->with(['campaign', 'optimizationRun'])
            ->orderByDesc('allocation_score')
            ->get();

        return response()->json([
            'success' => true,
            'allocations' => $allocations,
        ]);
    }

    /**
     * Apply budget allocation.
     */
    public function applyBudgetAllocation(Request $request, string $allocationId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $allocation = BudgetAllocation::where('org_id', $orgId)
            ->where('allocation_id', $allocationId)
            ->firstOrFail();

        try {
            // Update campaign budget
            $campaign = $allocation->campaign;
            $campaign->update([
                'daily_budget' => $allocation->recommended_budget,
            ]);

            // Mark allocation as applied
            $allocation->markAsApplied();

            return response()->json([
                'success' => true,
                'message' => 'Budget allocation applied successfully',
                'allocation' => $allocation,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to apply budget allocation', [
                'allocation_id' => $allocationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to apply budget allocation: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ===== Audience Overlap =====

    /**
     * Detect audience overlaps.
     */
    public function detectOverlaps(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'campaign_ids' => 'nullable|array',
            'campaign_ids.*' => 'uuid|exists:cmis.campaigns,campaign_id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $orgId = $request->user()->org_id;

            $overlaps = $this->audienceAnalyzer->detectOverlaps(
                $orgId,
                $request->campaign_ids
            );

            return response()->json([
                'success' => true,
                'overlaps' => $overlaps,
                'count' => count($overlaps),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Overlap detection failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get audience overlaps.
     */
    public function getOverlaps(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $overlaps = AudienceOverlap::where('org_id', $orgId)
            ->where('status', 'active')
            ->with(['campaignA', 'campaignB'])
            ->orderByDesc('overlap_percentage')
            ->get();

        return response()->json([
            'success' => true,
            'overlaps' => $overlaps,
        ]);
    }

    /**
     * Resolve audience overlap.
     */
    public function resolveOverlap(Request $request, string $overlapId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resolution_action' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $orgId = $request->user()->org_id;

        $overlap = AudienceOverlap::where('org_id', $orgId)
            ->where('overlap_id', $overlapId)
            ->firstOrFail();

        $overlap->resolve($request->resolution_action);

        return response()->json([
            'success' => true,
            'message' => 'Overlap resolved successfully',
            'overlap' => $overlap,
        ]);
    }

    // ===== Attribution =====

    /**
     * Calculate attribution for a conversion.
     */
    public function calculateAttribution(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'conversion_id' => 'required|string',
            'touchpoints' => 'required|array|min:1',
            'conversion_value' => 'required|numeric|min:0',
            'model_type' => 'required|in:first_touch,last_touch,linear,time_decay,position_based,data_driven',
            'lookback_days' => 'nullable|integer|min:1|max:90',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $orgId = $request->user()->org_id;

            $attribution = $this->attributionEngine->attributeConversion(
                $orgId,
                $request->conversion_id,
                $request->touchpoints,
                $request->conversion_value,
                $request->model_type,
                $request->lookback_days ?? 30
            );

            return response()->json([
                'success' => true,
                'attribution' => $attribution,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Attribution calculation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get attribution report.
     */
    public function getAttributionReport(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        $orgId = $request->user()->org_id;

        $report = $this->attributionEngine->generateAttributionReport($orgId, $days);

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    // ===== Creative Performance =====

    /**
     * Analyze creative performance.
     */
    public function analyzeCreatives(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'campaign_ids' => 'nullable|array',
            'campaign_ids.*' => 'uuid|exists:cmis.campaigns,campaign_id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $orgId = $request->user()->org_id;

            $performances = $this->creativeAnalyzer->analyzeCreatives(
                $orgId,
                $request->campaign_ids
            );

            return response()->json([
                'success' => true,
                'performances' => $performances,
                'count' => count($performances),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Creative analysis failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get creative performance report.
     */
    public function getCreativeReport(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        $orgId = $request->user()->org_id;

        $report = $this->creativeAnalyzer->generateCreativeReport($orgId, $days);

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    // ===== Optimization Insights =====

    /**
     * Generate optimization insights.
     */
    public function generateInsights(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'campaign_ids' => 'nullable|array',
            'campaign_ids.*' => 'uuid|exists:cmis.campaigns,campaign_id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        try {
            $orgId = $request->user()->org_id;

            $insights = $this->insightGenerator->generateInsights(
                $orgId,
                $request->campaign_ids
            );

            return response()->json([
                'success' => true,
                'insights' => $insights,
                'count' => count($insights),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Insight generation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get optimization insights.
     */
    public function getInsights(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $query = OptimizationInsight::where('org_id', $orgId);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Only actionable insights
        if ($request->boolean('actionable_only')) {
            $query->actionable();
        }

        $insights = $query->orderBy('priority')->orderByDesc('impact_estimate')->get();

        return response()->json([
            'success' => true,
            'insights' => $insights,
        ]);
    }

    /**
     * Acknowledge insight.
     */
    public function acknowledgeInsight(Request $request, string $insightId): JsonResponse
    {
        $orgId = $request->user()->org_id;
        $userId = $request->user()->user_id;

        $insight = OptimizationInsight::where('org_id', $orgId)
            ->where('insight_id', $insightId)
            ->firstOrFail();

        $insight->acknowledge($userId);

        return response()->json([
            'success' => true,
            'message' => 'Insight acknowledged',
            'insight' => $insight,
        ]);
    }

    /**
     * Apply insight.
     */
    public function applyInsight(Request $request, string $insightId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action_taken' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $orgId = $request->user()->org_id;
        $userId = $request->user()->user_id;

        $insight = OptimizationInsight::where('org_id', $orgId)
            ->where('insight_id', $insightId)
            ->firstOrFail();

        $insight->apply($userId, $request->action_taken);

        return response()->json([
            'success' => true,
            'message' => 'Insight applied successfully',
            'insight' => $insight,
        ]);
    }

    /**
     * Dismiss insight.
     */
    public function dismissInsight(Request $request, string $insightId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $insight = OptimizationInsight::where('org_id', $orgId)
            ->where('insight_id', $insightId)
            ->firstOrFail();

        $insight->dismiss();

        return response()->json([
            'success' => true,
            'message' => 'Insight dismissed',
        ]);
    }

    // ===== Optimization Runs =====

    /**
     * Get optimization runs.
     */
    public function getOptimizationRuns(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $runs = OptimizationRun::where('org_id', $orgId)
            ->with(['model', 'executor'])
            ->orderByDesc('started_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'runs' => $runs,
        ]);
    }

    /**
     * Get optimization run details.
     */
    public function getOptimizationRun(Request $request, string $runId): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $run = OptimizationRun::where('org_id', $orgId)
            ->where('run_id', $runId)
            ->with(['model', 'budgetAllocations', 'insights'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'run' => $run,
        ]);
    }

    // ===== Optimization Models =====

    /**
     * Get optimization models.
     */
    public function getOptimizationModels(Request $request): JsonResponse
    {
        $orgId = $request->user()->org_id;

        $models = OptimizationModel::where('org_id', $orgId)
            ->orderByDesc('deployed_at')
            ->get();

        return response()->json([
            'success' => true,
            'models' => $models,
        ]);
    }
}
