<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Analytics Export Controller
 *
 * Handles PDF and Excel/CSV export of analytics data.
 * Fixes TODO at analytics/index.blade.php lines 423 and 443.
 */
class AnalyticsExportController extends Controller
{
    use ApiResponse;

    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->middleware('auth:sanctum');
        $this->reportService = $reportService;
    }

    /**
     * Export analytics data to PDF
     *
     * @param Request $request
     * @return Response
     */
    public function exportPDF(Request $request): Response
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return response('No active organization found', 404);
            }

            $validated = $request->validate([
                'dateRange' => 'nullable|string',
                'platform' => 'nullable|string',
                'period' => 'nullable|integer|min:1|max:365',
            ]);

            $period = $validated['period'] ?? 30;
            $platform = $validated['platform'] ?? null;
            $startDate = now()->subDays($period);

            // Gather analytics data
            $data = $this->gatherAnalyticsData($orgId, $startDate, $platform);

            // Generate PDF
            $pdf = Pdf::loadView('reports.analytics-pdf', [
                'data' => $data,
                'period' => $period,
                'platform' => $platform,
                'generatedAt' => now(),
                'locale' => app()->getLocale(),
            ]);

            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

            $filename = 'analytics_report_' . now()->format('Y-m-d_His') . '.pdf';

            Log::info('Analytics PDF exported', [
                'org_id' => $orgId,
                'period' => $period,
                'platform' => $platform,
                'filename' => $filename
            ]);

            return response($pdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            Log::error('Failed to export analytics PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('Failed to generate PDF: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Export analytics data to Excel (CSV format)
     *
     * Since PhpSpreadsheet is not installed, we use CSV format which Excel can open.
     *
     * @param Request $request
     * @return Response
     */
    public function exportExcel(Request $request): Response
    {
        try {
            $orgId = $this->resolveOrgId($request);

            if (!$orgId) {
                return response('No active organization found', 404);
            }

            $validated = $request->validate([
                'dateRange' => 'nullable|string',
                'platform' => 'nullable|string',
                'period' => 'nullable|integer|min:1|max:365',
                'type' => 'nullable|string|in:overview,campaigns,posts,engagement',
            ]);

            $period = $validated['period'] ?? 30;
            $platform = $validated['platform'] ?? null;
            $type = $validated['type'] ?? 'overview';
            $startDate = now()->subDays($period);

            // Generate CSV content based on type
            $csvContent = $this->generateCSV($orgId, $startDate, $platform, $type);

            $filename = 'analytics_' . $type . '_' . now()->format('Y-m-d_His') . '.csv';

            Log::info('Analytics Excel/CSV exported', [
                'org_id' => $orgId,
                'period' => $period,
                'platform' => $platform,
                'type' => $type,
                'filename' => $filename
            ]);

            return response($csvContent)
                ->header('Content-Type', 'text/csv; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Transfer-Encoding', 'binary');

        } catch (\Exception $e) {
            Log::error('Failed to export analytics Excel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('Failed to generate Excel: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Gather all analytics data for export
     */
    protected function gatherAnalyticsData(string $orgId, $startDate, ?string $platform = null): array
    {
        // Overview stats
        $overview = [
            'total_posts' => DB::table('cmis_social.social_posts')
                ->where('org_id', $orgId)
                ->where('published_at', '>=', $startDate)
                ->when($platform, fn($q) => $q->where('platform', $platform))
                ->count(),

            'total_comments' => DB::table('cmis_social.social_comments')
                ->where('org_id', $orgId)
                ->where('created_at', '>=', $startDate)
                ->when($platform, fn($q) => $q->where('platform', $platform))
                ->count(),

            'total_messages' => DB::table('cmis_social.social_messages')
                ->where('org_id', $orgId)
                ->where('received_at', '>=', $startDate)
                ->when($platform, fn($q) => $q->where('platform', $platform))
                ->count(),

            'active_campaigns' => DB::table('cmis_ads.ad_campaigns')
                ->where('org_id', $orgId)
                ->whereIn('status', ['ACTIVE', 'ENABLED', 'active'])
                ->when($platform, fn($q) => $q->where('platform', $platform))
                ->count(),
        ];

        // Posts by platform
        $postsByPlatform = DB::table('cmis_social.social_posts')
            ->where('org_id', $orgId)
            ->where('published_at', '>=', $startDate)
            ->when($platform, fn($q) => $q->where('platform', $platform))
            ->select('platform', DB::raw('count(*) as count'))
            ->groupBy('platform')
            ->get()
            ->toArray();

        // Campaign performance
        $campaigns = DB::table('cmis_ads.ad_campaigns as c')
            ->leftJoin('cmis_ads.ad_metrics as m', 'c.campaign_id', '=', 'm.campaign_id')
            ->where('c.org_id', $orgId)
            ->when($platform, fn($q) => $q->where('c.platform', $platform))
            ->select(
                'c.campaign_id',
                'c.campaign_name',
                'c.platform',
                'c.status',
                DB::raw('SUM(COALESCE(m.impressions, 0)) as impressions'),
                DB::raw('SUM(COALESCE(m.clicks, 0)) as clicks'),
                DB::raw('SUM(COALESCE(m.spend, 0)) as spend'),
                DB::raw('SUM(COALESCE(m.conversions, 0)) as conversions')
            )
            ->groupBy('c.campaign_id', 'c.campaign_name', 'c.platform', 'c.status')
            ->get()
            ->toArray();

        // Daily trends
        $dailyTrends = DB::table('cmis_social.social_posts')
            ->where('org_id', $orgId)
            ->where('published_at', '>=', $startDate)
            ->when($platform, fn($q) => $q->where('platform', $platform))
            ->select(
                DB::raw('DATE(published_at) as date'),
                DB::raw('count(*) as posts'),
                DB::raw("SUM(COALESCE((metadata->>'likes')::int, 0)) as likes"),
                DB::raw("SUM(COALESCE((metadata->>'comments')::int, 0)) as comments"),
                DB::raw("SUM(COALESCE((metadata->>'shares')::int, 0)) as shares")
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        // Top performing posts
        $topPosts = DB::table('cmis_social.social_posts')
            ->where('org_id', $orgId)
            ->where('published_at', '>=', $startDate)
            ->when($platform, fn($q) => $q->where('platform', $platform))
            ->select(
                'post_id',
                'platform',
                'content',
                'published_at',
                DB::raw("COALESCE((metadata->>'likes')::int, 0) as likes"),
                DB::raw("COALESCE((metadata->>'comments')::int, 0) as comments"),
                DB::raw("COALESCE((metadata->>'shares')::int, 0) as shares")
            )
            ->orderByRaw("(COALESCE((metadata->>'likes')::int, 0) + COALESCE((metadata->>'comments')::int, 0) + COALESCE((metadata->>'shares')::int, 0)) DESC")
            ->limit(10)
            ->get()
            ->toArray();

        return [
            'overview' => $overview,
            'posts_by_platform' => $postsByPlatform,
            'campaigns' => $campaigns,
            'daily_trends' => $dailyTrends,
            'top_posts' => $topPosts,
        ];
    }

    /**
     * Generate CSV content for different report types
     */
    protected function generateCSV(string $orgId, $startDate, ?string $platform, string $type): string
    {
        $output = fopen('php://temp', 'r+');

        // Add BOM for Excel UTF-8 compatibility
        fputs($output, "\xEF\xBB\xBF");

        switch ($type) {
            case 'campaigns':
                $this->generateCampaignsCSV($output, $orgId, $startDate, $platform);
                break;

            case 'posts':
                $this->generatePostsCSV($output, $orgId, $startDate, $platform);
                break;

            case 'engagement':
                $this->generateEngagementCSV($output, $orgId, $startDate, $platform);
                break;

            case 'overview':
            default:
                $this->generateOverviewCSV($output, $orgId, $startDate, $platform);
                break;
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Generate overview CSV
     */
    protected function generateOverviewCSV($output, string $orgId, $startDate, ?string $platform): void
    {
        // Header
        fputcsv($output, ['Metric', 'Value']);

        // Overview metrics
        $overview = [
            'Total Posts' => DB::table('cmis_social.social_posts')
                ->where('org_id', $orgId)
                ->where('published_at', '>=', $startDate)
                ->when($platform, fn($q) => $q->where('platform', $platform))
                ->count(),
            'Total Comments' => DB::table('cmis_social.social_comments')
                ->where('org_id', $orgId)
                ->where('created_at', '>=', $startDate)
                ->when($platform, fn($q) => $q->where('platform', $platform))
                ->count(),
            'Total Messages' => DB::table('cmis_social.social_messages')
                ->where('org_id', $orgId)
                ->where('received_at', '>=', $startDate)
                ->when($platform, fn($q) => $q->where('platform', $platform))
                ->count(),
            'Active Campaigns' => DB::table('cmis_ads.ad_campaigns')
                ->where('org_id', $orgId)
                ->whereIn('status', ['ACTIVE', 'ENABLED', 'active'])
                ->when($platform, fn($q) => $q->where('platform', $platform))
                ->count(),
        ];

        foreach ($overview as $metric => $value) {
            fputcsv($output, [$metric, $value]);
        }

        // Blank row
        fputcsv($output, []);
        fputcsv($output, ['Posts by Platform']);
        fputcsv($output, ['Platform', 'Count']);

        $postsByPlatform = DB::table('cmis_social.social_posts')
            ->where('org_id', $orgId)
            ->where('published_at', '>=', $startDate)
            ->when($platform, fn($q) => $q->where('platform', $platform))
            ->select('platform', DB::raw('count(*) as count'))
            ->groupBy('platform')
            ->get();

        foreach ($postsByPlatform as $row) {
            fputcsv($output, [$row->platform, $row->count]);
        }
    }

    /**
     * Generate campaigns CSV
     */
    protected function generateCampaignsCSV($output, string $orgId, $startDate, ?string $platform): void
    {
        fputcsv($output, ['Campaign ID', 'Campaign Name', 'Platform', 'Status', 'Impressions', 'Clicks', 'CTR (%)', 'Spend', 'Conversions']);

        $campaigns = DB::table('cmis_ads.ad_campaigns as c')
            ->leftJoin('cmis_ads.ad_metrics as m', 'c.campaign_id', '=', 'm.campaign_id')
            ->where('c.org_id', $orgId)
            ->when($platform, fn($q) => $q->where('c.platform', $platform))
            ->select(
                'c.campaign_id',
                'c.campaign_name',
                'c.platform',
                'c.status',
                DB::raw('SUM(COALESCE(m.impressions, 0)) as impressions'),
                DB::raw('SUM(COALESCE(m.clicks, 0)) as clicks'),
                DB::raw('SUM(COALESCE(m.spend, 0)) as spend'),
                DB::raw('SUM(COALESCE(m.conversions, 0)) as conversions')
            )
            ->groupBy('c.campaign_id', 'c.campaign_name', 'c.platform', 'c.status')
            ->get();

        foreach ($campaigns as $campaign) {
            $ctr = $campaign->impressions > 0
                ? round(($campaign->clicks / $campaign->impressions) * 100, 2)
                : 0;

            fputcsv($output, [
                $campaign->campaign_id,
                $campaign->campaign_name,
                $campaign->platform,
                $campaign->status,
                $campaign->impressions,
                $campaign->clicks,
                $ctr,
                number_format($campaign->spend, 2),
                $campaign->conversions
            ]);
        }
    }

    /**
     * Generate posts CSV
     */
    protected function generatePostsCSV($output, string $orgId, $startDate, ?string $platform): void
    {
        fputcsv($output, ['Post ID', 'Platform', 'Content', 'Published At', 'Likes', 'Comments', 'Shares', 'Total Engagement']);

        $posts = DB::table('cmis_social.social_posts')
            ->where('org_id', $orgId)
            ->where('published_at', '>=', $startDate)
            ->when($platform, fn($q) => $q->where('platform', $platform))
            ->select(
                'post_id',
                'platform',
                'content',
                'published_at',
                DB::raw("COALESCE((metadata->>'likes')::int, 0) as likes"),
                DB::raw("COALESCE((metadata->>'comments')::int, 0) as comments"),
                DB::raw("COALESCE((metadata->>'shares')::int, 0) as shares")
            )
            ->orderBy('published_at', 'desc')
            ->get();

        foreach ($posts as $post) {
            $totalEngagement = $post->likes + $post->comments + $post->shares;
            fputcsv($output, [
                $post->post_id,
                $post->platform,
                substr($post->content ?? '', 0, 100) . (strlen($post->content ?? '') > 100 ? '...' : ''),
                $post->published_at,
                $post->likes,
                $post->comments,
                $post->shares,
                $totalEngagement
            ]);
        }
    }

    /**
     * Generate engagement CSV
     */
    protected function generateEngagementCSV($output, string $orgId, $startDate, ?string $platform): void
    {
        fputcsv($output, ['Date', 'Posts', 'Likes', 'Comments', 'Shares', 'Total Engagement']);

        $dailyData = DB::table('cmis_social.social_posts')
            ->where('org_id', $orgId)
            ->where('published_at', '>=', $startDate)
            ->when($platform, fn($q) => $q->where('platform', $platform))
            ->select(
                DB::raw('DATE(published_at) as date'),
                DB::raw('count(*) as posts'),
                DB::raw("SUM(COALESCE((metadata->>'likes')::int, 0)) as likes"),
                DB::raw("SUM(COALESCE((metadata->>'comments')::int, 0)) as comments"),
                DB::raw("SUM(COALESCE((metadata->>'shares')::int, 0)) as shares")
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        foreach ($dailyData as $row) {
            $totalEngagement = $row->likes + $row->comments + $row->shares;
            fputcsv($output, [
                $row->date,
                $row->posts,
                $row->likes,
                $row->comments,
                $row->shares,
                $totalEngagement
            ]);
        }
    }

    /**
     * Resolve organization ID from request
     */
    protected function resolveOrgId(Request $request): ?string
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        if ($request->route('org_id')) {
            return $request->route('org_id');
        }

        if (isset($user->org_id)) {
            return $user->org_id;
        }

        if (isset($user->active_org_id)) {
            return $user->active_org_id;
        }

        $activeOrg = DB::table('cmis.user_orgs')
            ->where('user_id', $user->user_id)
            ->where('is_active', true)
            ->first();

        return $activeOrg?->org_id;
    }
}
