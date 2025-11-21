<?php

namespace Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Parallel Test Case Support
 *
 * Provides database-per-process support for parallel test execution.
 * Each ParaTest worker process gets its own isolated test database.
 */
trait ParallelTestCase
{
    /**
     * Set up database for parallel testing
     *
     * Automatically selects the appropriate test database based on
     * the ParaTest token (worker ID).
     */
    protected function setUpParallelDatabase(): void
    {
        // Check if parallel testing is enabled
        if (!env('PARALLEL_TESTING', false)) {
            return;
        }

        // Get ParaTest token (worker ID)
        $token = env('TEST_TOKEN', null);

        if ($token !== null) {
            // Use database specific to this worker
            $database = "cmis_test_{$token}";

            // Update database configuration for this process
            config([
                'database.connections.pgsql.database' => $database,
            ]);

            // Reconnect to the new database
            DB::purge('pgsql');
            DB::reconnect('pgsql');

            // Log which database we're using (useful for debugging)
            if (env('APP_DEBUG', false)) {
                echo "\n[Worker {$token}] Using database: {$database}\n";
            }
        }
    }

    /**
     * Verify database isolation
     *
     * Ensures that each test process is using its own database.
     *
     * @return string Current database name
     */
    protected function getCurrentTestDatabase(): string
    {
        $result = DB::selectOne('SELECT current_database() as db');
        return $result->db;
    }

    /**
     * Get the ParaTest worker ID
     *
     * @return int|null
     */
    protected function getParaTestWorkerId(): ?int
    {
        $token = env('TEST_TOKEN', null);
        return $token !== null ? (int) $token : null;
    }

    /**
     * Check if running in parallel mode
     *
     * @return bool
     */
    protected function isParallelTesting(): bool
    {
        return env('PARALLEL_TESTING', false) && env('TEST_TOKEN', null) !== null;
    }
}
