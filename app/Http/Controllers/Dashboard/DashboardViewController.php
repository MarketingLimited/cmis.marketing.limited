<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Campaign;
use App\Models\CreativeAsset;
use App\Models\Core\Org;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard View Controller
 *
 * Handles main dashboard view and overview data
 */
class DashboardViewController extends Controller
{
    use ApiResponse;

    /**
     * Display main dashboard view
     */
    public function index(): View
    {
        $data = $this->resolveDashboardMetrics();
        return view('dashboard', $data);
    }

    /**
     * Get dashboard data (JSON)
     */
    public function data(): JsonResponse
    {
        return $this->success($this->resolveDashboardMetrics(), 'Dashboard metrics retrieved successfully');
    }

    /**
     * Get dashboard overview data
     */
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();
        $orgId = $this->resolveOrgId($request);

        if (!$orgId) {
            return $this->notFound('No active organization found');
        }

        $data = [
            'campaigns' => $this->getCampaignsData($orgId),
            'analytics' => $this->getAnalyticsData($orgId),
            'recent_activity' => $this->getRecentActivity($orgId),
        ];

        return $this->success($data, 'Overview retrieved successfully');
    }

    /**
     * Resolve dashboard metrics (cached)
     */
    protected function resolveDashboardMetrics(): array
    {
        return Cache::remember('dashboard.metrics', now()->addMinutes(5), function (): array {
            $stats = [
                'orgs' => $this->safeCount(fn() => Org::count()),
                'campaigns' => $this->safeCount(fn() => Campaign::count()),
                'offerings' => 0,
                'kpis' => $this->safeCount(fn() => DB::table('cmis.kpis')->count()),
                'creative_assets' => $this->safeCount(fn() => CreativeAsset::count()),
            ];

            $campaignStatus = $this->safeTry(function() {
                return Campaign::query()
                    ->select('status', DB::raw('COUNT(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status');
            }, collect());

            $campaignsByOrg = $this->safeTry(function() {
                return Campaign::query()
                    ->join('cmis.orgs as o', 'cmis.campaigns.org_id', '=', 'o.org_id')
                    ->select('o.name as org_name', DB::raw('COUNT(cmis.campaigns.campaign_id) as total'))
                    ->groupBy('o.name')
                    ->orderBy('o.name')
                    ->get();
            }, collect());

            $offerings = [
                'products' => 0,
                'services' => 0,
                'bundles' => 0,
            ];

            $analytics = [
                'kpis' => $this->safeCount(fn() => DB::table('cmis.kpis')->count()),
                'metrics' => 0,
            ];

            $creative = [
                'assets' => $this->safeCount(fn() => CreativeAsset::count()),
                'images' => 0,
                'videos' => 0,
            ];

            $ai = [
                'ai_campaigns' => 0,
                'recommendations' => 0,
                'models' => 0,
            ];

            return compact('stats', 'campaignStatus', 'campaignsByOrg', 'offerings', 'analytics', 'creative', 'ai');
        });
    }

    /**
     * Resolve organization ID from request
     */
    private function resolveOrgId(Request $request): ?string
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        if ($request->route('org_id')) {
            return $request->route('org_id');
        }

        if ($user->active_org_id ?? null) {
            return $user->active_org_id;
        }

        $activeOrg = DB::table('cmis.user_orgs')
            ->where('user_id', $user->user_id)
            ->where('is_active', true)
            ->first();

        return $activeOrg?->org_id;
    }

    /**
     * Get campaigns data (automatically filtered by RLS)
     */
    private function getCampaignsData(string $orgId): array
    {
        return [
            'total' => Campaign::count(),
            'active' => Campaign::where('status', 'active')->count(),
            'recent' => Campaign::orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['campaign_id', 'name', 'status', 'created_at']),
        ];
    }

    /**
     * Get analytics data for organization
     */
    private function getAnalyticsData(string $orgId): array
    {
        return [
            'total_impressions' => 0,
            'total_clicks' => 0,
            'avg_ctr' => 0,
        ];
    }

    /**
     * Get recent activity for organization
     */
    private function getRecentActivity(string $orgId): array
    {
        return [];
    }

    /**
     * Safely execute a count query with error handling
     */
    private function safeCount(callable $callback): int
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Safely execute a query with error handling
     */
    private function safeTry(callable $callback, $default)
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            return $default;
        }
    }
}
