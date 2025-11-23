<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CMIS\KnowledgeEmbeddingProcessor;
use App\Services\CMIS\SemanticSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Concerns\ApiResponse;

class CMISEmbeddingController extends Controller
{
    use ApiResponse;

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
            
            return $this->success($results, 'Operation completed successfully');
            
        } catch (\Exception $e) {
            return $this->serverError('Search failed: ');
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
            
            return $this->success(['success' => $success,
                'message' => $success 
                    ? "Knowledge {$knowledgeId} processed successfully"
                    : "Failed to process knowledge {$knowledgeId}"
            ], 'Operation completed successfully');
            
        } catch (\Exception $e) {
            return $this->serverError('Processing failed: ');
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
            
            return $this->success($results, 'Operation completed successfully');
            
        } catch (\Exception $e) {
            return $this->serverError('Search failed: ');
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
            return $this->serverError('Failed to get status: ');
        }
    }
}