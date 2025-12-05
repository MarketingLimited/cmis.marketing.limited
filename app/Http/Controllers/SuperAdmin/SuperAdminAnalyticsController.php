<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Platform\PlatformApiCall;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Super Admin Analytics Controller
 *
 * Provides API usage analytics and insights for the platform.
 */
class SuperAdminAnalyticsController extends Controller
{
    use ApiResponse;

    /**
     * Display the analytics dashboard.
     */
    public function index(Request $request)
    {
        $timeRange = $request->get('range', '24h');
        $hours = $this->getHoursFromRange($timeRange);

        $overview = $this->getOverview($hours);
        $byPlatform = $this->getByPlatform($hours);
        $hourlyStats = $this->getHourlyStats($hours);

        if ($request->expectsJson()) {
            return $this->success([
                'overview' => $overview,
                'by_platform' => $byPlatform,
                'hourly_stats' => $hourlyStats,
            ]);
        }

        return view('super-admin.analytics.index', compact('overview', 'byPlatform', 'hourlyStats', 'timeRange'));
    }

    /**
     * Get overview statistics.
     */
    public function overview(Request $request)
    {
        $hours = $this->getHoursFromRange($request->get('range', '24h'));
        return $this->success($this->getOverview($hours));
    }

    /**
     * Get statistics by platform.
     */
    public function byPlatform(Request $request)
    {
        $hours = $this->getHoursFromRange($request->get('range', '24h'));
        return $this->success($this->getByPlatform($hours));
    }

    /**
     * Get statistics by organization.
     */
    public function byOrg(Request $request)
    {
        $hours = $this->getHoursFromRange($request->get('range', '24h'));
        $limit = $request->get('limit', 20);

        $stats = DB::table('cmis.platform_api_calls')
            ->join('cmis.orgs', 'cmis.platform_api_calls.org_id', '=', 'cmis.orgs.org_id')
            ->where('called_at', '>=', now()->subHours($hours))
            ->select(
                'cmis.orgs.org_id',
                'cmis.orgs.name',
                DB::raw('count(*) as total_calls'),
                DB::raw('sum(case when success then 1 else 0 end) as successful'),
                DB::raw('sum(case when not success then 1 else 0 end) as failed'),
                DB::raw('avg(duration_ms) as avg_duration')
            )
            ->groupBy('cmis.orgs.org_id', 'cmis.orgs.name')
            ->orderBy('total_calls', 'desc')
            ->limit($limit)
            ->get();

        return $this->success($stats);
    }

    /**
     * Get statistics by user.
     */
    public function byUser(Request $request)
    {
        $hours = $this->getHoursFromRange($request->get('range', '24h'));
        $limit = $request->get('limit', 20);

        $stats = DB::table('cmis.platform_api_calls')
            ->leftJoin('cmis.users', 'cmis.platform_api_calls.user_id', '=', 'cmis.users.user_id')
            ->where('called_at', '>=', now()->subHours($hours))
            ->whereNotNull('cmis.platform_api_calls.user_id')
            ->select(
                'cmis.users.user_id',
                'cmis.users.name',
                'cmis.users.email',
                DB::raw('count(*) as total_calls'),
                DB::raw('sum(case when success then 1 else 0 end) as successful'),
                DB::raw('sum(case when not success then 1 else 0 end) as failed'),
                DB::raw('avg(duration_ms) as avg_duration')
            )
            ->groupBy('cmis.users.user_id', 'cmis.users.name', 'cmis.users.email')
            ->orderBy('total_calls', 'desc')
            ->limit($limit)
            ->get();

        return $this->success($stats);
    }

    /**
     * Get error statistics.
     */
    public function errors(Request $request)
    {
        $hours = $this->getHoursFromRange($request->get('range', '24h'));

        // Errors by HTTP status
        $byStatus = DB::table('cmis.platform_api_calls')
            ->where('called_at', '>=', now()->subHours($hours))
            ->where('success', false)
            ->select('http_status', DB::raw('count(*) as count'))
            ->groupBy('http_status')
            ->orderBy('count', 'desc')
            ->get();

        // Errors by platform
        $byPlatform = DB::table('cmis.platform_api_calls')
            ->where('called_at', '>=', now()->subHours($hours))
            ->where('success', false)
            ->select('platform', DB::raw('count(*) as count'))
            ->groupBy('platform')
            ->orderBy('count', 'desc')
            ->get();

        // Recent errors with details
        $recentErrors = DB::table('cmis.platform_api_calls')
            ->where('called_at', '>=', now()->subHours($hours))
            ->where('success', false)
            ->select('call_id', 'platform', 'endpoint', 'http_status', 'error_message', 'called_at')
            ->orderBy('called_at', 'desc')
            ->limit(50)
            ->get();

        // Error rate over time
        $errorTrend = DB::table('cmis.platform_api_calls')
            ->where('called_at', '>=', now()->subHours($hours))
            ->selectRaw("
                DATE_TRUNC('hour', called_at) as hour,
                count(*) as total,
                sum(case when not success then 1 else 0 end) as errors,
                round(100.0 * sum(case when not success then 1 else 0 end) / count(*), 2) as error_rate
            ")
            ->groupByRaw("DATE_TRUNC('hour', called_at)")
            ->orderByRaw("DATE_TRUNC('hour', called_at)")
            ->get();

        return $this->success([
            'by_status' => $byStatus,
            'by_platform' => $byPlatform,
            'recent_errors' => $recentErrors,
            'error_trend' => $errorTrend,
        ]);
    }

    /**
     * Get rate limit statistics.
     */
    public function rateLimits(Request $request)
    {
        $hours = $this->getHoursFromRange($request->get('range', '24h'));

        // Organizations approaching rate limits
        $approaching = DB::table('cmis.platform_api_calls')
            ->join('cmis.orgs', 'cmis.platform_api_calls.org_id', '=', 'cmis.orgs.org_id')
            ->where('called_at', '>=', now()->subHours($hours))
            ->whereNotNull('rate_limit_remaining')
            ->where('rate_limit_remaining', '<', 100)
            ->select(
                'cmis.orgs.org_id',
                'cmis.orgs.name',
                'platform',
                DB::raw('min(rate_limit_remaining) as lowest_remaining'),
                DB::raw('max(rate_limit_reset_at) as reset_at')
            )
            ->groupBy('cmis.orgs.org_id', 'cmis.orgs.name', 'platform')
            ->orderBy('lowest_remaining')
            ->limit(20)
            ->get();

        // Rate limit hits (requests that were rejected)
        $rateLimitHits = DB::table('cmis.platform_api_calls')
            ->where('called_at', '>=', now()->subHours($hours))
            ->where('http_status', 429)
            ->select(
                'platform',
                DB::raw('count(*) as hits'),
                DB::raw("DATE_TRUNC('hour', called_at) as hour")
            )
            ->groupBy('platform', DB::raw("DATE_TRUNC('hour', called_at)"))
            ->orderByRaw("DATE_TRUNC('hour', called_at)")
            ->get();

        return $this->success([
            'approaching_limits' => $approaching,
            'rate_limit_hits' => $rateLimitHits,
        ]);
    }

    /**
     * Get endpoint performance statistics.
     */
    public function endpoints(Request $request)
    {
        $hours = $this->getHoursFromRange($request->get('range', '24h'));
        $platform = $request->get('platform');

        $query = DB::table('cmis.platform_api_calls')
            ->where('called_at', '>=', now()->subHours($hours));

        if ($platform) {
            $query->where('platform', $platform);
        }

        $stats = $query->select(
            'platform',
            'endpoint',
            DB::raw('count(*) as total_calls'),
            DB::raw('avg(duration_ms) as avg_duration'),
            DB::raw('percentile_cont(0.95) within group (order by duration_ms) as p95_duration'),
            DB::raw('max(duration_ms) as max_duration'),
            DB::raw('sum(case when not success then 1 else 0 end) as errors')
        )
            ->groupBy('platform', 'endpoint')
            ->orderBy('total_calls', 'desc')
            ->limit(50)
            ->get();

        return $this->success($stats);
    }

    /**
     * Get slow requests.
     */
    public function slowRequests(Request $request)
    {
        $hours = $this->getHoursFromRange($request->get('range', '24h'));
        $threshold = $request->get('threshold', 1000); // ms

        $slowRequests = PlatformApiCall::where('called_at', '>=', now()->subHours($hours))
            ->where('duration_ms', '>', $threshold)
            ->orderBy('duration_ms', 'desc')
            ->limit(100)
            ->get([
                'call_id', 'platform', 'endpoint', 'method',
                'duration_ms', 'http_status', 'success', 'called_at'
            ]);

        return $this->success($slowRequests);
    }

    // ===== Helper Methods =====

    /**
     * Convert time range string to hours.
     */
    protected function getHoursFromRange(string $range): int
    {
        return match ($range) {
            '1h' => 1,
            '6h' => 6,
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            default => 24,
        };
    }

    /**
     * Get overview statistics.
     */
    protected function getOverview(int $hours): array
    {
        $startTime = now()->subHours($hours);

        return [
            'total_requests' => PlatformApiCall::where('called_at', '>=', $startTime)->count(),
            'successful_requests' => PlatformApiCall::where('called_at', '>=', $startTime)->successful()->count(),
            'failed_requests' => PlatformApiCall::where('called_at', '>=', $startTime)->failed()->count(),
            'error_rate' => PlatformApiCall::getErrorRate(null, null, $hours),
            'avg_response_time' => round(PlatformApiCall::where('called_at', '>=', $startTime)->avg('duration_ms') ?? 0, 2),
            'unique_orgs' => PlatformApiCall::where('called_at', '>=', $startTime)->distinct('org_id')->count('org_id'),
            'unique_connections' => PlatformApiCall::where('called_at', '>=', $startTime)->whereNotNull('connection_id')->distinct('connection_id')->count('connection_id'),
        ];
    }

    /**
     * Get statistics by platform.
     */
    protected function getByPlatform(int $hours): array
    {
        return PlatformApiCall::getRequestCountByPlatform(null, now()->subHours($hours), now());
    }

    /**
     * Get hourly statistics.
     */
    protected function getHourlyStats(int $hours): array
    {
        return PlatformApiCall::getHourlyStats(null, null, $hours);
    }
}
