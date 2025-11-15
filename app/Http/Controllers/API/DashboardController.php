<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Core\Org;
use App\Services\Dashboard\UnifiedDashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private UnifiedDashboardService $dashboardService
    ) {}

    /**
     * Get unified dashboard for organization
     */
    public function index(Org $org): JsonResponse
    {
        $dashboard = $this->dashboardService->getOrgDashboard($org);

        return response()->json($dashboard);
    }

    /**
     * Refresh dashboard cache
     */
    public function refresh(Org $org): JsonResponse
    {
        $this->dashboardService->clearCache($org);
        $dashboard = $this->dashboardService->getOrgDashboard($org);

        return response()->json([
            'message' => 'Dashboard refreshed',
            'data' => $dashboard,
        ]);
    }
}
