<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

/**
 * Trait for optimizing test performance.
 *
 * This trait provides methods to speed up tests by:
 * - Disabling unnecessary features
 * - Using database transactions
 * - Caching test data
 * - Batch operations
 */
trait OptimizesTestPerformance
{
    /**
     * Disable events for faster tests.
     *
     * @return void
     */
    protected function withoutEvents(): void
    {
        Event::fake();
    }

    /**
     * Disable queue jobs for faster tests.
     *
     * @return void
     */
    protected function withoutJobs(): void
    {
        Queue::fake();
    }

    /**
     * Disable model observers for faster tests.
     *
     * @param array $models
     * @return void
     */
    protected function withoutModelObservers(array $models = []): void
    {
        foreach ($models as $model) {
            $model::unsetEventDispatcher();
        }
    }

    /**
     * Execute a callback without foreign key checks (faster inserts).
     *
     * @param callable $callback
     * @return mixed
     */
    protected function withoutForeignKeyChecks(callable $callback): mixed
    {
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        try {
            return $callback();
        } finally {
            DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        }
    }

    /**
     * Bulk insert records for faster test data creation.
     *
     * @param string $table
     * @param array $records
     * @return void
     */
    protected function bulkInsert(string $table, array $records): void
    {
        $chunks = array_chunk($records, 1000);

        foreach ($chunks as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    /**
     * Create test data in batches for performance.
     *
     * @param callable $factory
     * @param int $count
     * @param int $batchSize
     * @return array
     */
    protected function createInBatches(callable $factory, int $count, int $batchSize = 100): array
    {
        $results = [];
        $batches = ceil($count / $batchSize);

        DB::transaction(function () use ($factory, $count, $batchSize, $batches, &$results) {
            for ($i = 0; $i < $batches; $i++) {
                $remaining = min($batchSize, $count - ($i * $batchSize));

                for ($j = 0; $j < $remaining; $j++) {
                    $results[] = $factory();
                }
            }
        });

        return $results;
    }

    /**
     * Cache test data to avoid recreation.
     *
     * @param string $key
     * @param callable $callback
     * @param int $ttl
     * @return mixed
     */
    protected function cacheTestData(string $key, callable $callback, int $ttl = 3600): mixed
    {
        return Cache::remember("test:{$key}", $ttl, $callback);
    }

    /**
     * Clear all test caches.
     *
     * @return void
     */
    protected function clearTestCaches(): void
    {
        $keys = Cache::getStore()->getKeys('test:*');

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Measure test execution time.
     *
     * @param callable $callback
     * @return array ['result' => mixed, 'time' => float]
     */
    protected function measureTime(callable $callback): array
    {
        $start = microtime(true);
        $result = $callback();
        $end = microtime(true);

        return [
            'result' => $result,
            'time' => round(($end - $start) * 1000, 2), // milliseconds
        ];
    }

    /**
     * Assert test execution is within time limit.
     *
     * @param callable $callback
     * @param int $maxMilliseconds
     * @return void
     */
    protected function assertExecutionTime(callable $callback, int $maxMilliseconds): void
    {
        $measured = $this->measureTime($callback);

        $this->assertLessThanOrEqual(
            $maxMilliseconds,
            $measured['time'],
            "Test execution took {$measured['time']}ms, expected max {$maxMilliseconds}ms"
        );
    }

    /**
     * Truncate tables for faster cleanup.
     *
     * @param array $tables
     * @return void
     */
    protected function truncateTables(array $tables): void
    {
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    }

    /**
     * Disable query logging for faster tests.
     *
     * @return void
     */
    protected function disableQueryLog(): void
    {
        DB::connection()->disableQueryLog();
    }

    /**
     * Enable query logging.
     *
     * @return void
     */
    protected function enableQueryLog(): void
    {
        DB::connection()->enableQueryLog();
    }

    /**
     * Get query count during callback execution.
     *
     * @param callable $callback
     * @return array ['result' => mixed, 'query_count' => int]
     */
    protected function countQueries(callable $callback): array
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        $result = $callback();

        $queries = DB::getQueryLog();
        $count = count($queries);

        DB::disableQueryLog();

        return [
            'result' => $result,
            'query_count' => $count,
        ];
    }

    /**
     * Assert query count is within expected range.
     *
     * @param callable $callback
     * @param int $maxQueries
     * @return void
     */
    protected function assertQueryCount(callable $callback, int $maxQueries): void
    {
        $measured = $this->countQueries($callback);

        $this->assertLessThanOrEqual(
            $maxQueries,
            $measured['query_count'],
            "Query count is {$measured['query_count']}, expected max {$maxQueries}"
        );
    }

    /**
     * Seed test data only once per test class.
     *
     * @param string $key
     * @param callable $seeder
     * @return void
     */
    protected function seedOnce(string $key, callable $seeder): void
    {
        static $seeded = [];

        $testClass = static::class;
        $cacheKey = "{$testClass}:{$key}";

        if (!isset($seeded[$cacheKey])) {
            $seeder();
            $seeded[$cacheKey] = true;
        }
    }
}
