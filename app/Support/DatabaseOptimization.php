<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Database Optimization Helpers
 *
 * Provides utilities for query optimization and performance monitoring
 */
class DatabaseOptimization
{
    /**
     * Add index hint to query (PostgreSQL)
     *
     * @param Builder $query Query builder
     * @param string $index Index name
     * @return Builder
     */
    public static function useIndex(Builder $query, string $index): Builder
    {
        // PostgreSQL doesn't support index hints like MySQL
        // This is a placeholder for future optimization
        return $query;
    }

    /**
     * Chunk large result sets efficiently
     *
     * @param Builder $query Query builder
     * @param int $chunkSize Chunk size
     * @param callable $callback Callback function
     * @return void
     */
    public static function chunkById(Builder $query, int $chunkSize, callable $callback): void
    {
        $query->chunkById($chunkSize, $callback);
    }

    /**
     * Get query execution plan (EXPLAIN)
     *
     * @param string $sql SQL query
     * @param array $bindings Query bindings
     * @return array Execution plan
     */
    public static function explainQuery(string $sql, array $bindings = []): array
    {
        $result = DB::select("EXPLAIN ANALYZE $sql", $bindings);
        return array_map(fn($row) => (array) $row, $result);
    }

    /**
     * Log slow queries for analysis
     *
     * @param float $threshold Threshold in milliseconds
     * @return void
     */
    public static function logSlowQueries(float $threshold = 100): void
    {
        DB::listen(function ($query) use ($threshold) {
            if ($query->time > $threshold) {
                \Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                ]);
            }
        });
    }

    /**
     * Optimize query with eager loading suggestions
     *
     * @param Model $model Model instance
     * @return array Suggested eager loads
     */
    public static function suggestEagerLoads(Model $model): array
    {
        $suggestions = [];
        $relations = $model->getRelations();

        foreach ($relations as $name => $relation) {
            if (!$model->relationLoaded($name)) {
                $suggestions[] = $name;
            }
        }

        return $suggestions;
    }

    /**
     * Analyze table for optimization opportunities
     *
     * @param string $table Table name
     * @return array Analysis results
     */
    public static function analyzeTable(string $table): array
    {
        // Get table size
        $sizeQuery = "
            SELECT
                pg_size_pretty(pg_total_relation_size(?)) as total_size,
                pg_size_pretty(pg_relation_size(?)) as table_size,
                pg_size_pretty(pg_total_relation_size(?) - pg_relation_size(?)) as indexes_size
        ";

        $size = DB::selectOne($sizeQuery, [$table, $table, $table, $table]);

        // Get index information
        $indexes = DB::select("
            SELECT indexname, indexdef
            FROM pg_indexes
            WHERE tablename = ?
        ", [$table]);

        // Get row count estimate
        $stats = DB::selectOne("
            SELECT reltuples::bigint AS row_estimate
            FROM pg_class
            WHERE relname = ?
        ", [$table]);

        return [
            'table' => $table,
            'size' => (array) $size,
            'row_estimate' => $stats->row_estimate ?? 0,
            'indexes' => array_map(fn($idx) => (array) $idx, $indexes),
        ];
    }

    /**
     * Suggest missing indexes based on query patterns
     *
     * @param string $table Table name
     * @return array Index suggestions
     */
    public static function suggestIndexes(string $table): array
    {
        // This would analyze pg_stat_user_tables and pg_stat_user_indexes
        // For now, return common patterns
        return [
            'Consider adding index on frequently queried columns',
            'Check for composite indexes on multi-column WHERE clauses',
            'Review index usage with pg_stat_user_indexes',
        ];
    }

    /**
     * Vacuum and analyze table
     *
     * @param string $table Table name
     * @param bool $full Full vacuum
     * @return bool Success
     */
    public static function vacuumTable(string $table, bool $full = false): bool
    {
        try {
            $command = $full ? "VACUUM FULL ANALYZE $table" : "VACUUM ANALYZE $table";
            DB::statement($command);
            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to vacuum table $table", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get database statistics
     *
     * @return array Statistics
     */
    public static function getDatabaseStats(): array
    {
        $dbSize = DB::selectOne("SELECT pg_size_pretty(pg_database_size(current_database())) as size");

        $connectionStats = DB::select("
            SELECT state, count(*)
            FROM pg_stat_activity
            WHERE datname = current_database()
            GROUP BY state
        ");

        $cacheHitRatio = DB::selectOne("
            SELECT
                round(100.0 * sum(blks_hit) / nullif(sum(blks_hit + blks_read), 0), 2) as cache_hit_ratio
            FROM pg_stat_database
            WHERE datname = current_database()
        ");

        return [
            'database_size' => $dbSize->size,
            'connections' => array_map(fn($stat) => (array) $stat, $connectionStats),
            'cache_hit_ratio' => $cacheHitRatio->cache_hit_ratio ?? 0,
        ];
    }
}
