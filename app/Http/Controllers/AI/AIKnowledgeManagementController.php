<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\CMIS\KnowledgeItem;
use App\Repositories\Knowledge\KnowledgeRepository;
use App\Repositories\Knowledge\EmbeddingRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * AI Knowledge Management Controller
 *
 * Handles knowledge base CRUD and embedding operations
 */
class AIKnowledgeManagementController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected KnowledgeRepository $knowledgeRepo,
        protected EmbeddingRepository $embeddingRepo
    ) {}

    /**
     * Get knowledge base items
     */
    public function index(Request $request, string $orgId)
    {
        Gate::authorize('ai.manage_knowledge');

        try {
            $category = $request->input('category'); // dev, marketing, org, research
            $domain = $request->input('domain');
            $limit = $request->input('limit', 50);

            $query = KnowledgeItem::where('is_deprecated', false);

            if ($category) {
                $query->where('category', $category);
            }

            if ($domain) {
                $query->where('domain', $domain);
            }

            $items = $query->orderBy('tier', 'asc')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'knowledge_id' => $item->knowledge_id,
                        'domain' => $item->domain,
                        'topic' => $item->topic,
                        'category' => $item->category,
                        'tier' => $item->tier,
                        'keywords' => $item->keywords,
                        'has_embedding' => !empty($item->topic_embedding),
                    ];
                });

            return $this->success([
                'items' => $items,
                'count' => $items->count(),
            ], 'Knowledge items retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to fetch knowledge items');
        }
    }

    /**
     * Process and vectorize knowledge documents
     */
    public function process(Request $request, string $orgId)
    {
        Gate::authorize('ai.manage_knowledge');

        $validator = Validator::make($request->all(), [
            'knowledge_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation Error');
        }

        try {
            $knowledgeId = $request->input('knowledge_id');

            $item = KnowledgeItem::where('knowledge_id', $knowledgeId)->firstOrFail();

            // Generate embedding using the EmbeddingRepository
            $result = $this->embeddingRepo->updateSingleEmbedding($knowledgeId);

            if ($result && isset($result->success) && $result->success) {
                return $this->success([
                    'knowledge_id' => $knowledgeId,
                    'status' => 'completed',
                    'result' => $result,
                ], 'Knowledge item processed successfully');
            }

            return $this->serverError('Failed to process knowledge item');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound('Knowledge item not found');
        } catch (\Exception $e) {
            Log::error('Knowledge processing failed: ' . $e->getMessage());
            return $this->serverError('Knowledge processing failed');
        }
    }

    /**
     * Batch process knowledge embeddings
     */
    public function batchProcess(Request $request, string $orgId)
    {
        Gate::authorize('ai.manage_knowledge');

        $validator = Validator::make($request->all(), [
            'batch_size' => 'nullable|integer|min:1|max:500',
            'category' => 'nullable|string|in:dev,marketing,org,research',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation Error');
        }

        try {
            $batchSize = $request->input('batch_size', 100);
            $category = $request->input('category');

            $result = $this->embeddingRepo->batchUpdateEmbeddings($batchSize, $category);

            return $this->success([
                'result' => $result,
            ], 'Batch processing completed successfully');

        } catch (\Exception $e) {
            Log::error('Batch processing failed: ' . $e->getMessage());
            return $this->serverError('Batch processing failed');
        }
    }

    /**
     * Register new knowledge entry
     */
    public function register(Request $request, string $orgId)
    {
        Gate::authorize('ai.manage_knowledge');

        $validator = Validator::make($request->all(), [
            'domain' => 'required|string|max:255',
            'category' => 'required|string|in:dev,marketing,org,research',
            'topic' => 'required|string|max:500',
            'content' => 'required|string',
            'tier' => 'nullable|integer|min:1|max:5',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation Error');
        }

        try {
            $knowledgeId = $this->knowledgeRepo->registerKnowledge(
                $request->input('domain'),
                $request->input('category'),
                $request->input('topic'),
                $request->input('content'),
                $request->input('tier', 2),
                $request->input('keywords', [])
            );

            return $this->created([
                'knowledge_id' => $knowledgeId,
            ], 'Knowledge registered successfully');

        } catch (\Exception $e) {
            Log::error('Knowledge registration failed: ' . $e->getMessage());
            return $this->serverError('Knowledge registration failed');
        }
    }

    /**
     * Advanced semantic search
     */
    public function advancedSearch(Request $request, string $orgId)
    {
        Gate::authorize('ai.semantic_search');

        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:500',
            'intent' => 'nullable|string',
            'direction' => 'nullable|string',
            'purpose' => 'nullable|string',
            'category' => 'nullable|string|in:dev,marketing,org,research',
            'limit' => 'nullable|integer|min:1|max:100',
            'threshold' => 'nullable|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation Error');
        }

        try {
            $results = $this->knowledgeRepo->semanticSearchAdvanced(
                $request->input('query'),
                $request->input('intent'),
                $request->input('direction'),
                $request->input('purpose'),
                $request->input('category'),
                $request->input('limit', 10),
                $request->input('threshold', 0.3)
            );

            return $this->success([
                'query' => $request->input('query'),
                'results' => $results,
                'count' => $results->count(),
            ], 'Advanced semantic search completed successfully');

        } catch (\Exception $e) {
            Log::error('Advanced semantic search failed: ' . $e->getMessage());
            return $this->serverError('Advanced semantic search failed');
        }
    }

    /**
     * Auto analyze knowledge
     */
    public function analyze(Request $request, string $orgId)
    {
        Gate::authorize('ai.view_insights');

        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:500',
            'domain' => 'nullable|string|max:255',
            'category' => 'nullable|string|in:dev,marketing,org,research',
            'max_batches' => 'nullable|integer|min:1|max:20',
            'batch_limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation Error');
        }

        try {
            $analysis = $this->knowledgeRepo->autoAnalyzeKnowledge(
                $request->input('query'),
                $request->input('domain'),
                $request->input('category', 'dev'),
                $request->input('max_batches', 5),
                $request->input('batch_limit', 20)
            );

            return $this->success([
                'query' => $request->input('query'),
                'analysis' => $analysis,
            ], 'Knowledge analysis completed successfully');

        } catch (\Exception $e) {
            Log::error('Knowledge analysis failed: ' . $e->getMessage());
            return $this->serverError('Knowledge analysis failed');
        }
    }

    /**
     * Smart context loader for RAG
     */
    public function loadContext(Request $request, string $orgId)
    {
        Gate::authorize('ai.semantic_search');

        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:500',
            'domain' => 'nullable|string|max:255',
            'category' => 'nullable|string|in:dev,marketing,org,research',
            'token_limit' => 'nullable|integer|min:100|max:50000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Validation Error');
        }

        try {
            $context = $this->knowledgeRepo->smartContextLoader(
                $request->input('query'),
                $request->input('domain'),
                $request->input('category', 'dev'),
                $request->input('token_limit', 5000)
            );

            return $this->success([
                'query' => $request->input('query'),
                'context' => $context,
            ], 'Context loaded successfully');

        } catch (\Exception $e) {
            Log::error('Context loading failed: ' . $e->getMessage());
            return $this->serverError('Context loading failed');
        }
    }

    /**
     * Get system report
     */
    public function systemReport(Request $request)
    {
        Gate::authorize('ai.view_insights');

        try {
            $report = $this->knowledgeRepo->generateSystemReport();

            return $this->success([
                'report' => $report,
            ], 'System report generated successfully');

        } catch (\Exception $e) {
            Log::error('System report generation failed: ' . $e->getMessage());
            return $this->serverError('System report generation failed');
        }
    }

    /**
     * Cleanup old embeddings
     */
    public function cleanup(Request $request)
    {
        Gate::authorize('ai.manage_knowledge');

        try {
            $success = $this->knowledgeRepo->cleanupOldEmbeddings();

            return $this->success([
                'success' => $success,
            ], $success ? 'Embeddings cleaned up successfully' : 'Cleanup failed');

        } catch (\Exception $e) {
            Log::error('Embeddings cleanup failed: ' . $e->getMessage());
            return $this->serverError('Embeddings cleanup failed');
        }
    }
}
