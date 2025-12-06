<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\LogsSuperAdminActions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Super Admin Integration Controller
 *
 * Monitors and manages platform integrations (Meta, Google, TikTok, etc.)
 */
class SuperAdminIntegrationController extends Controller
{
    use ApiResponse, LogsSuperAdminActions;

    /**
     * List of supported platforms with their configurations.
     */
    protected array $platforms = [
        'meta' => [
            'name' => 'Meta (Facebook/Instagram)',
            'icon' => 'fab fa-facebook',
            'color' => 'blue',
            'endpoints' => ['ads', 'pages', 'instagram'],
        ],
        'google' => [
            'name' => 'Google Ads',
            'icon' => 'fab fa-google',
            'color' => 'red',
            'endpoints' => ['ads', 'analytics', 'youtube'],
        ],
        'tiktok' => [
            'name' => 'TikTok',
            'icon' => 'fab fa-tiktok',
            'color' => 'gray',
            'endpoints' => ['ads', 'analytics'],
        ],
        'linkedin' => [
            'name' => 'LinkedIn',
            'icon' => 'fab fa-linkedin',
            'color' => 'blue',
            'endpoints' => ['ads', 'company'],
        ],
        'twitter' => [
            'name' => 'X (Twitter)',
            'icon' => 'fab fa-x-twitter',
            'color' => 'gray',
            'endpoints' => ['ads', 'timeline'],
        ],
        'snapchat' => [
            'name' => 'Snapchat',
            'icon' => 'fab fa-snapchat',
            'color' => 'yellow',
            'endpoints' => ['ads'],
        ],
    ];

    /**
     * Display a listing of all platform integrations.
     */
    public function index(Request $request)
    {
        // Get all platform connections grouped by platform
        $connections = DB::table('cmis.platform_connections as pc')
            ->join('cmis.orgs as o', 'pc.org_id', '=', 'o.org_id')
            ->select([
                'pc.*',
                'o.name as org_name',
                'o.status as org_status',
            ])
            ->whereNull('pc.deleted_at')
            ->orderBy('pc.platform')
            ->orderByDesc('pc.created_at')
            ->get();

        // Group by platform
        $connectionsByPlatform = $connections->groupBy('platform');

        // Get stats for each platform
        $platformStats = [];
        foreach ($this->platforms as $key => $platform) {
            $platformConnections = $connectionsByPlatform->get($key, collect());
            $activeConnections = $platformConnections->filter(fn($c) => $c->status === 'active');
            $errorConnections = $platformConnections->filter(fn($c) => $c->status === 'error');

            $platformStats[$key] = [
                'name' => $platform['name'],
                'icon' => $platform['icon'],
                'color' => $platform['color'],
                'total' => $platformConnections->count(),
                'active' => $activeConnections->count(),
                'error' => $errorConnections->count(),
                'pending' => $platformConnections->filter(fn($c) => $c->status === 'pending')->count(),
            ];
        }

        // Get overall stats
        $stats = [
            'total_connections' => $connections->count(),
            'active_connections' => $connections->filter(fn($c) => $c->status === 'active')->count(),
            'error_connections' => $connections->filter(fn($c) => $c->status === 'error')->count(),
            'orgs_with_integrations' => $connections->unique('org_id')->count(),
        ];

        // Get recent connection activity
        $recentActivity = DB::table('cmis.platform_connections as pc')
            ->join('cmis.orgs as o', 'pc.org_id', '=', 'o.org_id')
            ->select([
                'pc.connection_id',
                'pc.platform',
                'pc.account_name',
                'pc.status',
                'pc.created_at',
                'pc.updated_at',
                'o.name as org_name',
            ])
            ->whereNull('pc.deleted_at')
            ->orderByDesc('pc.updated_at')
            ->limit(10)
            ->get();

        if ($request->expectsJson()) {
            return $this->success([
                'platform_stats' => $platformStats,
                'stats' => $stats,
                'connections' => $connections,
                'recent_activity' => $recentActivity,
            ]);
        }

        return view('super-admin.integrations.index', compact(
            'platformStats',
            'stats',
            'connections',
            'connectionsByPlatform',
            'recentActivity'
        ));
    }

    /**
     * Display platform health dashboard.
     */
    public function healthDashboard(Request $request)
    {
        $healthData = [];

        foreach ($this->platforms as $key => $platform) {
            // Get connection stats
            $connections = DB::table('cmis.platform_connections')
                ->where('platform', $key)
                ->whereNull('deleted_at')
                ->get();

            $activeCount = $connections->where('status', 'active')->count();
            $totalCount = $connections->count();

            // Get recent sync status by joining with platform_connections
            $lastSync = DB::table('cmis.platform_sync_logs as sl')
                ->join('cmis.platform_connections as pc', 'sl.connection_id', '=', 'pc.connection_id')
                ->where('pc.platform', $key)
                ->orderByDesc('sl.created_at')
                ->select([
                    'sl.*',
                    'pc.platform',
                ])
                ->first();

            // Get rate limit info from cache
            $rateLimitKey = "platform_rate_limit_{$key}";
            $rateLimitInfo = Cache::get($rateLimitKey, [
                'remaining' => null,
                'limit' => null,
                'reset_at' => null,
            ]);

            // Calculate health score (0-100)
            $healthScore = $this->calculateHealthScore($key, $connections, $lastSync, $rateLimitInfo);

            $healthData[$key] = [
                'platform' => $platform,
                'connections' => [
                    'total' => $totalCount,
                    'active' => $activeCount,
                    'error' => $connections->where('status', 'error')->count(),
                ],
                'last_sync' => $lastSync ? [
                    'status' => $lastSync->status ?? 'unknown',
                    'at' => $lastSync->created_at,
                    'duration' => $lastSync->duration_ms ?? null,
                    'records_synced' => $lastSync->entities_processed ?? 0,
                ] : null,
                'rate_limit' => $rateLimitInfo,
                'health_score' => $healthScore,
                'status' => $healthScore >= 80 ? 'healthy' : ($healthScore >= 50 ? 'degraded' : 'critical'),
            ];
        }

        // Get recent errors across all platforms
        $recentErrors = DB::table('cmis.platform_sync_logs as sl')
            ->leftJoin('cmis.platform_connections as pc', 'sl.connection_id', '=', 'pc.connection_id')
            ->where('sl.status', 'error')
            ->where('sl.created_at', '>', now()->subHours(24))
            ->select([
                'sl.*',
                'pc.platform',
                'pc.account_name',
            ])
            ->orderByDesc('sl.created_at')
            ->limit(20)
            ->get();

        // Get sync performance metrics by platform
        $syncMetrics = DB::table('cmis.platform_sync_logs as sl')
            ->join('cmis.platform_connections as pc', 'sl.connection_id', '=', 'pc.connection_id')
            ->select([
                'pc.platform',
                DB::raw('COUNT(*) as total_syncs'),
                DB::raw('AVG(sl.duration_ms) as avg_duration'),
                DB::raw('SUM(sl.entities_processed) as total_records'),
                DB::raw("COUNT(CASE WHEN sl.status = 'completed' THEN 1 END) as successful_syncs"),
                DB::raw("COUNT(CASE WHEN sl.status = 'error' THEN 1 END) as failed_syncs"),
            ])
            ->where('sl.created_at', '>', now()->subHours(24))
            ->groupBy('pc.platform')
            ->get()
            ->keyBy('platform');

        if ($request->expectsJson()) {
            return $this->success([
                'health_data' => $healthData,
                'recent_errors' => $recentErrors,
                'sync_metrics' => $syncMetrics,
            ]);
        }

        return view('super-admin.integrations.health', compact(
            'healthData',
            'recentErrors',
            'syncMetrics'
        ));
    }

    /**
     * Show details for a specific integration connection.
     */
    public function show(Request $request, string $connectionId)
    {
        // Get connection with organization info
        // Note: token_expires_at is already in platform_connections table
        $connection = DB::table('cmis.platform_connections as pc')
            ->join('cmis.orgs as o', 'pc.org_id', '=', 'o.org_id')
            ->select([
                'pc.*',
                'o.name as org_name',
                'o.status as org_status',
                'o.org_id',
            ])
            ->where('pc.connection_id', $connectionId)
            ->whereNull('pc.deleted_at')
            ->first();

        if (!$connection) {
            if ($request->expectsJson()) {
                return $this->notFound(__('super_admin.integrations.connection_not_found'));
            }
            abort(404);
        }

        // Get sync history
        $syncHistory = DB::table('cmis.platform_sync_logs')
            ->where('connection_id', $connectionId)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // Get webhook activity
        $webhookActivity = DB::table('cmis.platform_webhooks')
            ->where('connection_id', $connectionId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        // Get related ad accounts if applicable
        $adAccounts = DB::table('cmis.ad_accounts')
            ->where('platform_connection_id', $connectionId)
            ->whereNull('deleted_at')
            ->get();

        // Get platform-specific metrics
        $metrics = $this->getConnectionMetrics($connection);

        if ($request->expectsJson()) {
            return $this->success([
                'connection' => $connection,
                'sync_history' => $syncHistory,
                'webhook_activity' => $webhookActivity,
                'ad_accounts' => $adAccounts,
                'metrics' => $metrics,
            ]);
        }

        return view('super-admin.integrations.show', compact(
            'connection',
            'syncHistory',
            'webhookActivity',
            'adAccounts',
            'metrics'
        ));
    }

    /**
     * Force refresh data for a specific connection.
     */
    public function forceRefresh(Request $request, string $connectionId)
    {
        $connection = DB::table('cmis.platform_connections')
            ->where('connection_id', $connectionId)
            ->whereNull('deleted_at')
            ->first();

        if (!$connection) {
            return $this->notFound(__('super_admin.integrations.connection_not_found'));
        }

        try {
            // Create a sync job entry
            // Note: platform_sync_logs has: sync_id, org_id, connection_id, sync_type, entity_type, direction, status, etc.
            $syncId = DB::table('cmis.platform_sync_logs')->insertGetId([
                'sync_id' => \Illuminate\Support\Str::uuid(),
                'org_id' => $connection->org_id,
                'connection_id' => $connectionId,
                'sync_type' => 'full',
                'entity_type' => 'all',
                'direction' => 'inbound',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ], 'sync_id');

            // Dispatch sync job (would normally queue this)
            // For now, just update status
            DB::table('cmis.platform_connections')
                ->where('connection_id', $connectionId)
                ->update([
                    'last_sync_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->logAction('integration_force_refresh', 'integration', $connectionId, $connection->account_name, [
                'platform' => $connection->platform,
            ]);

            return $this->success([
                'connection_id' => $connectionId,
                'sync_initiated' => true,
            ], __('super_admin.integrations.refresh_initiated'));
        } catch (\Exception $e) {
            Log::error('Failed to force refresh integration', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('super_admin.integrations.refresh_failed'));
        }
    }

    /**
     * Disconnect an integration.
     */
    public function disconnect(Request $request, string $connectionId)
    {
        $connection = DB::table('cmis.platform_connections')
            ->where('connection_id', $connectionId)
            ->whereNull('deleted_at')
            ->first();

        if (!$connection) {
            return $this->notFound(__('super_admin.integrations.connection_not_found'));
        }

        try {
            DB::beginTransaction();

            // Soft delete the connection
            DB::table('cmis.platform_connections')
                ->where('connection_id', $connectionId)
                ->update([
                    'status' => 'revoked',
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);

            // Clear tokens for security
            DB::table('cmis.platform_connections')
                ->where('connection_id', $connectionId)
                ->update([
                    'access_token' => null,
                    'refresh_token' => null,
                ]);

            DB::commit();

            $this->logAction('integration_disconnected', 'integration', $connectionId, $connection->account_name, [
                'platform' => $connection->platform,
                'org_id' => $connection->org_id,
            ]);

            return $this->success([
                'connection_id' => $connectionId,
                'disconnected' => true,
            ], __('super_admin.integrations.disconnected_success'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to disconnect integration', [
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError(__('super_admin.integrations.disconnect_failed'));
        }
    }

    /**
     * Get sync status across all platforms.
     */
    public function syncStatus(Request $request)
    {
        $timeframe = $request->get('timeframe', '24h');
        $since = match ($timeframe) {
            '1h' => now()->subHour(),
            '6h' => now()->subHours(6),
            '24h' => now()->subHours(24),
            '7d' => now()->subDays(7),
            default => now()->subHours(24),
        };

        // Get sync statistics by platform
        $syncStats = DB::table('cmis.platform_sync_logs as sl')
            ->join('cmis.platform_connections as pc', 'sl.connection_id', '=', 'pc.connection_id')
            ->select([
                'pc.platform',
                DB::raw('COUNT(*) as total'),
                DB::raw("COUNT(CASE WHEN sl.status = 'completed' THEN 1 END) as success"),
                DB::raw("COUNT(CASE WHEN sl.status = 'error' THEN 1 END) as error"),
                DB::raw("COUNT(CASE WHEN sl.status = 'pending' THEN 1 END) as pending"),
                DB::raw('AVG(sl.duration_ms) as avg_duration_ms'),
                DB::raw('SUM(sl.entities_processed) as total_records'),
            ])
            ->where('sl.created_at', '>=', $since)
            ->groupBy('pc.platform')
            ->get()
            ->keyBy('platform');

        // Get currently running syncs
        $runningSyncs = DB::table('cmis.platform_sync_logs as sl')
            ->leftJoin('cmis.platform_connections as pc', 'sl.connection_id', '=', 'pc.connection_id')
            ->where('sl.status', 'in_progress')
            ->select(['sl.*', 'pc.platform', 'pc.account_name'])
            ->get();

        // Get pending syncs
        $pendingSyncs = DB::table('cmis.platform_sync_logs as sl')
            ->leftJoin('cmis.platform_connections as pc', 'sl.connection_id', '=', 'pc.connection_id')
            ->where('sl.status', 'pending')
            ->select(['sl.*', 'pc.platform', 'pc.account_name'])
            ->orderBy('sl.created_at')
            ->limit(20)
            ->get();

        // Get recent failures with details
        $recentFailures = DB::table('cmis.platform_sync_logs as sl')
            ->leftJoin('cmis.platform_connections as pc', 'sl.connection_id', '=', 'pc.connection_id')
            ->leftJoin('cmis.orgs as o', 'pc.org_id', '=', 'o.org_id')
            ->select([
                'sl.*',
                'pc.account_name',
                'o.name as org_name',
            ])
            ->where('sl.status', 'error')
            ->where('sl.created_at', '>=', $since)
            ->orderByDesc('sl.created_at')
            ->limit(20)
            ->get();

        return $this->success([
            'timeframe' => $timeframe,
            'since' => $since->toIso8601String(),
            'sync_stats' => $syncStats,
            'running_syncs' => $runningSyncs,
            'pending_syncs' => $pendingSyncs,
            'recent_failures' => $recentFailures,
        ]);
    }

    /**
     * Get rate limit information for all platforms.
     */
    public function rateLimits(Request $request)
    {
        $rateLimits = [];

        foreach ($this->platforms as $key => $platform) {
            $rateLimitKey = "platform_rate_limit_{$key}";
            $cached = Cache::get($rateLimitKey);

            // Get rate limit hits from logs
            $recentHits = DB::table('cmis.platform_rate_limits')
                ->where('platform', $key)
                ->where('created_at', '>=', now()->subHour())
                ->count();

            $rateLimits[$key] = [
                'platform' => $platform['name'],
                'current' => $cached ? [
                    'remaining' => $cached['remaining'] ?? null,
                    'limit' => $cached['limit'] ?? null,
                    'reset_at' => $cached['reset_at'] ?? null,
                ] : null,
                'recent_hits' => $recentHits,
                'status' => $this->getRateLimitStatus($cached, $recentHits),
            ];
        }

        return $this->success([
            'rate_limits' => $rateLimits,
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Calculate health score for a platform (0-100).
     */
    protected function calculateHealthScore(string $platform, $connections, $lastSync, array $rateLimitInfo): int
    {
        $score = 100;

        // Deduct for inactive connections
        $activePercent = $connections->count() > 0
            ? ($connections->where('status', 'active')->count() / $connections->count()) * 100
            : 0;
        $score -= max(0, (100 - $activePercent) * 0.3);

        // Deduct for error connections
        $errorCount = $connections->where('status', 'error')->count();
        $score -= min(30, $errorCount * 5);

        // Deduct for stale sync
        if ($lastSync) {
            $lastSyncTime = Carbon::parse($lastSync->created_at);
            $hoursSinceSync = $lastSyncTime->diffInHours(now());
            if ($hoursSinceSync > 24) {
                $score -= min(20, ($hoursSinceSync - 24) * 0.5);
            }
            if (($lastSync->status ?? 'unknown') === 'error') {
                $score -= 15;
            }
        } else {
            $score -= 10;
        }

        // Deduct for rate limit issues
        if (isset($rateLimitInfo['remaining']) && isset($rateLimitInfo['limit'])) {
            $remainingPercent = ($rateLimitInfo['remaining'] / $rateLimitInfo['limit']) * 100;
            if ($remainingPercent < 10) {
                $score -= 20;
            } elseif ($remainingPercent < 30) {
                $score -= 10;
            }
        }

        return max(0, min(100, (int) $score));
    }

    /**
     * Get rate limit status string.
     */
    protected function getRateLimitStatus(?array $cached, int $recentHits): string
    {
        if ($recentHits > 50) {
            return 'critical';
        }
        if ($recentHits > 20) {
            return 'warning';
        }
        if (!$cached || !isset($cached['remaining'])) {
            return 'unknown';
        }
        if (isset($cached['limit']) && $cached['limit'] > 0) {
            $percent = ($cached['remaining'] / $cached['limit']) * 100;
            if ($percent < 10) {
                return 'critical';
            }
            if ($percent < 30) {
                return 'warning';
            }
        }
        return 'healthy';
    }

    /**
     * Get metrics for a specific connection.
     */
    protected function getConnectionMetrics($connection): array
    {
        // Get sync stats for last 7 days
        $syncStats = DB::table('cmis.platform_sync_logs')
            ->where('connection_id', $connection->connection_id)
            ->where('created_at', '>=', now()->subDays(7))
            ->select([
                DB::raw('COUNT(*) as total_syncs'),
                DB::raw("COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_syncs"),
                DB::raw('AVG(duration_ms) as avg_duration_ms'),
                DB::raw('SUM(entities_processed) as total_records_synced'),
            ])
            ->first();

        // Get daily sync counts
        $dailySyncs = DB::table('cmis.platform_sync_logs')
            ->where('connection_id', $connection->connection_id)
            ->where('created_at', '>=', now()->subDays(7))
            ->select([
                DB::raw("DATE(created_at) as date"),
                DB::raw('COUNT(*) as count'),
                DB::raw("COUNT(CASE WHEN status = 'completed' THEN 1 END) as success_count"),
            ])
            ->groupBy(DB::raw("DATE(created_at)"))
            ->orderBy('date')
            ->get();

        return [
            'sync_stats' => $syncStats,
            'daily_syncs' => $dailySyncs,
            'success_rate' => $syncStats && $syncStats->total_syncs > 0
                ? round(($syncStats->successful_syncs / $syncStats->total_syncs) * 100, 1)
                : null,
        ];
    }
}
