<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Vector Embeddings v2.0 API Controller
 *
 * يوفر endpoints كاملة لجميع ميزات Vector Embeddings v2.0
 * بما في ذلك الدوال الجديدة: hybrid_search, smart_context_loader_v2, إلخ
 */
class VectorEmbeddingsV2Controller extends Controller
{
    use ApiResponse;

    /**
     * البحث الدلالي المتقدم مع النوايا والمقاصد والاتجاهات
     *
     * POST /api/v2/vector/semantic-search
     */
    public function semanticSearchAdvanced(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:2000',
            'intent' => 'nullable|string|max:200',
            'direction' => 'nullable|string|max:200',
            'purpose' => 'nullable|string|max:200',
            'category' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:100',
            'threshold' => 'nullable|numeric|min:0|max:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $results = DB::select(
                'SELECT * FROM cmis_knowledge.semantic_search_advanced(?, ?, ?, ?, ?, ?, ?)',
                [
                    $request->input('query'),
                    $request->input('intent'),
                    $request->input('direction'),
                    $request->input('purpose'),
                    $request->input('category'),
                    $request->input('limit', 10),
                    $request->input('threshold', 0.7)
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
                'count' => count($results),
                'query' => $request->input('query'),
                'filters' => [
                    'intent' => $request->input('intent'),
                    'direction' => $request->input('direction'),
                    'purpose' => $request->input('purpose'),
                    'category' => $request->input('category')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Semantic search failed', [
                'error' => $e->getMessage(),
                'query' => $request->input('query')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * البحث الهجين (نصي + vector)
     *
     * POST /api/v2/vector/hybrid-search
     */
    public function hybridSearch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text_query' => 'required|string|max:2000',
            'vector_query' => 'nullable|string|max:2000',
            'weight_text' => 'nullable|numeric|min:0|max:1',
            'weight_vector' => 'nullable|numeric|min:0|max:1',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $results = DB::select(
                'SELECT * FROM cmis_knowledge.hybrid_search(?, ?, ?, ?, ?)',
                [
                    $request->input('text_query'),
                    $request->input('vector_query'),
                    $request->input('weight_text', 0.3),
                    $request->input('weight_vector', 0.7),
                    $request->input('limit', 10)
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $results,
                'count' => count($results),
                'search_type' => 'hybrid',
                'weights' => [
                    'text' => $request->input('weight_text', 0.3),
                    'vector' => $request->input('weight_vector', 0.7)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Hybrid search failed', [
                'error' => $e->getMessage(),
                'query' => $request->input('text_query')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Hybrid search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحميل السياق الذكي v2
     *
     * POST /api/v2/vector/smart-context
     */
    public function smartContextLoader(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:2000',
            'intent' => 'nullable|string|max:200',
            'direction' => 'nullable|string|max:200',
            'purpose' => 'nullable|string|max:200',
            'domain' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'token_limit' => 'nullable|integer|min:100|max:20000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = DB::selectOne(
                'SELECT cmis_knowledge.smart_context_loader_v2(?, ?, ?, ?, ?, ?, ?) as context',
                [
                    $request->input('query'),
                    $request->input('intent'),
                    $request->input('direction'),
                    $request->input('purpose'),
                    $request->input('domain'),
                    $request->input('category', 'dev'),
                    $request->input('token_limit', 5000)
                ]
            );

            $context = json_decode($result->context, true);

            return response()->json([
                'success' => true,
                'data' => $context,
                'metadata' => [
                    'total_items' => $context['total_items'] ?? 0,
                    'estimated_tokens' => $context['estimated_tokens'] ?? 0,
                    'token_limit' => $request->input('token_limit', 5000)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Smart context loader failed', [
                'error' => $e->getMessage(),
                'query' => $request->input('query')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Context loading failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تسجيل معرفة جديدة مع vectors مخصصة
     *
     * POST /api/v2/vector/register-knowledge
     */
    public function registerKnowledgeWithVectors(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'domain' => 'required|string|max:200',
            'category' => 'required|string|max:100',
            'topic' => 'required|string|max:500',
            'content' => 'required|string',
            'intent_vector' => 'nullable|array',
            'direction_vector' => 'nullable|array',
            'purpose_vector' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // تحويل arrays إلى vector format إذا تم توفيرها
            $intentVector = $request->input('intent_vector')
                ? '[' . implode(',', $request->input('intent_vector')) . ']'
                : null;
            $directionVector = $request->input('direction_vector')
                ? '[' . implode(',', $request->input('direction_vector')) . ']'
                : null;
            $purposeVector = $request->input('purpose_vector')
                ? '[' . implode(',', $request->input('purpose_vector')) . ']'
                : null;

            $result = DB::selectOne(
                'SELECT cmis_knowledge.register_knowledge_with_vectors(?, ?, ?, ?, ?::vector, ?::vector, ?::vector) as knowledge_id',
                [
                    $request->input('domain'),
                    $request->input('category'),
                    $request->input('topic'),
                    $request->input('content'),
                    $intentVector,
                    $directionVector,
                    $purposeVector
                ]
            );

            return response()->json([
                'success' => true,
                'knowledge_id' => $result->knowledge_id,
                'message' => 'Knowledge registered successfully',
                'data' => [
                    'domain' => $request->input('domain'),
                    'category' => $request->input('category'),
                    'topic' => $request->input('topic'),
                    'has_custom_vectors' => $intentVector !== null
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Knowledge registration failed', [
                'error' => $e->getMessage(),
                'topic' => $request->input('topic')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * معالجة قائمة انتظار Embeddings
     *
     * POST /api/v2/vector/process-queue
     */
    public function processQueue(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'batch_size' => 'nullable|integer|min:1|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = DB::selectOne(
                'SELECT cmis_knowledge.process_embedding_queue(?) as result',
                [$request->input('batch_size', 10)]
            );

            $data = json_decode($result->result, true);

            return response()->json([
                'success' => true,
                'data' => $data,
                'summary' => sprintf(
                    'Processed %d items: %d successful, %d failed',
                    $data['processed'] ?? 0,
                    $data['successful'] ?? 0,
                    $data['failed'] ?? 0
                )
            ]);

        } catch (\Exception $e) {
            Log::error('Queue processing failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Queue processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض حالة Embeddings
     *
     * GET /api/v2/vector/embedding-status
     */
    public function embeddingStatus(): JsonResponse
    {
        try {
            $status = DB::select('SELECT * FROM cmis_knowledge.v_embedding_status');

            return response()->json([
                'success' => true,
                'data' => $status,
                'count' => count($status)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get embedding status', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحليل النوايا
     *
     * GET /api/v2/vector/intent-analysis
     */
    public function intentAnalysis(): JsonResponse
    {
        try {
            $analysis = DB::select('SELECT * FROM cmis_knowledge.v_intent_analysis');

            return response()->json([
                'success' => true,
                'data' => $analysis,
                'count' => count($analysis)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get intent analysis', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get analysis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * حالة قائمة الانتظار
     *
     * GET /api/v2/vector/queue-status
     */
    public function queueStatus(): JsonResponse
    {
        try {
            $status = DB::select('SELECT * FROM cmis_knowledge.v_embedding_queue_status');

            return response()->json([
                'success' => true,
                'data' => $status,
                'count' => count($status)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get queue status', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get queue status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * أداء البحث
     *
     * GET /api/v2/vector/search-performance
     */
    public function searchPerformance(): JsonResponse
    {
        try {
            $performance = DB::select('SELECT * FROM cmis_knowledge.v_search_performance LIMIT 24');

            return response()->json([
                'success' => true,
                'data' => $performance,
                'count' => count($performance)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get search performance', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get performance data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تقرير شامل للنظام
     *
     * GET /api/v2/vector/system-report
     */
    public function systemReport(): JsonResponse
    {
        try {
            $report = DB::selectOne('SELECT cmis_knowledge.generate_system_report() as report');
            $reportData = json_decode($report->report, true);

            return response()->json([
                'success' => true,
                'data' => $reportData,
                'generated_at' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate system report', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * التحقق من التثبيت
     *
     * GET /api/v2/vector/verify-installation
     */
    public function verifyInstallation(): JsonResponse
    {
        try {
            $verification = DB::selectOne('SELECT cmis_knowledge.verify_installation() as result');
            $verificationData = json_decode($verification->result, true);

            return response()->json([
                'success' => true,
                'data' => $verificationData,
                'verified_at' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            Log::error('Installation verification failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
