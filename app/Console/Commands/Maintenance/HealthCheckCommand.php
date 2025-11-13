<?php

namespace App\Console\Commands\Maintenance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class HealthCheckCommand extends Command
{
    protected $signature = 'monitoring:health {--verbose : Show detailed information}';
    protected $description = 'Check system health (database, cache, queue, storage)';

    public function handle()
    {
        $this->info('🏥 Running system health check...');
        $this->newLine();

        $checks = [
            'Database' => [$this, 'checkDatabase'],
            'Cache' => [$this, 'checkCache'],
            'Storage' => [$this, 'checkStorage'],
            'Queue' => [$this, 'checkQueue'],
        ];

        $results = [];
        foreach ($checks as $name => $callback) {
            $result = call_user_func($callback);
            $results[$name] = $result;

            $icon = $result['status'] === 'healthy' ? '✅' : '❌';
            $this->line("{$icon} {$name}: {$result['message']}");

            if ($this->option('verbose') && isset($result['details'])) {
                foreach ($result['details'] as $detail) {
                    $this->line("   - {$detail}");
                }
            }
        }

        $this->newLine();

        $healthyCount = count(array_filter($results, fn($r) => $r['status'] === 'healthy'));
        $totalCount = count($results);

        if ($healthyCount === $totalCount) {
            $this->info("✅ System is healthy ({$healthyCount}/{$totalCount} checks passed)");
            return self::SUCCESS;
        } else {
            $this->error("⚠️  System has issues ({$healthyCount}/{$totalCount} checks passed)");
            return self::FAILURE;
        }
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $tableCount = count(DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'cmis'"));

            return [
                'status' => 'healthy',
                'message' => 'Connected',
                'details' => ["Tables: {$tableCount}", 'Connection: PostgreSQL']
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Connection failed',
                'details' => [$e->getMessage()]
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
                'status' => $value === 'test' ? 'healthy' : 'unhealthy',
                'message' => $value === 'test' ? 'Working' : 'Failed',
                'details' => ['Driver: ' . config('cache.default')]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Failed',
                'details' => [$e->getMessage()]
            ];
        }
    }

    protected function checkStorage(): array
    {
        try {
            $disks = ['local', 'public'];
            $details = [];

            foreach ($disks as $disk) {
                if (\Storage::disk($disk)->exists('.')) {
                    $details[] = "{$disk}: Available";
                }
            }

            return [
                'status' => 'healthy',
                'message' => 'Available',
                'details' => $details
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Failed',
                'details' => [$e->getMessage()]
            ];
        }
    }

    protected function checkQueue(): array
    {
        try {
            $driver = config('queue.default');

            return [
                'status' => 'healthy',
                'message' => 'Configured',
                'details' => ["Driver: {$driver}"]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Failed',
                'details' => [$e->getMessage()]
            ];
        }
    }
}
