<?php

namespace App\Http\Controllers\Optimization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\Optimization\HealthCheckService;
use Illuminate\Http\JsonResponse;

/**
 * Health Check Controller
 *
 * Public endpoints for Kubernetes liveness and readiness probes
 */
class HealthCheckController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected HealthCheckService $healthCheck
    ) {
        // Health check endpoints are public for Kubernetes probes
        $this->middleware('auth:sanctum')->except([
            'liveness',
            'readiness',
            'health'
        ]);
    }

    /**
     * Liveness probe endpoint
     *
     * GET /api/health/live
     */
    public function liveness(): JsonResponse
    {
        $result = $this->healthCheck->liveness();
        $status = $result['status'] === 'healthy' ? 200 : 503;

        return response()->json($result, $status);
    }

    /**
     * Readiness probe endpoint
     *
     * GET /api/health/ready
     */
    public function readiness(): JsonResponse
    {
        $result = $this->healthCheck->readiness();
        $status = $result['status'] === 'healthy' ? 200 : 503;

        return response()->json($result, $status);
    }

    /**
     * Comprehensive health check
     *
     * GET /api/health
     */
    public function health(): JsonResponse
    {
        $result = $this->healthCheck->health();
        $status = $result['status'] === 'healthy' ? 200 : 503;

        return response()->json($result, $status);
    }

    /**
     * Detailed diagnostics (authenticated)
     *
     * GET /api/health/diagnostics
     */
    public function diagnostics(): JsonResponse
    {
        $result = $this->healthCheck->diagnostics();

        return $this->success($result, 'Diagnostics retrieved successfully');
    }
}
