<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\CMIS\SemanticSearchService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Concerns\ApiResponse;

class SemanticSearchController extends Controller
{
    use ApiResponse;

    /**
     * Execute semantic search via API.
     */
    public function search(Request $request, SemanticSearchService $service)
    {
        Gate::authorize('useSemanticSearch', auth()->user());

        $query = $request->input('q');

        if (!$query) {
            return $this->validationError(
                ['q' => ['The query parameter is required']],
                'Missing query parameter'
            );
        }

        try {
            $results = $service->search($query);
            Log::info('Semantic search executed', ['query' => $query, 'count' => count($results)]);

            return $this->success([
                'results' => $results,
                'count' => count($results),
                'timestamp' => now()->toDateTimeString()
            ], 'Semantic search completed successfully');

        } catch (\Throwable $e) {
            Log::error('Semantic search failed', ['error' => $e->getMessage()]);
            return $this->serverError('Semantic search failed: ' . $e->getMessage());
        }
    }
}
