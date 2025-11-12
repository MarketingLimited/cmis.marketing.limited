<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\CMIS\SemanticSearchService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class SemanticSearchController extends Controller
{
    /**
     * تنفيذ البحث الدلالي عبر API.
     */
    public function search(Request $request, SemanticSearchService $service)
    {
        Gate::authorize('useSemanticSearch', auth()->user());

        $query = $request->input('q');

        if (!$query) {
            return response()->json([
                'error' => 'Missing query parameter (q)'
            ], 400);
        }

        try {
            $results = $service->search($query);
            Log::info('Semantic search executed', ['query' => $query, 'count' => count($results)]);

            return response()->json([
                'data' => $results,
                'count' => count($results),
                'timestamp' => now()->toDateTimeString()
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Semantic search failed', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
