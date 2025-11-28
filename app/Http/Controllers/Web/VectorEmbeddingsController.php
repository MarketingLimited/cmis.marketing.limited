<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Vector Embeddings Web Controller
 *
 * واجهة ويب لمراقبة وإدارة نظام Vector Embeddings v2.0
 */
class VectorEmbeddingsController extends Controller
{
    /**
     * لوحة المعلومات الرئيسية
     */
    public function dashboard(): View
    {
        try {
            // حالة Embeddings
            $embeddingStatus = DB::select('SELECT * FROM cmis_knowledge.v_embedding_status');

            // حالة قائمة الانتظار
            $queueStatus = DB::select('SELECT * FROM cmis_knowledge.v_embedding_queue_status');

            // أداء البحث (آخر 24 ساعة)
            $searchPerformance = DB::select('SELECT * FROM cmis_knowledge.v_search_performance LIMIT 24');

            // تقرير النظام
            $systemReport = DB::selectOne('SELECT cmis_knowledge.generate_system_report() as report');
            $reportData = json_decode($systemReport->report, true);

            return view('vector-embeddings.dashboard', compact(
                'embeddingStatus',
                'queueStatus',
                'searchPerformance',
                'reportData'
            ));

        } catch (\Exception $e) {
            return view('vector-embeddings.dashboard')->with('error', $e->getMessage());
        }
    }

    /**
     * صفحة تحليل النوايا
     */
    public function intentAnalysis(): View
    {
        try {
            $intentAnalysis = DB::select('SELECT * FROM cmis_knowledge.v_intent_analysis');

            return view('vector-embeddings.intent-analysis', compact('intentAnalysis'));

        } catch (\Exception $e) {
            return view('vector-embeddings.intent-analysis')->with('error', $e->getMessage());
        }
    }

    /**
     * صفحة حالة قائمة الانتظار
     */
    public function queueManager(): View
    {
        try {
            $queueItems = DB::select('
                SELECT queue_id, knowledge_id, source_table, source_field,
                       status, priority, retry_count, error_message,
                       created_at, processing_started_at, processed_at
                FROM cmis_knowledge.embedding_update_queue
                WHERE status != \'completed\'
                ORDER BY priority DESC, created_at ASC
                LIMIT 100
            ');

            $queueStats = DB::select('SELECT * FROM cmis_knowledge.v_embedding_queue_status');

            return view('vector-embeddings.queue-manager', compact('queueItems', 'queueStats'));

        } catch (\Exception $e) {
            return view('vector-embeddings.queue-manager')->with('error', $e->getMessage());
        }
    }

    /**
     * صفحة البحث الدلالي
     */
    public function search(): View
    {
        return view('vector-embeddings.search');
    }

    /**
     * تنفيذ البحث الدلالي
     */
    public function executeSearch(Request $request): View
    {
        $request->validate([
            'query' => 'required|string|max:2000',
            'intent' => 'nullable|string|max:200',
            'direction' => 'nullable|string|max:200',
            'purpose' => 'nullable|string|max:200',
            'category' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:50',
            'threshold' => 'nullable|numeric|min:0|max:1',
            'search_type' => 'required|in:semantic,hybrid'
        ]);

        try {
            if ($request->input('search_type') === 'hybrid') {
                $results = DB::select(
                    'SELECT * FROM cmis_knowledge.hybrid_search(?, ?, 0.3, 0.7, ?)',
                    [
                        $request->input('query'),
                        null,
                        $request->input('limit', 10)
                    ]
                );
            } else {
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
            }

            return view('vector-embeddings.search', compact('results'))->with('query', $request->input('query'));

        } catch (\Exception $e) {
            return view('vector-embeddings.search')->with('error', $e->getMessage());
        }
    }

    /**
     * معالجة قائمة الانتظار (Action)
     */
    public function processQueue(Request $request): RedirectResponse
    {
        $request->validate([
            'batch_size' => 'nullable|integer|min:1|max:500'
        ]);

        try {
            $result = DB::selectOne(
                'SELECT cmis_knowledge.process_embedding_queue(?) as result',
                [$request->input('batch_size', 50)]
            );

            $data = json_decode($result->result, true);

            return redirect()->route('vector-embeddings.queue')
                ->with('success', sprintf(
                    'تمت معالجة %d عنصر: %d ناجح، %d فشل',
                    $data['processed'] ?? 0,
                    $data['successful'] ?? 0,
                    $data['failed'] ?? 0
                ));

        } catch (\Exception $e) {
            return redirect()->route('vector-embeddings.queue')
                ->with('error', __('common.processing_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * صفحة إحصائيات الأداء
     */
    public function performance(): View
    {
        try {
            // أداء البحث
            $searchPerformance = DB::select('
                SELECT * FROM cmis_knowledge.v_search_performance
                ORDER BY "الساعة" DESC
                LIMIT 48
            ');

            // إحصائيات عامة
            $stats = [
                'total_searches' => DB::selectOne('
                    SELECT COUNT(*) as count
                    FROM cmis_knowledge.semantic_search_logs
                    WHERE created_at > NOW() - INTERVAL \'24 hours\'
                ')->count ?? 0,

                'avg_similarity' => DB::selectOne('
                    SELECT AVG(avg_similarity) as avg
                    FROM cmis_knowledge.semantic_search_logs
                    WHERE created_at > NOW() - INTERVAL \'24 hours\'
                ')->avg ?? 0,

                'total_embeddings' => DB::selectOne('
                    SELECT COUNT(*) as count
                    FROM cmis_knowledge.index
                    WHERE topic_embedding IS NOT NULL
                ')->count ?? 0,

                'pending_queue' => DB::selectOne('
                    SELECT COUNT(*) as count
                    FROM cmis_knowledge.embedding_update_queue
                    WHERE status = \'pending\'
                ')->count ?? 0
            ];

            return view('vector-embeddings.performance', compact('searchPerformance', 'stats'));

        } catch (\Exception $e) {
            return view('vector-embeddings.performance')->with('error', $e->getMessage());
        }
    }

    /**
     * API endpoint للبيانات الحية (للـ charts)
     */
    public function liveData(): array
    {
        try {
            return [
                'queue_status' => DB::select('SELECT * FROM cmis_knowledge.v_embedding_queue_status'),
                'embedding_status' => DB::select('SELECT * FROM cmis_knowledge.v_embedding_status LIMIT 10'),
                'recent_searches' => DB::select('
                    SELECT COUNT(*) as count, DATE_TRUNC(\'hour\', created_at) as hour
                    FROM cmis_knowledge.semantic_search_logs
                    WHERE created_at > NOW() - INTERVAL \'24 hours\'
                    GROUP BY hour
                    ORDER BY hour DESC
                ')
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
