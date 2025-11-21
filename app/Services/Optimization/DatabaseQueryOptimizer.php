<?php

namespace App\Services\Optimization;

use Illuminate\Support\Facades\{DB, Cache, Log};
use Carbon\Carbon;

/**
 * Database Query Optimizer Service (Phase 6)
 *
 * Provides query analysis, optimization recommendations, and automated query tuning
 */
class DatabaseQueryOptimizer
{
    // Query performance thresholds (milliseconds)
    const SLOW_QUERY_THRESHOLD = 1000;
    const WARNING_QUERY_THRESHOLD = 500;
    const OPTIMAL_QUERY_THRESHOLD = 100;

    // Cache TTLs
    const QUERY_CACHE_TTL = 300; // 5 minutes
    const STATS_CACHE_TTL = 3600; // 1 hour

    /**
     * Analyze query performance and provide optimization recommendations
     *
     * @param string $query
     * @param array $bindings
     * @return array
     */
    public function analyzeQuery(string $query, array $bindings = []): array
    {
        try {
            // Execute EXPLAIN ANALYZE
            $explainQuery = "EXPLAIN (ANALYZE, BUFFERS, FORMAT JSON) " . $query;
            $result = DB::select($explainQuery, $bindings);

            $plan = json_decode($result[0]->{'QUERY PLAN'}, true);
            $executionTime = $plan[0]['Execution Time'] ?? 0;

            // Analyze the plan
            $analysis = $this->analyzePlan($plan[0]['Plan']);

            // Generate recommendations
            $recommendations = $this->generateRecommendations($analysis, $executionTime);

            // Determine severity
            $severity = $this->determineSeverity($executionTime, $analysis);

            return [
                'success' => true,
                'query' => $query,
                'execution_time_ms' => round($executionTime, 2),
                'severity' => $severity,
                'analysis' => $analysis,
                'recommendations' => $recommendations,
                'plan' => $plan
            ];

        } catch (\Exception $e) {
            Log::error('Query analysis failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Query analysis failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Analyze execution plan
     *
     * @param array $plan
     * @return array
     */
    protected function analyzePlan(array $plan): array
    {
        $issues = [];
        $stats = [
            'seq_scans' => 0,
            'index_scans' => 0,
            'nested_loops' => 0,
            'hash_joins' => 0,
            'merge_joins' => 0,
            'total_cost' => $plan['Total Cost'] ?? 0,
            'rows_returned' => $plan['Actual Rows'] ?? 0,
            'rows_estimated' => $plan['Plan Rows'] ?? 0
        ];

        $this->traversePlan($plan, $issues, $stats);

        // Calculate estimation accuracy
        if ($stats['rows_estimated'] > 0) {
            $stats['estimation_accuracy'] = min(
                $stats['rows_returned'] / $stats['rows_estimated'],
                $stats['rows_estimated'] / $stats['rows_returned']
            ) * 100;
        }

        return [
            'issues' => $issues,
            'statistics' => $stats
        ];
    }

    /**
     * Traverse execution plan recursively
     *
     * @param array $node
     * @param array &$issues
     * @param array &$stats
     */
    protected function traversePlan(array $node, array &$issues, array &$stats): void
    {
        $nodeType = $node['Node Type'] ?? '';

        // Detect sequential scans
        if ($nodeType === 'Seq Scan') {
            $stats['seq_scans']++;
            $tableName = $node['Relation Name'] ?? 'unknown';
            $rows = $node['Actual Rows'] ?? 0;

            if ($rows > 1000) {
                $issues[] = [
                    'type' => 'sequential_scan',
                    'severity' => 'high',
                    'description' => "Sequential scan on table '{$tableName}' with {$rows} rows",
                    'suggestion' => "Consider adding an index to improve query performance"
                ];
            }
        }

        // Detect index scans
        if ($nodeType === 'Index Scan' || $nodeType === 'Index Only Scan') {
            $stats['index_scans']++;
        }

        // Detect nested loops
        if ($nodeType === 'Nested Loop') {
            $stats['nested_loops']++;
            $actualLoops = $node['Actual Loops'] ?? 1;

            if ($actualLoops > 1000) {
                $issues[] = [
                    'type' => 'nested_loop',
                    'severity' => 'medium',
                    'description' => "Nested loop with {$actualLoops} iterations",
                    'suggestion' => "Consider using hash join or merge join instead"
                ];
            }
        }

        // Detect hash joins
        if ($nodeType === 'Hash Join') {
            $stats['hash_joins']++;
        }

        // Detect merge joins
        if ($nodeType === 'Merge Join') {
            $stats['merge_joins']++;
        }

        // Check for large estimation errors
        $actualRows = $node['Actual Rows'] ?? 0;
        $planRows = $node['Plan Rows'] ?? 0;

        if ($planRows > 0 && $actualRows > 0) {
            $estimationError = abs($actualRows - $planRows) / $planRows * 100;

            if ($estimationError > 50 && $planRows > 100) {
                $issues[] = [
                    'type' => 'estimation_error',
                    'severity' => 'medium',
                    'description' => "Large estimation error: estimated {$planRows} rows, got {$actualRows}",
                    'suggestion' => "Consider running ANALYZE on affected tables to update statistics"
                ];
            }
        }

        // Recursively traverse child plans
        if (isset($node['Plans'])) {
            foreach ($node['Plans'] as $childPlan) {
                $this->traversePlan($childPlan, $issues, $stats);
            }
        }
    }

    /**
     * Generate optimization recommendations
     *
     * @param array $analysis
     * @param float $executionTime
     * @return array
     */
    protected function generateRecommendations(array $analysis, float $executionTime): array
    {
        $recommendations = [];
        $stats = $analysis['statistics'];

        // High execution time
        if ($executionTime > self::SLOW_QUERY_THRESHOLD) {
            $recommendations[] = [
                'priority' => 'critical',
                'category' => 'performance',
                'message' => "Query execution time ({$executionTime}ms) exceeds threshold",
                'action' => "Review query structure and consider adding indexes"
            ];
        }

        // Multiple sequential scans
        if ($stats['seq_scans'] > 2) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'indexing',
                'message' => "Query contains {$stats['seq_scans']} sequential scans",
                'action' => "Add indexes on frequently filtered columns"
            ];
        }

        // Poor estimation accuracy
        if (isset($stats['estimation_accuracy']) && $stats['estimation_accuracy'] < 50) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'statistics',
                'message' => "Poor query planner estimation accuracy",
                'action' => "Run ANALYZE on affected tables to update statistics"
            ];
        }

        // No index usage
        if ($stats['index_scans'] === 0 && $stats['seq_scans'] > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'indexing',
                'message' => "Query does not use any indexes",
                'action' => "Create appropriate indexes for WHERE, JOIN, and ORDER BY clauses"
            ];
        }

        // Excessive nested loops
        if ($stats['nested_loops'] > 3) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'joins',
                'message' => "Query contains multiple nested loops",
                'action' => "Consider restructuring joins or adding indexes to enable hash/merge joins"
            ];
        }

        return $recommendations;
    }

    /**
     * Determine query severity level
     *
     * @param float $executionTime
     * @param array $analysis
     * @return string
     */
    protected function determineSeverity(float $executionTime, array $analysis): string
    {
        // Critical if slow query with issues
        if ($executionTime > self::SLOW_QUERY_THRESHOLD && count($analysis['issues']) > 0) {
            return 'critical';
        }

        // High if slow query or multiple issues
        if ($executionTime > self::WARNING_QUERY_THRESHOLD || count($analysis['issues']) > 2) {
            return 'high';
        }

        // Medium if some issues
        if (count($analysis['issues']) > 0) {
            return 'medium';
        }

        // Low if fast with no issues
        if ($executionTime < self::OPTIMAL_QUERY_THRESHOLD) {
            return 'optimal';
        }

        return 'low';
    }

    /**
     * Get missing indexes recommendations for a table
     *
     * @param string $tableName
     * @return array
     */
    public function getMissingIndexes(string $tableName): array
    {
        try {
            // Query PostgreSQL statistics to find missing indexes
            $query = "
                SELECT
                    schemaname,
                    tablename,
                    attname,
                    null_frac,
                    avg_width,
                    n_distinct,
                    correlation
                FROM pg_stats
                WHERE schemaname = 'cmis'
                  AND tablename = ?
                  AND null_frac < 0.9
                  AND n_distinct > 10
                ORDER BY null_frac ASC, n_distinct DESC
                LIMIT 10
            ";

            $stats = DB::select($query, [$tableName]);

            $recommendations = [];

            foreach ($stats as $stat) {
                // Recommend index if column has good selectivity
                if ($stat->n_distinct > 10 && $stat->null_frac < 0.5) {
                    $recommendations[] = [
                        'column' => $stat->attname,
                        'distinct_values' => $stat->n_distinct,
                        'null_percentage' => round($stat->null_frac * 100, 2),
                        'correlation' => round($stat->correlation, 3),
                        'priority' => $this->calculateIndexPriority($stat),
                        'suggested_index' => "CREATE INDEX idx_{$tableName}_{$stat->attname} ON cmis.{$tableName} ({$stat->attname});"
                    ];
                }
            }

            return [
                'success' => true,
                'table' => $tableName,
                'recommendations' => $recommendations,
                'count' => count($recommendations)
            ];

        } catch (\Exception $e) {
            Log::error('Missing indexes analysis failed', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate index priority based on statistics
     *
     * @param object $stat
     * @return string
     */
    protected function calculateIndexPriority(object $stat): string
    {
        $score = 0;

        // High selectivity (many distinct values)
        if ($stat->n_distinct > 1000) {
            $score += 3;
        } elseif ($stat->n_distinct > 100) {
            $score += 2;
        } elseif ($stat->n_distinct > 10) {
            $score += 1;
        }

        // Low null percentage
        if ($stat->null_frac < 0.1) {
            $score += 2;
        } elseif ($stat->null_frac < 0.3) {
            $score += 1;
        }

        // High correlation (good for range queries)
        if (abs($stat->correlation) > 0.7) {
            $score += 1;
        }

        if ($score >= 5) return 'high';
        if ($score >= 3) return 'medium';
        return 'low';
    }

    /**
     * Get database statistics and health metrics
     *
     * @return array
     */
    public function getDatabaseStatistics(): array
    {
        $cacheKey = 'db_statistics';

        return Cache::remember($cacheKey, self::STATS_CACHE_TTL, function () {
            try {
                // Database size
                $dbSize = DB::selectOne("
                    SELECT pg_size_pretty(pg_database_size(current_database())) as size
                ");

                // Table sizes
                $tableSizes = DB::select("
                    SELECT
                        schemaname,
                        tablename,
                        pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as size,
                        pg_total_relation_size(schemaname||'.'||tablename) as size_bytes
                    FROM pg_tables
                    WHERE schemaname LIKE 'cmis%'
                    ORDER BY size_bytes DESC
                    LIMIT 20
                ");

                // Index usage statistics
                $indexStats = DB::select("
                    SELECT
                        schemaname,
                        tablename,
                        indexname,
                        idx_scan as scans,
                        idx_tup_read as tuples_read,
                        idx_tup_fetch as tuples_fetched
                    FROM pg_stat_user_indexes
                    WHERE schemaname LIKE 'cmis%'
                      AND idx_scan > 0
                    ORDER BY idx_scan DESC
                    LIMIT 20
                ");

                // Unused indexes
                $unusedIndexes = DB::select("
                    SELECT
                        schemaname,
                        tablename,
                        indexname,
                        pg_size_pretty(pg_relation_size(indexrelid)) as size
                    FROM pg_stat_user_indexes
                    WHERE schemaname LIKE 'cmis%'
                      AND idx_scan = 0
                      AND indexname NOT LIKE '%_pkey'
                    ORDER BY pg_relation_size(indexrelid) DESC
                ");

                // Cache hit ratio
                $cacheHitRatio = DB::selectOne("
                    SELECT
                        sum(heap_blks_read) as heap_read,
                        sum(heap_blks_hit) as heap_hit,
                        CASE
                            WHEN sum(heap_blks_hit) + sum(heap_blks_read) = 0 THEN 0
                            ELSE round(sum(heap_blks_hit) * 100.0 / (sum(heap_blks_hit) + sum(heap_blks_read)), 2)
                        END as cache_hit_ratio
                    FROM pg_statio_user_tables
                    WHERE schemaname LIKE 'cmis%'
                ");

                // Active connections
                $connections = DB::selectOne("
                    SELECT
                        count(*) as total,
                        count(*) FILTER (WHERE state = 'active') as active,
                        count(*) FILTER (WHERE state = 'idle') as idle
                    FROM pg_stat_activity
                    WHERE datname = current_database()
                ");

                return [
                    'success' => true,
                    'database_size' => $dbSize->size,
                    'table_sizes' => $tableSizes,
                    'index_statistics' => $indexStats,
                    'unused_indexes' => $unusedIndexes,
                    'cache_hit_ratio' => $cacheHitRatio->cache_hit_ratio,
                    'connections' => [
                        'total' => $connections->total,
                        'active' => $connections->active,
                        'idle' => $connections->idle
                    ],
                    'generated_at' => Carbon::now()->toIso8601String()
                ];

            } catch (\Exception $e) {
                Log::error('Database statistics retrieval failed', [
                    'error' => $e->getMessage()
                ]);

                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Optimize table by running VACUUM and ANALYZE
     *
     * @param string $tableName
     * @return array
     */
    public function optimizeTable(string $tableName): array
    {
        try {
            // Validate table name to prevent SQL injection
            if (!preg_match('/^[a-z_][a-z0-9_]*$/', $tableName)) {
                throw new \InvalidArgumentException('Invalid table name');
            }

            $startTime = microtime(true);

            // Run ANALYZE to update statistics
            DB::statement("ANALYZE cmis.{$tableName}");

            // Get table statistics after ANALYZE
            $stats = DB::selectOne("
                SELECT
                    n_live_tup as live_tuples,
                    n_dead_tup as dead_tuples,
                    last_vacuum,
                    last_autovacuum,
                    last_analyze,
                    last_autoanalyze
                FROM pg_stat_user_tables
                WHERE schemaname = 'cmis' AND relname = ?
            ", [$tableName]);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'table' => $tableName,
                'execution_time_ms' => $executionTime,
                'statistics' => [
                    'live_tuples' => $stats->live_tuples ?? 0,
                    'dead_tuples' => $stats->dead_tuples ?? 0,
                    'last_analyze' => $stats->last_analyze,
                    'last_vacuum' => $stats->last_vacuum
                ],
                'recommendation' => $this->getVacuumRecommendation($stats)
            ];

        } catch (\Exception $e) {
            Log::error('Table optimization failed', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get vacuum recommendation based on table statistics
     *
     * @param object|null $stats
     * @return string|null
     */
    protected function getVacuumRecommendation(?object $stats): ?string
    {
        if (!$stats) {
            return null;
        }

        $deadTuples = $stats->dead_tuples ?? 0;
        $liveTuples = $stats->live_tuples ?? 1;
        $deadRatio = ($deadTuples / max($liveTuples, 1)) * 100;

        if ($deadRatio > 20) {
            return "High number of dead tuples ({$deadRatio}%). Consider running VACUUM to reclaim space.";
        }

        if ($deadRatio > 10) {
            return "Moderate number of dead tuples ({$deadRatio}%). VACUUM recommended.";
        }

        return "Table is healthy. No immediate VACUUM needed.";
    }

    /**
     * Enable query logging for performance monitoring
     *
     * @return void
     */
    public function enableQueryLogging(): void
    {
        DB::listen(function ($query) {
            if ($query->time > self::WARNING_QUERY_THRESHOLD) {
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time
                ]);
            }
        });
    }
}
