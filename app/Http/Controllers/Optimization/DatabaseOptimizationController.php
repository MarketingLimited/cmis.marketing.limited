<?php

namespace App\Http\Controllers\Optimization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\Optimization\DatabaseQueryOptimizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Database Optimization Controller
 *
 * Manages database query optimization, indexing, and statistics
 */
class DatabaseOptimizationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DatabaseQueryOptimizer $queryOptimizer
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Analyze query performance
     *
     * POST /api/optimization/database/analyze-query
     */
    public function analyzeQuery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string',
            'bindings' => 'nullable|array'
        ]);

        try {
            $result = $this->queryOptimizer->analyzeQuery(
                $validated['query'],
                $validated['bindings'] ?? []
            );

            return $this->success($result, 'Query analysis completed');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Get missing indexes for a table
     *
     * GET /api/optimization/database/missing-indexes/{table}
     */
    public function getMissingIndexes(string $table): JsonResponse
    {
        try {
            $result = $this->queryOptimizer->getMissingIndexes($table);

            return $this->success($result, 'Missing indexes analysis completed');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Get database statistics
     *
     * GET /api/optimization/database/statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $result = $this->queryOptimizer->getDatabaseStatistics();

            return $this->success($result, 'Database statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Optimize a table
     *
     * POST /api/optimization/database/optimize/{table}
     */
    public function optimizeTable(string $table): JsonResponse
    {
        try {
            $result = $this->queryOptimizer->optimizeTable($table);

            return $this->success($result, "Table '{$table}' optimized successfully");

        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
