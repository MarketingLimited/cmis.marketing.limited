<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

/**
 * Super Admin System Controller
 *
 * Provides system health monitoring and management tools.
 */
class SuperAdminSystemController extends Controller
{
    use ApiResponse;

    /**
     * Display the system health dashboard.
     */
    public function health(Request $request)
    {
        $health = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'services' => $this->checkExternalServices(),
        ];

        $overallStatus = $this->calculateOverallStatus($health);
        $recentErrors = $this->getRecentErrors(10);

        if ($request->expectsJson()) {
            return $this->success([
                'status' => $overallStatus,
                'checks' => $health,
                'recent_errors' => $recentErrors,
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        return view('super-admin.system.health', compact('health', 'overallStatus', 'recentErrors'));
    }

    /**
     * View Laravel logs.
     */
    public function logs(Request $request)
    {
        $logFile = storage_path('logs/laravel.log');
        $lines = $request->get('lines', 100);
        $level = $request->get('level'); // error, warning, info, debug

        if (!file_exists($logFile)) {
            return $this->success(['logs' => [], 'message' => 'No log file found']);
        }

        // Read last N lines
        $logs = $this->tailFile($logFile, $lines * 10); // Read more to filter

        // Parse log entries
        $entries = $this->parseLogEntries($logs, $level);
        $entries = array_slice($entries, -$lines);

        if ($request->expectsJson()) {
            return $this->success(['logs' => $entries]);
        }

        return view('super-admin.system.logs', compact('entries', 'level'));
    }

    /**
     * View queue status.
     */
    public function queues(Request $request)
    {
        $queueStats = $this->getQueueStats();
        $failedJobs = $this->getFailedJobs($request->get('limit', 20));
        $recentJobs = $this->getRecentJobs($request->get('limit', 20));

        if ($request->expectsJson()) {
            return $this->success([
                'stats' => $queueStats,
                'failed_jobs' => $failedJobs,
                'recent_jobs' => $recentJobs,
            ]);
        }

        return view('super-admin.system.queues', compact('queueStats', 'failedJobs', 'recentJobs'));
    }

    /**
     * Retry a failed job.
     */
    public function retryJob(Request $request, string $jobId)
    {
        try {
            Artisan::call('queue:retry', ['id' => [$jobId]]);
            return $this->success(null, __('super_admin.job_retried'));
        } catch (\Exception $e) {
            return $this->error(__('super_admin.job_retry_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Flush failed jobs.
     */
    public function flushFailedJobs(Request $request)
    {
        try {
            Artisan::call('queue:flush');
            return $this->success(null, __('super_admin.failed_jobs_flushed'));
        } catch (\Exception $e) {
            return $this->error(__('super_admin.flush_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Clear application caches.
     */
    public function clearCache(Request $request)
    {
        $type = $request->get('type', 'all');

        try {
            switch ($type) {
                case 'config':
                    Artisan::call('config:clear');
                    break;
                case 'route':
                    Artisan::call('route:clear');
                    break;
                case 'view':
                    Artisan::call('view:clear');
                    break;
                case 'cache':
                    Artisan::call('cache:clear');
                    break;
                case 'all':
                default:
                    Artisan::call('optimize:clear');
                    break;
            }

            return $this->success(null, __('super_admin.cache_cleared'));
        } catch (\Exception $e) {
            return $this->error(__('super_admin.cache_clear_failed') . ': ' . $e->getMessage());
        }
    }

    /**
     * Get database statistics.
     */
    public function databaseStats(Request $request)
    {
        $stats = [
            'size' => $this->getDatabaseSize(),
            'connections' => $this->getConnectionStats(),
            'tables' => $this->getTableStats(),
            'slow_queries' => $this->getSlowQueries(),
        ];

        return $this->success($stats);
    }

    /**
     * Get scheduled tasks status.
     */
    public function scheduledTasks(Request $request)
    {
        $schedule = [];

        // Get scheduled tasks from kernel
        try {
            $kernel = app(\Illuminate\Console\Scheduling\Schedule::class);
            $events = $kernel->events();

            foreach ($events as $event) {
                $schedule[] = [
                    'command' => $event->command ?? $event->description ?? 'Closure',
                    'expression' => $event->expression,
                    'next_run' => $event->nextRunDate()->format('Y-m-d H:i:s'),
                    'timezone' => $event->timezone ?? config('app.timezone'),
                    'without_overlapping' => $event->withoutOverlapping ?? false,
                    'on_one_server' => $event->onOneServer ?? false,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get scheduled tasks', ['error' => $e->getMessage()]);
        }

        return $this->success($schedule);
    }

    /**
     * Get super admin action logs.
     */
    public function actionLogs(Request $request)
    {
        $query = DB::table('cmis.super_admin_actions')
            ->leftJoin('cmis.users', 'cmis.super_admin_actions.admin_user_id', '=', 'cmis.users.user_id')
            ->select(
                'cmis.super_admin_actions.*',
                'cmis.users.name as admin_name',
                'cmis.users.email as admin_email'
            )
            ->orderBy('created_at', 'desc');

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->target_type);
        }

        if ($request->filled('admin_user_id')) {
            $query->where('admin_user_id', $request->admin_user_id);
        }

        $logs = $query->paginate($request->get('per_page', 50));

        if ($request->expectsJson()) {
            return $this->paginated($logs);
        }

        return view('super-admin.system.action-logs', compact('logs'));
    }

    // ===== Health Check Methods =====

    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);

            return [
                'status' => $value === 'test' ? 'healthy' : 'degraded',
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkQueue(): array
    {
        try {
            $failedCount = DB::table('failed_jobs')->count();
            $pendingCount = 0;

            // Try to get Redis queue length
            if (config('queue.default') === 'redis') {
                try {
                    $pendingCount = Redis::llen('queues:default') ?? 0;
                } catch (\Exception $e) {
                    // Ignore Redis errors
                }
            }

            return [
                'status' => $failedCount > 100 ? 'degraded' : 'healthy',
                'driver' => config('queue.default'),
                'failed_jobs' => $failedCount,
                'pending_jobs' => $pendingCount,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $testFile = 'health_check_' . time() . '.txt';
            $disk->put($testFile, 'test');
            $content = $disk->get($testFile);
            $disk->delete($testFile);

            return [
                'status' => $content === 'test' ? 'healthy' : 'degraded',
                'driver' => config('filesystems.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkExternalServices(): array
    {
        // Add checks for external services like APIs, etc.
        return [
            'status' => 'healthy',
            'services' => [],
        ];
    }

    protected function calculateOverallStatus(array $health): string
    {
        $statuses = array_column($health, 'status');

        if (in_array('unhealthy', $statuses)) {
            return 'unhealthy';
        }
        if (in_array('degraded', $statuses)) {
            return 'degraded';
        }
        return 'healthy';
    }

    // ===== Helper Methods =====

    protected function tailFile(string $file, int $lines): string
    {
        $handle = fopen($file, 'r');
        $buffer = '';
        $lineCount = 0;

        fseek($handle, -1, SEEK_END);
        $pos = ftell($handle);

        while ($pos >= 0 && $lineCount < $lines) {
            $char = fgetc($handle);
            if ($char === "\n") {
                $lineCount++;
            }
            $buffer = $char . $buffer;
            fseek($handle, --$pos);
        }

        fclose($handle);
        return $buffer;
    }

    protected function parseLogEntries(string $logs, ?string $level): array
    {
        $entries = [];
        $pattern = '/\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}\.?\d*[^\]]*)\]\s+(\w+)\.(\w+):\s+(.+?)(?=\[\d{4}-\d{2}-\d{2}|$)/s';

        preg_match_all($pattern, $logs, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $entryLevel = strtolower($match[3]);

            if ($level && $entryLevel !== strtolower($level)) {
                continue;
            }

            $entries[] = [
                'timestamp' => $match[1],
                'environment' => $match[2],
                'level' => $entryLevel,
                'message' => trim($match[4]),
            ];
        }

        return $entries;
    }

    protected function getQueueStats(): array
    {
        return [
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'jobs_last_hour' => DB::table('jobs')
                ->where('created_at', '>=', now()->subHour())
                ->count(),
        ];
    }

    protected function getFailedJobs(int $limit): array
    {
        return DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'connection' => $job->connection,
                    'queue' => $job->queue,
                    'failed_at' => $job->failed_at,
                    'exception' => substr($job->exception, 0, 500),
                ];
            })
            ->toArray();
    }

    protected function getRecentJobs(int $limit): array
    {
        return DB::table('jobs')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    protected function getDatabaseSize(): array
    {
        try {
            $dbName = config('database.connections.pgsql.database');
            $result = DB::selectOne("
                SELECT pg_size_pretty(pg_database_size(?)) as size,
                       pg_database_size(?) as bytes
            ", [$dbName, $dbName]);

            return [
                'size' => $result->size,
                'bytes' => $result->bytes,
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    protected function getConnectionStats(): array
    {
        try {
            $result = DB::selectOne("
                SELECT count(*) as total,
                       sum(case when state = 'active' then 1 else 0 end) as active,
                       sum(case when state = 'idle' then 1 else 0 end) as idle
                FROM pg_stat_activity
                WHERE datname = current_database()
            ");

            return [
                'total' => $result->total,
                'active' => $result->active,
                'idle' => $result->idle,
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    protected function getTableStats(): array
    {
        try {
            return DB::select("
                SELECT schemaname, relname as table_name,
                       pg_size_pretty(pg_total_relation_size(schemaname || '.' || relname)) as total_size,
                       n_live_tup as row_count
                FROM pg_stat_user_tables
                WHERE schemaname = 'cmis'
                ORDER BY pg_total_relation_size(schemaname || '.' || relname) DESC
                LIMIT 20
            ");
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getSlowQueries(): array
    {
        try {
            return DB::select("
                SELECT query, calls, mean_exec_time, total_exec_time
                FROM pg_stat_statements
                WHERE dbid = (SELECT oid FROM pg_database WHERE datname = current_database())
                ORDER BY mean_exec_time DESC
                LIMIT 10
            ");
        } catch (\Exception $e) {
            // pg_stat_statements extension might not be enabled
            return [];
        }
    }

    /**
     * Get recent errors from Laravel logs.
     *
     * @param int $limit Maximum number of errors to return
     * @return array Recent error entries
     */
    protected function getRecentErrors(int $limit = 10): array
    {
        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            return [];
        }

        try {
            // Read last portion of log file
            $logs = $this->tailFile($logFile, $limit * 50); // Read more to filter

            // Parse for error/critical/alert/emergency entries
            $entries = $this->parseLogEntries($logs, null);

            // Filter only errors and above
            $errorLevels = ['error', 'critical', 'alert', 'emergency'];
            $errors = array_filter($entries, function ($entry) use ($errorLevels) {
                return in_array(strtolower($entry['level']), $errorLevels);
            });

            // Take only the last N errors
            $errors = array_slice(array_values($errors), -$limit);

            // Format for frontend
            $formatted = [];
            $id = 1;
            foreach ($errors as $error) {
                // Extract file path from message if present
                $file = '-';
                if (preg_match('/in\s+([^\s]+\.php)(?::(\d+))?/', $error['message'], $matches)) {
                    $file = $matches[1] . (isset($matches[2]) ? ':' . $matches[2] : '');
                }

                // Clean up message - get first line or truncate
                $message = $error['message'];
                $firstLineEnd = strpos($message, "\n");
                if ($firstLineEnd !== false) {
                    $message = substr($message, 0, $firstLineEnd);
                }
                // Truncate if too long
                if (strlen($message) > 200) {
                    $message = substr($message, 0, 200) . '...';
                }

                // Parse timestamp for display
                $time = $error['timestamp'];
                try {
                    $dateTime = new \DateTime($error['timestamp']);
                    $time = $dateTime->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    // Keep original timestamp if parsing fails
                }

                $formatted[] = [
                    'id' => $id++,
                    'message' => trim($message),
                    'time' => $time,
                    'file' => $file,
                    'level' => strtoupper($error['level']),
                    'environment' => $error['environment'],
                ];
            }

            // Return in reverse chronological order (newest first)
            return array_reverse($formatted);
        } catch (\Exception $e) {
            Log::warning('Failed to get recent errors', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
