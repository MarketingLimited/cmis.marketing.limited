<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\CMIS\SemanticSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * AI Semantic Search Controller
 *
 * Handles pgvector-powered semantic search operations
 */
class AISemanticSearchController extends Controller
{
    use ApiResponse;

    /**
     * Perform semantic search using pgvector
     */
    public function search(Request $request, string $orgId, SemanticSearchService $searchService)
    : \Illuminate\Http\JsonResponse {
        Gate::authorize('ai.semantic_search');

        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:500',
            'sources' => 'nullable|array',
            'sources.*' => 'nullable|string|in:knowledge,campaigns,assets,posts',
            'limit' => 'nullable|integer|min:1|max:50',
            'threshold' => 'nullable|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation Error');
        }

        try {
            $query = $request->input('query');
            $sources = $request->input('sources', ['knowledge']);
            $limit = $request->input('limit', 10);
            $threshold = $request->input('threshold', 0.7);

            // Use the existing SemanticSearchService
            $results = $searchService->search($query, $limit);

            // Filter results by threshold if needed
            $filteredResults = collect($results)->filter(function ($result) use ($threshold) {
                return ($result['similarity'] ?? 0) >= $threshold;
            })->values();

            return $this->success([
                'query' => $query,
                'results' => $filteredResults,
                'count' => $filteredResults->count(),
                'sources' => $sources,
                'timestamp' => now()->toIso8601String(),
            ], 'Semantic search completed successfully');

        } catch (\Exception $e) {
            Log::error('Semantic search failed', [
                'org_id' => $orgId,
                'query' => $request->input('query'),
                'error' => $e->getMessage(),
            ]);

            return $this->serverError('Semantic search failed');
        }
    }
}
