<?php

namespace App\Http\Controllers;

use App\Services\CMIS\SemanticSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Search Controller (Phase 1 Week 3 - Task 3.2)
 *
 * Provides API endpoints for semantic search across CMIS entities
 * using pgvector and Google Gemini embeddings.
 *
 * Endpoints:
 * - POST /api/search/semantic - Universal semantic search
 * - POST /api/search/campaigns - Campaign-specific search
 * - GET /api/search/similar/{entity_type}/{id} - Find similar entities
 */
class SearchController extends Controller
{
    use ApiResponse;

    protected SemanticSearchService $searchService;

    public function __construct(SemanticSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Universal semantic search across all entity types
     *
     * POST /api/search/semantic
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function semantic(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:3|max:500',
            'entity_types' => 'sometimes|array',
            'entity_types.*' => 'in:campaigns,content,creatives',
            'limit' => 'sometimes|integer|min:1|max:50',
            'use_cache' => 'sometimes|boolean'
        ]);

        $query = $validated['query'];
        $entityTypes = $validated['entity_types'] ?? ['campaigns', 'content', 'creatives'];
        $limit = $validated['limit'] ?? 10;
        $useCache = $validated['use_cache'] ?? true;

        try {
            $results = $this->searchService->universalSearch(
                $query,
                $limit,
                $entityTypes
            );

            return response()->json($results);

        } catch (\Exception $e) {
            Log::error('Universal search failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Search failed',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Campaign-specific semantic search
     *
     * POST /api/search/campaigns
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function campaigns(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:3|max:500',
            'limit' => 'sometimes|integer|min:1|max:50',
            'threshold' => 'sometimes|numeric|min:0|max:1',
            'use_cache' => 'sometimes|boolean'
        ]);

        $query = $validated['query'];
        $limit = $validated['limit'] ?? 10;
        $threshold = $validated['threshold'] ?? 0.7;
        $useCache = $validated['use_cache'] ?? true;

        try {
            $results = $useCache
                ? $this->searchService->searchWithCache($query, $limit, $threshold)
                : $this->searchService->searchCampaigns($query, $limit, $threshold);

            return response()->json($results);

        } catch (\Exception $e) {
            Log::error('Campaign search failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Search failed',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Find similar entities based on entity ID
     *
     * GET /api/search/similar/{entity_type}/{id}
     *
     * @param Request $request
     * @param string $entityType Entity type (campaigns, content)
     * @param string $id Entity ID
     * @return JsonResponse
     */
    public function similar(Request $request, string $entityType, string $id): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'sometimes|integer|min:1|max:20',
        ]);

        $limit = $validated['limit'] ?? 5;

        try {
            $results = match ($entityType) {
                'campaigns' => $this->searchService->findSimilarCampaigns($id, $limit),
                'content' => $this->searchService->findSimilar($id, $limit),
                default => [
                    'success' => false,
                    'error' => 'Invalid entity type. Supported: campaigns, content'
                ]
            };

            if (!$results['success']) {
                return response()->json($results, 400);
            }

            return response()->json($results);

        } catch (\Exception $e) {
            Log::error('Similar search failed', [
                'entity_type' => $entityType,
                'entity_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Search failed',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Get search statistics
     *
     * GET /api/search/stats
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            // Get embedding statistics
            $stats = [
                'total_embeddings' => \DB::table('cmis.embeddings_cache')->count(),
                'campaign_embeddings' => \DB::table('cmis_ai.campaign_embeddings')->count(),
                'content_embeddings' => \DB::table('cmis_ai.content_embeddings')->count(),
                'creative_embeddings' => \DB::table('cmis_ai.creative_embeddings')->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve stats'
            ], 500);
        }
    }
}
