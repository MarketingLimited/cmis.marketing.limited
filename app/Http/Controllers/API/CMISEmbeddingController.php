<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CMIS\KnowledgeEmbeddingProcessor;
use App\Services\CMIS\SemanticSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CMISEmbeddingController extends Controller
{
    private KnowledgeEmbeddingProcessor $processor;
    private SemanticSearchService $searchService;

    public function __construct(
        KnowledgeEmbeddingProcessor $processor,
        SemanticSearchService $searchService
    ) {
        $this->processor = $processor;
        $this->searchService = $searchService;
    }

    /**
     * Search knowledge base
     */
    public function search(Request $request): JsonResponse
    {
        Gate::authorize('useSemanticSearch', auth()->user());
        $validated = $request->validate([
            'query' => 'required|string|max:1000',
            'intent' => 'nullable|string|max:500',
            'direction' => 'nullable|string|max:500',
            'purpose' => 'nullable|string|max:500',
            'limit' => 'nullable|integer|min:1|max:100',
            'threshold' => 'nullable|numeric|min:0|max:1'
        ]);
        
        try {
            $results = $this->searchService->search(
                $validated['query'],
                $validated['intent'] ?? null,
                $validated['direction'] ?? null,
                $validated['purpose'] ?? null,
                $validated['limit'] ?? 10,
                $validated['threshold'] ?? 0.7
            );
            
            return response()->json([
                'success' => true,
                'data' => $results,
                'count' => count($results)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Process specific knowledge item
     */
    public function processKnowledge(Request $request, string $knowledgeId): JsonResponse
    {
        Gate::authorize('manageKnowledge', auth()->user());

        try {
            $success = $this->processor->processSpecificKnowledge($knowledgeId);
            
            return response()->json([
                'success' => $success,
                'message' => $success 
                    ? "Knowledge {$knowledgeId} processed successfully"
                    : "Failed to process knowledge {$knowledgeId}"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Processing failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get similar knowledge items
     */
    public function findSimilar(Request $request, string $knowledgeId): JsonResponse
    {
        Gate::authorize('useSemanticSearch', auth()->user());

        $limit = $request->input('limit', 5);
        
        try {
            $results = $this->searchService->findSimilar($knowledgeId, $limit);
            
            return response()->json([
                'success' => true,
                'data' => $results,
                'count' => count($results)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get system status
     */
    public function status(): JsonResponse
    {
        try {
            $stats = \DB::connection(config('cmis-embeddings.database.connection'))
                ->select("SELECT * FROM cmis_knowledge.generate_system_report()");
            
            return response()->json([
                'success' => true,
                'data' => $stats[0] ?? []
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get status: ' . $e->getMessage()
            ], 500);
        }
    }
}