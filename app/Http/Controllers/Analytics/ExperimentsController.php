<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Analytics\Experiment;
use App\Models\Analytics\ExperimentVariant;
use App\Services\Analytics\ExperimentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Experiments Controller (Phase 15)
 *
 * Manages A/B testing experiments, variants, and results
 */
class ExperimentsController extends Controller
{
    use ApiResponse;

    public function __construct(protected ExperimentService $experimentService)
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * List experiments
     * GET /api/orgs/{org_id}/experiments
     */
    public function index(string $orgId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $query = Experiment::where('org_id', $orgId)
            ->with(['creator', 'variants']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('experiment_type')) {
            $query->ofType($request->input('experiment_type'));
        }

        if ($request->has('entity_type') && $request->has('entity_id')) {
            $query->where('entity_type', $request->input('entity_type'))
                  ->where('entity_id', $request->input('entity_id'));
        }

        $experiments = $query->latest('created_at')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'experiments' => $experiments
        ]);
    }

    /**
     * Create experiment
     * POST /api/orgs/{org_id}/experiments
     */
    public function store(string $orgId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'sometimes|string',
            'experiment_type' => 'required|in:campaign,content,audience,budget',
            'entity_type' => 'sometimes|string|max:50',
            'entity_id' => 'sometimes|uuid',
            'metric' => 'required|string|max:100',
            'metrics' => 'sometimes|array',
            'hypothesis' => 'sometimes|string|max:500',
            'duration_days' => 'sometimes|integer|min:1|max:90',
            'sample_size_per_variant' => 'sometimes|integer|min:100',
            'confidence_level' => 'sometimes|numeric|min:90|max:99.9',
            'minimum_detectable_effect' => 'sometimes|numeric|min:1|max:50',
            'traffic_allocation' => 'sometimes|in:equal,weighted,adaptive',
            'config' => 'sometimes|array',
            'control_config' => 'sometimes|array'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $experiment = $this->experimentService->createExperiment($orgId, $user->user_id, $validated);

        return response()->json([
            'success' => true,
            'experiment' => $experiment->load(['creator', 'variants']),
            'message' => 'Experiment created successfully'
        ], 201);
    }

    /**
     * Get experiment details
     * GET /api/orgs/{org_id}/experiments/{experiment_id}
     */
    public function show(string $orgId, string $experimentId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $experiment = Experiment::with(['creator', 'variants', 'results'])
            ->findOrFail($experimentId);

        return response()->json([
            'success' => true,
            'experiment' => $experiment,
            'performance' => $this->experimentService->getPerformanceSummary($experiment)
        ]);
    }

    /**
     * Update experiment
     * PUT /api/orgs/{org_id}/experiments/{experiment_id}
     */
    public function update(string $orgId, string $experimentId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'duration_days' => 'sometimes|integer|min:1|max:90',
            'confidence_level' => 'sometimes|numeric|min:90|max:99.9',
            'minimum_detectable_effect' => 'sometimes|numeric|min:1|max:50',
            'config' => 'sometimes|array'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $experiment = Experiment::findOrFail($experimentId);

        if ($experiment->status !== 'draft') {
            return $this->error('Can only update draft experiments', 422);
        }

        $experiment->update($validated);

        return response()->json([
            'success' => true,
            'experiment' => $experiment->fresh(['creator', 'variants']),
            'message' => 'Experiment updated successfully'
        ]);
    }

    /**
     * Delete experiment
     * DELETE /api/orgs/{org_id}/experiments/{experiment_id}
     */
    public function destroy(string $orgId, string $experimentId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $experiment = Experiment::findOrFail($experimentId);

        if ($experiment->status === 'running') {
            return $this->error('Cannot delete running experiment. Pause or complete it first.', 422);
        }

        $experiment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Experiment deleted successfully'
        ]);
    }

    /**
     * Add variant to experiment
     * POST /api/orgs/{org_id}/experiments/{experiment_id}/variants
     */
    public function addVariant(string $orgId, string $experimentId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'sometimes|string',
            'traffic_percentage' => 'sometimes|numeric|min:0|max:100',
            'config' => 'required|array'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $experiment = Experiment::findOrFail($experimentId);
        $variant = $this->experimentService->addVariant($experiment, $validated);

        return response()->json([
            'success' => true,
            'variant' => $variant,
            'message' => 'Variant added successfully'
        ], 201);
    }

    /**
     * Update variant
     * PUT /api/orgs/{org_id}/experiments/{experiment_id}/variants/{variant_id}
     */
    public function updateVariant(
        string $orgId,
        string $experimentId,
        string $variantId,
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'sometimes|string',
            'traffic_percentage' => 'sometimes|numeric|min:0|max:100',
            'config' => 'sometimes|array',
            'status' => 'sometimes|in:active,paused,stopped'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $variant = ExperimentVariant::where('experiment_id', $experimentId)
            ->findOrFail($variantId);

        $variant->update($validated);

        return response()->json([
            'success' => true,
            'variant' => $variant->fresh(),
            'message' => 'Variant updated successfully'
        ]);
    }

    /**
     * Start experiment
     * POST /api/orgs/{org_id}/experiments/{experiment_id}/start
     */
    public function start(string $orgId, string $experimentId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $experiment = Experiment::findOrFail($experimentId);

        try {
            $experiment->start();

            return response()->json([
                'success' => true,
                'experiment' => $experiment->fresh(),
                'message' => 'Experiment started successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Pause experiment
     * POST /api/orgs/{org_id}/experiments/{experiment_id}/pause
     */
    public function pause(string $orgId, string $experimentId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $experiment = Experiment::findOrFail($experimentId);

        try {
            $experiment->pause();

            return response()->json([
                'success' => true,
                'experiment' => $experiment->fresh(),
                'message' => 'Experiment paused successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Resume experiment
     * POST /api/orgs/{org_id}/experiments/{experiment_id}/resume
     */
    public function resume(string $orgId, string $experimentId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $experiment = Experiment::findOrFail($experimentId);

        try {
            $experiment->resume();

            return response()->json([
                'success' => true,
                'experiment' => $experiment->fresh(),
                'message' => 'Experiment resumed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Complete experiment
     * POST /api/orgs/{org_id}/experiments/{experiment_id}/complete
     */
    public function complete(string $orgId, string $experimentId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $experiment = Experiment::findOrFail($experimentId);

        // Calculate statistical significance
        $significanceResults = $this->experimentService->calculateStatisticalSignificance($experiment);

        // Determine winner
        $winner = $this->experimentService->determineWinner($experiment);

        $experiment->complete(
            $winner?->variant_id,
            $winner ? $winner->improvement_over_control : null
        );

        return response()->json([
            'success' => true,
            'experiment' => $experiment->fresh(['variants']),
            'winner' => $winner,
            'significance_results' => $significanceResults,
            'message' => 'Experiment completed successfully'
        ]);
    }

    /**
     * Record experiment event
     * POST /api/orgs/{org_id}/experiments/{experiment_id}/events
     */
    public function recordEvent(string $orgId, string $experimentId, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => 'required|uuid',
            'event_type' => 'required|in:impression,click,conversion,custom',
            'user_id' => 'sometimes|string',
            'session_id' => 'sometimes|string',
            'value' => 'sometimes|numeric',
            'properties' => 'sometimes|array'
        ]);

        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $event = $this->experimentService->recordEvent(
            $experimentId,
            $validated['variant_id'],
            $validated['event_type'],
            $validated
        );

        return response()->json([
            'success' => true,
            'event' => $event,
            'message' => 'Event recorded successfully'
        ], 201);
    }

    /**
     * Get experiment results
     * GET /api/orgs/{org_id}/experiments/{experiment_id}/results
     */
    public function results(string $orgId, string $experimentId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $experiment = Experiment::with(['variants', 'results'])->findOrFail($experimentId);

        $performance = $this->experimentService->getPerformanceSummary($experiment);
        $timeSeries = $this->experimentService->getTimeSeriesData($experiment);

        if ($experiment->status === 'running' || $experiment->status === 'completed') {
            $significance = $this->experimentService->calculateStatisticalSignificance($experiment);
        } else {
            $significance = [];
        }

        return response()->json([
            'success' => true,
            'performance' => $performance,
            'time_series' => $timeSeries,
            'statistical_significance' => $significance
        ]);
    }

    /**
     * Get experiment statistics
     * GET /api/orgs/{org_id}/experiments/stats
     */
    public function stats(string $orgId, Request $request): JsonResponse
    {
        $user = $request->user();

        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            $user->user_id,
            $orgId
        ]);

        $stats = [
            'total_experiments' => Experiment::where('org_id', $orgId)->count(),
            'running_experiments' => Experiment::where('org_id', $orgId)->running()->count(),
            'completed_experiments' => Experiment::where('org_id', $orgId)->completed()->count(),
            'draft_experiments' => Experiment::where('org_id', $orgId)->where('status', 'draft')->count(),
            'experiments_with_winner' => Experiment::where('org_id', $orgId)
                ->whereNotNull('winner_variant_id')
                ->count()
        ];

        // Recent experiments
        $stats['recent_experiments'] = Experiment::where('org_id', $orgId)
            ->with(['creator', 'variants'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }
}
