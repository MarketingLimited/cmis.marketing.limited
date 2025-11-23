<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Kpi;
use App\Repositories\Analytics\AnalyticsRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\JsonResponse;

class KpiController extends Controller
{
    use ApiResponse;

    protected AnalyticsRepository $analyticsRepo;

    public function __construct(AnalyticsRepository $analyticsRepo)
    {
        $this->analyticsRepo = $analyticsRepo;
    }

    public function index(Request $request, string $orgId): JsonResponse
    {
        Gate::authorize('viewReports', auth()->user());

        try {
            $kpis = Kpi::where('org_id', $orgId)
                ->orderBy('created_at', 'desc')
                ->paginate(50);

            return $this->success($kpis, 'Retrieved successfully');

        } catch (\Exception $e) {
            Log::error('فشل جلب مؤشرات الأداء: ' . $e->getMessage());
            return $this->serverError('فشل جلب مؤشرات الأداء');
        }
    }

    public function summary(Request $request, string $orgId): JsonResponse
    {
        Gate::authorize('viewPerformance', auth()->user());

        try {
            // Get performance snapshot from analytics repository
            $performanceData = $this->analyticsRepo->snapshotPerformance();

            $summary = [
                'total_campaigns' => \App\Models\Campaign::where('org_id', $orgId)->count(),
                'active_campaigns' => \App\Models\Campaign::where('org_id', $orgId)->where('status', 'active')->count(),
                'total_assets' => \App\Models\CreativeAsset::where('org_id', $orgId)->count(),
                'total_channels' => \App\Models\Channel::where('org_id', $orgId)->count(),
                'performance_metrics' => $performanceData,
            ];

            return $this->success($summary, 'Retrieved successfully');

        } catch (\Exception $e) {
            Log::error('فشل جلب الملخص: ' . $e->getMessage());
            return $this->serverError('فشل جلب الملخص');
        }
    }

    public function trends(Request $request, string $orgId): JsonResponse
    {
        Gate::authorize('viewInsights', auth()->user());

        try {
            $validated = $request->validate([
                'days' => 'nullable|integer|min:1|max:365',
            ]);

            $days = $validated['days'] ?? 30;

            // Get performance trends from analytics repository
            $trendsData = $this->analyticsRepo->snapshotPerformanceForDays($days);

            return response()->json([
                'success' => true,
                'org_id' => $orgId,
                'period_days' => $days,
                'trends' => $trendsData,
            ]);

        } catch (\Exception $e) {
            Log::error('فشل جلب الاتجاهات: ' . $e->getMessage());
            return $this->serverError('فشل جلب الاتجاهات');
        }
    }

    /**
     * Get migration reports
     */
    public function migrations(Request $request): JsonResponse
    {
        Gate::authorize('viewReports', auth()->user());

        try {
            $migrations = $this->analyticsRepo->reportMigrations();

            return response()->json([
                'success' => true,
                'migrations' => $migrations,
            ]);

        } catch (\Exception $e) {
            Log::error('فشل جلب تقارير الهجرة: ' . $e->getMessage());
            return $this->serverError('فشل جلب تقارير الهجرة');
        }
    }

    /**
     * Run AI query on analytics data
     */
    public function aiQuery(Request $request, string $orgId): JsonResponse
    {
        Gate::authorize('viewInsights', auth()->user());

        try {
            $validated = $request->validate([
                'prompt' => 'required|string|max:1000',
            ]);

            $success = $this->analyticsRepo->runAiQuery($orgId, $validated['prompt']);

            return response()->json([
                'success' => $success,
                'message' => $success ? 'تم تنفيذ الاستعلام بنجاح' : 'فشل تنفيذ الاستعلام',
            ]);

        } catch (\Exception $e) {
            Log::error('فشل تنفيذ استعلام الذكاء الاصطناعي: ' . $e->getMessage());
            return $this->serverError('فشل تنفيذ استعلام الذكاء الاصطناعي');
        }
    }
}
