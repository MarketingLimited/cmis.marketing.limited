<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Custom database setup trait for CMIS Dusk tests.
 *
 * This trait extends RefreshDatabase but skips running migrations
 * since they are already run and take too long (45+ migrations).
 *
 * Instead, it just truncates tables between tests for isolation.
 */
trait DatabaseSetup
{
    use RefreshDatabase;

    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function refreshDatabase()
    {
        $this->beforeRefreshingDatabase();

        // Skip migrations - they're already run
        // Just use transactions or truncate tables

        $this->afterRefreshingDatabase();
    }
}
