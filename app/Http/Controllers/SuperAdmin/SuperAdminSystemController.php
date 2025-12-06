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
            'scheduler' => $this->checkScheduler(),
            'mail' => $this->checkMail(),
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
        $perPage = $request->get('per_page', 50);
        $page = $request->get('page', 1);
        $level = $request->get('level');
        $search = $request->get('search');
        $date = $request->get('date', 'today');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!file_exists($logFile)) {
            if ($request->expectsJson()) {
                return response()->json(['logs' => [], 'pagination' => $this->emptyPagination()]);
            }
            return view('super-admin.system.logs', ['entries' => [], 'level' => $level]);
        }

        // Read log file and parse entries
        $logs = $this->tailFile($logFile, 5000); // Read more to filter
        $entries = $this->parseLogEntriesFormatted($logs, $level, $search, $date, $dateFrom, $dateTo);

        // Reverse to show newest first
        $entries = array_reverse($entries);

        // Paginate
        $total = count($entries);
        $offset = ($page - 1) * $perPage;
        $paginatedEntries = array_slice($entries, $offset, $perPage);

        if ($request->expectsJson()) {
            return response()->json([
                'logs' => $paginatedEntries,
                'pagination' => [
                    'current_page' => (int) $page,
                    'last_page' => (int) ceil($total / $perPage),
                    'per_page' => (int) $perPage,
                    'total' => $total,
                ],
            ]);
        }

        return view('super-admin.system.logs', compact('entries', 'level'));
    }

    /**
     * Get empty pagination structure.
     */
    protected function emptyPagination(): array
    {
        return [
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 50,
            'total' => 0,
        ];
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

            $driver = config('cache.default');
            $hitRate = null;
            $memoryUsed = null;
            $keys = 0;
            $driverLabel = ucfirst($driver);

            // Get Redis stats if using Redis
            if ($driver === 'redis') {
                try {
                    $info = Redis::info();

                    // Calculate hit rate
                    $hits = $info['keyspace_hits'] ?? 0;
                    $misses = $info['keyspace_misses'] ?? 0;
                    $total = $hits + $misses;
                    if ($total > 0) {
                        $hitRate = round(($hits / $total) * 100, 1);
                    } else {
                        $hitRate = 0;
                    }

                    // Memory used
                    $usedMemory = $info['used_memory_human'] ?? null;
                    if ($usedMemory) {
                        $memoryUsed = $usedMemory;
                    }

                    // Key count
                    $keys = $info['db0']['keys'] ?? Redis::dbSize() ?? 0;
                } catch (\Exception $e) {
                    // Redis stats not available
                    Log::debug('Could not get Redis stats: ' . $e->getMessage());
                }
            } elseif ($driver === 'file') {
                // For file cache, we can count cached files
                $cachePath = storage_path('framework/cache/data');
                if (is_dir($cachePath)) {
                    $iterator = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($cachePath, \RecursiveDirectoryIterator::SKIP_DOTS)
                    );
                    $keys = iterator_count($iterator);
                }
                $driverLabel = 'File';
            } elseif ($driver === 'array') {
                // Array driver is in-memory per request, no persistent stats
                $driverLabel = 'Array (In-Memory)';
            } elseif ($driver === 'database') {
                // Database cache - count entries
                try {
                    $keys = DB::table('cache')->count();
                } catch (\Exception $e) {
                    $keys = 0;
                }
                $driverLabel = 'Database';
            }

            return [
                'status' => $value === 'test' ? 'healthy' : 'degraded',
                'driver' => $driver,
                'driver_label' => $driverLabel,
                'hit_rate' => $hitRate,
                'memory_used' => $memoryUsed,
                'keys' => $keys,
                'supports_stats' => in_array($driver, ['redis']),
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

            // Get disk space information
            $path = storage_path();
            $totalBytes = @disk_total_space($path);
            $freeBytes = @disk_free_space($path);
            $usedBytes = $totalBytes - $freeBytes;

            $used = '-';
            $available = '-';
            $percentage = 0;

            if ($totalBytes > 0) {
                $percentage = round(($usedBytes / $totalBytes) * 100, 1);
                $used = $this->formatBytes($usedBytes);
                $available = $this->formatBytes($freeBytes);
            }

            $status = $content === 'test' ? 'healthy' : 'degraded';
            // Mark as degraded if disk is over 90% full
            if ($percentage >= 90) {
                $status = 'degraded';
            }

            return [
                'status' => $status,
                'driver' => config('filesystems.default'),
                'used' => $used,
                'available' => $available,
                'percentage' => $percentage,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format bytes to human-readable string.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    protected function checkExternalServices(): array
    {
        // Add checks for external services like APIs, etc.
        return [
            'status' => 'healthy',
            'services' => [],
        ];
    }

    protected function checkScheduler(): array
    {
        try {
            $lastRunFormatted = '-';
            $status = 'healthy';
            $lastRunTime = null;

            // Method 1: Check cache for scheduler last run
            $lastRun = Cache::get('schedule:last_run');
            if ($lastRun) {
                $lastRunTime = \Carbon\Carbon::parse($lastRun);
            }

            // Method 2: Check Laravel log file for evidence of scheduler runs
            if (!$lastRunTime) {
                $logFile = storage_path('logs/laravel.log');
                if (file_exists($logFile)) {
                    // Read last 200 lines to find scheduler activity
                    $logContent = $this->tailFile($logFile, 200);
                    $lines = explode("\n", $logContent);
                    $schedulerPatterns = [
                        '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*Scheduled posts processed/',
                        '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*Processing scheduled/',
                        '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*schedule:run/',
                        '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*Running scheduled command/',
                    ];

                    foreach (array_reverse($lines) as $line) {
                        foreach ($schedulerPatterns as $pattern) {
                            if (preg_match($pattern, $line, $matches)) {
                                $lastRunTime = \Carbon\Carbon::parse($matches[1]);
                                break 2;
                            }
                        }
                    }
                }
            }

            // Method 3: Check file modification time of scheduler output
            if (!$lastRunTime) {
                $schedulerOutputFile = storage_path('logs/scheduler.log');
                if (file_exists($schedulerOutputFile)) {
                    $lastRunTime = \Carbon\Carbon::createFromTimestamp(filemtime($schedulerOutputFile));
                }
            }

            // Format the last run time
            if ($lastRunTime) {
                $lastRunFormatted = $lastRunTime->diffForHumans();

                // If scheduler hasn't run in more than 5 minutes, it's degraded
                if ($lastRunTime->lt(now()->subMinutes(5))) {
                    $status = 'degraded';
                }
            } else {
                // No evidence of scheduler running - check if cron is configured
                $status = 'degraded';
            }

            // Get scheduled tasks using Artisan schedule:list command
            // This is the most reliable way to get tasks in HTTP context
            $tasksCount = 0;
            $nextRun = '-';

            try {
                \Artisan::call('schedule:list', ['--next' => true]);
                $output = \Artisan::output();

                // Count tasks by counting lines that have a cron expression pattern
                // Lines typically start with cron expression like "0 * * * *" or "*/5 * * * *"
                $lines = explode("\n", trim($output));
                foreach ($lines as $line) {
                    // Skip empty lines and header lines
                    $line = trim($line);
                    if (empty($line)) continue;

                    // Cron expressions start with a number or asterisk followed by whitespace and more cron parts
                    if (preg_match('/^[\*\d\/,\-]+\s+[\*\d\/,\-]+/', $line)) {
                        $tasksCount++;

                        // Extract "Next Due:" time from the line
                        if (preg_match('/Next Due:\s*(.+)$/i', $line, $matches)) {
                            $nextDueText = trim($matches[1]);
                            // Keep the earliest next run time
                            if ($nextRun === '-') {
                                $nextRun = $nextDueText;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to get schedule list', ['error' => $e->getMessage()]);
            }

            return [
                'status' => $status,
                'last_run' => $lastRunFormatted,
                'next_run' => $nextRun,
                'tasks_count' => $tasksCount,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'last_run' => '-',
                'next_run' => '-',
                'tasks_count' => 0,
            ];
        }
    }

    protected function checkMail(): array
    {
        try {
            $driver = config('mail.default');
            $status = 'healthy';
            $sentToday = 0;
            $lastSent = '-';

            // Check if mail table exists for logging
            try {
                $sentToday = DB::table('cmis.email_logs')
                    ->whereDate('created_at', today())
                    ->count();

                $lastEmail = DB::table('cmis.email_logs')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($lastEmail) {
                    $lastSent = \Carbon\Carbon::parse($lastEmail->created_at)->diffForHumans();
                }
            } catch (\Exception $e) {
                // Email logs table doesn't exist - that's ok
                // Try to check if SMTP is configured
                if ($driver === 'smtp') {
                    $host = config('mail.mailers.smtp.host');
                    $port = config('mail.mailers.smtp.port');
                    if (empty($host)) {
                        $status = 'degraded';
                    }
                }
            }

            return [
                'status' => $status,
                'driver' => $driver,
                'sent_today' => $sentToday,
                'last_sent' => $lastSent,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'sent_today' => 0,
                'last_sent' => '-',
            ];
        }
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

    /**
     * Parse log entries with full formatting for the logs viewer.
     *
     * Returns entries with: id, level, date, message, stack, context, file, line
     */
    protected function parseLogEntriesFormatted(string $logs, ?string $level, ?string $search, string $date, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $entries = [];
        // Match log entries with stack traces
        $pattern = '/\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}\.?\d*[^\]]*)\]\s+(\w+)\.(\w+):\s+(.+?)(?=\[\d{4}-\d{2}-\d{2}|$)/s';

        preg_match_all($pattern, $logs, $matches, PREG_SET_ORDER);

        // Calculate date filter
        $filterDate = null;
        $filterDateEnd = null;
        switch ($date) {
            case 'today':
                $filterDate = now()->startOfDay();
                $filterDateEnd = now()->endOfDay();
                break;
            case 'yesterday':
                $filterDate = now()->subDay()->startOfDay();
                $filterDateEnd = now()->subDay()->endOfDay();
                break;
            case 'week':
                $filterDate = now()->subDays(7)->startOfDay();
                $filterDateEnd = now()->endOfDay();
                break;
            case 'month':
                $filterDate = now()->subDays(30)->startOfDay();
                $filterDateEnd = now()->endOfDay();
                break;
            case 'custom':
                // Parse custom date range
                if ($dateFrom) {
                    try {
                        $filterDate = \Carbon\Carbon::parse($dateFrom);
                    } catch (\Exception $e) {
                        $filterDate = now()->subDays(7)->startOfDay();
                    }
                }
                if ($dateTo) {
                    try {
                        $filterDateEnd = \Carbon\Carbon::parse($dateTo);
                    } catch (\Exception $e) {
                        $filterDateEnd = now()->endOfDay();
                    }
                }
                break;
        }

        $id = 1;
        foreach ($matches as $match) {
            $entryLevel = strtolower($match[3]);

            // Filter by level
            if ($level && $entryLevel !== strtolower($level)) {
                continue;
            }

            // Parse timestamp
            $timestamp = $match[1];
            try {
                $dateTime = new \DateTime($timestamp);
                $logDate = \Carbon\Carbon::parse($dateTime);

                // Filter by date
                if ($filterDate && $filterDateEnd) {
                    if ($logDate < $filterDate || $logDate > $filterDateEnd) {
                        continue;
                    }
                }

                $formattedDate = $logDate->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $formattedDate = $timestamp;
            }

            $fullMessage = trim($match[4]);

            // Filter by search
            if ($search && stripos($fullMessage, $search) === false) {
                continue;
            }

            // Extract file and line from message
            $file = null;
            $line = null;
            if (preg_match('/in\s+([^\s]+\.php)(?::(\d+))?/', $fullMessage, $fileMatch)) {
                $file = $fileMatch[1];
                $line = $fileMatch[2] ?? null;
            }

            // Separate message from stack trace
            $message = $fullMessage;
            $stack = null;
            $firstNewline = strpos($fullMessage, "\n");
            if ($firstNewline !== false) {
                $message = substr($fullMessage, 0, $firstNewline);
                $stack = trim(substr($fullMessage, $firstNewline));
            }

            // Truncate message if too long
            if (strlen($message) > 200) {
                $message = substr($message, 0, 200) . '...';
            }

            $entries[] = [
                'id' => $id++,
                'level' => $entryLevel,
                'date' => $formattedDate,
                'message' => $message,
                'stack' => $stack,
                'context' => null,
                'file' => $file,
                'line' => $line,
                'environment' => $match[2],
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
