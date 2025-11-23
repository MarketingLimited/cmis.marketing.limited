<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\AiRecommendation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * AI Recommendations Controller
 *
 * Handles AI-powered recommendations and suggestions
 */
class AIRecommendationsController extends Controller
{
    use ApiResponse;

    /**
     * Get AI-powered recommendations
     */
    public function index(Request $request, string $orgId)
    {
        Gate::authorize('ai.view_recommendations');

        try {
            $type = $request->input('type', 'all'); // all, campaign, content, optimization

            $query = AiRecommendation::where('org_id', $orgId)
                ->where('is_active', true)
                ->orderBy('priority', 'desc')
                ->orderBy('created_at', 'desc');

            if ($type !== 'all') {
                $query->where('recommendation_type', $type);
            }

            $recommendations = $query->limit(20)->get()->map(function ($rec) {
                return [
                    'id' => $rec->recommendation_id,
                    'type' => $rec->recommendation_type,
                    'title' => $rec->title,
                    'description' => $rec->description,
                    'priority' => $rec->priority,
                    'confidence' => $rec->confidence_score,
                    'created_at' => $rec->created_at,
                ];
            });

            return $this->success([
                'recommendations' => $recommendations,
                'count' => $recommendations->count(),
            ], 'Recommendations retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch recommendations');
        }
    }
}
