<?php

namespace App\Http\Controllers\Intelligence;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Intelligence\Recommendation;
use App\Services\Intelligence\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RecommendationController extends Controller
{
    use ApiResponse;

    protected RecommendationService $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    /**
     * Display a listing of recommendations
     */
    public function index(Request $request)
    {
        $orgId = session('current_org_id');

        $recommendations = Recommendation::where('org_id', $orgId)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->byPriority($request->priority))
            ->when($request->type, fn($q) => $q->byType($request->type))
            ->when($request->entity_type, fn($q) => $q->where('entity_type', $request->entity_type))
            ->when($request->pending_only, fn($q) => $q->pending())
            ->when($request->high_priority_only, fn($q) => $q->highPriority())
            ->with(['creator', 'appliedByUser', 'entity'])
            ->latest('created_at')
            ->paginate($request->get('per_page', 20));

        if ($request->expectsJson()) {
            return $this->paginated($recommendations, 'Recommendations retrieved successfully');
        }

        return view('intelligence.recommendations.index', compact('recommendations'));
    }

    /**
     * Display the specified recommendation
     */
    public function show(string $id)
    {
        $recommendation = Recommendation::with(['creator', 'appliedByUser', 'entity'])
            ->findOrFail($id);

        if (request()->expectsJson()) {
            return $this->success($recommendation, 'Recommendation retrieved successfully');
        }

        return view('intelligence.recommendations.show', compact('recommendation'));
    }

    /**
     * Generate recommendations for an entity
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string',
            'entity_id' => 'required|uuid',
            'types' => 'nullable|array',
            'min_confidence' => 'nullable|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $recommendations = $this->recommendationService->generateRecommendations(
            $request->entity_type,
            $request->entity_id,
            $request->types,
            $request->min_confidence ?? 0.5
        );

        return $this->success($recommendations, 'Recommendations generated successfully');
    }

    /**
     * Apply a recommendation
     */
    public function apply(Request $request, string $id)
    {
        $recommendation = Recommendation::findOrFail($id);

        $result = $this->recommendationService->applyRecommendation(
            $recommendation,
            auth()->id()
        );

        if ($request->expectsJson()) {
            return $this->success($result, 'Recommendation applied successfully');
        }

        return redirect()->route('recommendations.show', $recommendation->recommendation_id)
            ->with('success', __('intelligence.applied_success'));
    }

    /**
     * Reject a recommendation
     */
    public function reject(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $recommendation = Recommendation::findOrFail($id);
        $recommendation->reject(auth()->id(), $request->reason);

        if ($request->expectsJson()) {
            return $this->success($recommendation, 'Recommendation rejected');
        }

        return redirect()->route('recommendations.index')
            ->with('success', __('intelligence.rejected'));
    }

    /**
     * Dismiss a recommendation
     */
    public function dismiss(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $recommendation = Recommendation::findOrFail($id);
        $recommendation->dismiss(auth()->id(), $request->reason);

        if ($request->expectsJson()) {
            return $this->success($recommendation, 'Recommendation dismissed');
        }

        return redirect()->route('recommendations.index')
            ->with('success', __('intelligence.dismissed'));
    }

    /**
     * Provide feedback on a recommendation
     */
    public function provideFeedback(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'helpful' => 'required|boolean',
            'actual_impact' => 'nullable|numeric',
            'feedback_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $recommendation = Recommendation::findOrFail($id);
        $recommendation->provideFeedback(
            $request->helpful,
            $request->actual_impact,
            $request->feedback_notes
        );

        if ($request->expectsJson()) {
            return $this->success($recommendation, 'Feedback recorded successfully');
        }

        return redirect()->route('recommendations.show', $recommendation->recommendation_id)
            ->with('success', __('intelligence.recorded_success'));
    }

    /**
     * Get recommendation analytics dashboard data
     */
    public function analytics(Request $request)
    {
        $orgId = session('current_org_id');

        $analytics = $this->recommendationService->getAnalytics($orgId);

        if ($request->expectsJson()) {
            return $this->success($analytics, 'Recommendation analytics retrieved successfully');
        }

        return view('intelligence.recommendations.analytics', compact('analytics'));
    }

    /**
     * Get recommendations summary
     */
    public function summary(Request $request)
    {
        $orgId = session('current_org_id');

        $summary = $this->recommendationService->getSummary($orgId, $request->days ?? 30);

        return $this->success($summary, 'Recommendation summary retrieved successfully');
    }

    /**
     * Get recommendations by entity
     */
    public function byEntity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entity_type' => 'required|string',
            'entity_id' => 'required|uuid',
            'status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation failed');
        }

        $recommendations = Recommendation::where('entity_type', $request->entity_type)
            ->where('entity_id', $request->entity_id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->with(['creator', 'appliedByUser'])
            ->latest('created_at')
            ->get();

        return $this->success($recommendations, 'Entity recommendations retrieved successfully');
    }
}
