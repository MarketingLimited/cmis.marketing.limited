<?php

namespace App\Http\Controllers\API\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Analytics Export Controller
 *
 * Handles export and reporting functionality for analytics data
 */
class AnalyticsExportController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Export analytics report
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $orgId = $request->user()->org_id;
            $period = $request->input('period', 30);
            $format = $request->input('format', 'json');

            $startDate = now()->subDays($period);

            $report = [
                'generated_at' => now()->toIso8601String(),
                'period_days' => $period,
                'organization_id' => $orgId,
                'posts' => DB::table('cmis_social.social_posts')
                    ->where('org_id', $orgId)
                    ->where('published_at', '>=', $startDate)
                    ->get(),
                'comments' => DB::table('cmis_social.social_comments')
                    ->where('org_id', $orgId)
                    ->where('created_at', '>=', $startDate)
                    ->get(),
                'messages' => DB::table('cmis_social.social_messages')
                    ->where('org_id', $orgId)
                    ->where('received_at', '>=', $startDate)
                    ->get(),
                'campaigns' => DB::table('cmis_ads.ad_campaigns')
                    ->where('org_id', $orgId)
                    ->where('created_at', '>=', $startDate)
                    ->get(),
            ];

            return $this->success([
                'report' => $report,
            ], 'Report exported successfully');
        } catch (\Exception $e) {
            Log::error("Failed to export report: {$e->getMessage()}");
            return $this->serverError('Failed to export report');
        }
    }
}
