<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class KnowledgeController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display knowledge base dashboard
     */
    public function index(): View
    {
        try {
            $stats = DB::select("
                SELECT
                    COUNT(*) as total_items,
                    COUNT(DISTINCT domain) as domains_count,
                    COUNT(DISTINCT category) as categories_count
                FROM cmis_knowledge.index
                WHERE deleted_at IS NULL
            ")[0] ?? null;

            $recentKnowledge = DB::select("
                SELECT knowledge_id, domain, category, topic, created_at
                FROM cmis_knowledge.index
                WHERE deleted_at IS NULL
                ORDER BY created_at DESC
                LIMIT 10
            ");

            return view('knowledge.index', compact('stats', 'recentKnowledge'));
        } catch (\Exception $e) {
            Log::error('Knowledge index error: ' . $e->getMessage());
            return view('knowledge.index', ['stats' => null, 'recentKnowledge' => []]);
        }
    }

    /**
     * Search knowledge base
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q');
        $domain = $request->input('domain');
        $category = $request->input('category');

        try {
            $results = DB::select("
                SELECT * FROM cmis_knowledge.semantic_search_advanced(?, ?, ?, NULL, NULL, 20)
            ", [$query, $domain, $category]);

            return response()->json([
                'success' => true,
                'results' => $results,
                'count' => count($results)
            ]);
        } catch (\Exception $e) {
            Log::error('Knowledge search error: ' . $e->getMessage());
            return $this->serverError('خطأ في البحث' . ': ' . $e->getMessage());
        }
    }

    /**
     * Register new knowledge
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:50',
            'category' => 'required|string|max:100',
            'topic' => 'required|string|max:500',
            'content' => 'required|string',
            'tier' => 'nullable|integer|min:1|max:5',
            'keywords' => 'nullable|array',
        ]);

        try {
            $result = DB::select("
                SELECT cmis_knowledge.register_knowledge(?, ?, ?, ?, ?, ?) as knowledge_id
            ", [
                $validated['domain'],
                $validated['category'],
                $validated['topic'],
                $validated['content'],
                $validated['tier'] ?? 3,
                json_encode($validated['keywords'] ?? [])
            ]);

            return response()->json([
                'success' => true,
                'knowledge_id' => $result[0]->knowledge_id ?? null,
                'message' => 'تم تسجيل المعرفة بنجاح'
            ]);
        } catch (\Exception $e) {
            Log::error('Knowledge store error: ' . $e->getMessage());
            return $this->serverError('فشل تسجيل المعرفة' . ': ' . $e->getMessage());
        }
    }

    /**
     * Get domains list
     */
    public function domains(): JsonResponse
    {
        try {
            $domains = DB::select("
                SELECT DISTINCT domain, COUNT(*) as count
                FROM cmis_knowledge.index
                WHERE deleted_at IS NULL
                GROUP BY domain
                ORDER BY count DESC
            ");

            return response()->json([
                'success' => true,
                'domains' => $domains
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories by domain
     */
    public function categories($domain): JsonResponse
    {
        try {
            $categories = DB::select("
                SELECT DISTINCT category, COUNT(*) as count
                FROM cmis_knowledge.index
                WHERE domain = ? AND deleted_at IS NULL
                GROUP BY category
                ORDER BY count DESC
            ", [$domain]);

            return response()->json([
                'success' => true,
                'categories' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
