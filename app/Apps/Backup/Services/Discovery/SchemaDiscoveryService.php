<?php

namespace App\Apps\Backup\Services\Discovery;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Schema Discovery Service
 *
 * Discovers all database tables with org_id column across all schemas.
 * Uses information_schema for schema-agnostic, dynamic table discovery.
 * This enables backup/restore to work automatically with any new tables.
 */
class SchemaDiscoveryService
{
    /**
     * Cache TTL for schema discovery (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Schemas to scan for org-scoped tables
     */
    protected array $schemas;

    /**
     * Tables to always exclude from backups
     */
    protected array $excludedTables;

    /**
     * Category patterns for auto-categorization
     */
    protected array $categoryPatterns;

    public function __construct()
    {
        $this->schemas = config('backup.discovery.schemas', [
            'cmis',
            'cmis_ai',
            'cmis_analytics',
            'cmis_creative',
            'cmis_platform',
            'cmis_google',
            'cmis_meta',
            'cmis_tiktok',
            'cmis_linkedin',
            'cmis_twitter',
            'cmis_snapchat',
        ]);

        $this->excludedTables = config('backup.discovery.excluded_tables', []);
        $this->categoryPatterns = config('backup.discovery.category_patterns', []);
    }

    /**
     * Discover all tables with org_id column across all schemas
     *
     * @return Collection<string> Collection of fully qualified table names (schema.table)
     */
    public function discoverOrgTables(): Collection
    {
        $cacheKey = 'backup:org_tables:' . md5(implode(',', $this->schemas));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return DB::table('information_schema.columns')
                ->select('table_schema', 'table_name')
                ->where('column_name', 'org_id')
                ->whereIn('table_schema', $this->schemas)
                ->get()
                ->map(fn($row) => "{$row->table_schema}.{$row->table_name}")
                ->filter(fn($table) => !in_array($table, $this->excludedTables))
                ->values();
        });
    }

    /**
     * Get column definitions for a table
     * Used to create schema snapshots for restore compatibility checking
     *
     * @param string $tableName Fully qualified table name (schema.table)
     * @return array Column definitions
     */
    public function getTableSchema(string $tableName): array
    {
        [$schema, $table] = $this->parseTableName($tableName);

        $columns = DB::table('information_schema.columns')
            ->where('table_schema', $schema)
            ->where('table_name', $table)
            ->select([
                'column_name',
                'data_type',
                'udt_name',
                'is_nullable',
                'column_default',
                'character_maximum_length',
                'numeric_precision',
                'numeric_scale',
            ])
            ->orderBy('ordinal_position')
            ->get()
            ->toArray();

        return [
            'schema' => $schema,
            'table' => $table,
            'full_name' => $tableName,
            'columns' => array_map(fn($col) => (array) $col, $columns),
            'column_count' => count($columns),
        ];
    }

    /**
     * Get all table schemas for backup snapshot
     *
     * @param Collection|array $tables List of table names
     * @return array Schema snapshot for all tables
     */
    public function getSchemaSnapshot($tables): array
    {
        $snapshot = [
            'version' => '1.0',
            'created_at' => now()->toISOString(),
            'database' => config('database.connections.pgsql.database'),
            'tables' => [],
        ];

        foreach ($tables as $tableName) {
            $snapshot['tables'][$tableName] = $this->getTableSchema($tableName);
        }

        return $snapshot;
    }

    /**
     * Group tables into user-friendly categories
     *
     * @param string $tableName Fully qualified table name
     * @return string Category name
     */
    public function categorizeTable(string $tableName): string
    {
        // First check configured category mapping
        $configuredMapping = config('backup.category_mapping', []);

        foreach ($configuredMapping as $category => $tables) {
            if (in_array($tableName, $tables)) {
                return $category;
            }
        }

        // Auto-categorize by pattern matching
        [$schema, $table] = $this->parseTableName($tableName);
        $lowerTable = strtolower($table);

        foreach ($this->categoryPatterns as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($lowerTable, strtolower($pattern))) {
                    return $category;
                }
            }
        }

        // Default categorization by schema
        return match ($schema) {
            'cmis_meta', 'cmis_google', 'cmis_tiktok',
            'cmis_linkedin', 'cmis_twitter', 'cmis_snapchat' => 'integrations',
            'cmis_ai' => 'ai_data',
            'cmis_analytics' => 'analytics',
            'cmis_creative' => 'creative',
            'cmis_platform' => 'integrations',
            default => 'other',
        };
    }

    /**
     * Discover all tables and group by category
     *
     * @return Collection Categories with their tables
     */
    public function discoverByCategory(): Collection
    {
        $tables = $this->discoverOrgTables();

        return $tables
            ->groupBy(fn($table) => $this->categorizeTable($table))
            ->map(function ($tables, $category) {
                return [
                    'category' => $category,
                    'label' => config("backup.category_labels.{$category}", 'backup.categories.other'),
                    'tables' => $tables->values()->toArray(),
                    'table_count' => $tables->count(),
                ];
            });
    }

    /**
     * Get row count for a table (for progress estimation)
     *
     * @param string $tableName Fully qualified table name
     * @param string $orgId Organization ID
     * @return int Estimated row count
     */
    public function getTableRowCount(string $tableName, string $orgId): int
    {
        try {
            // Set RLS context
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                config('cmis.system_user_id'),
                $orgId
            ]);

            return DB::table($tableName)->count();
        } catch (\Exception $e) {
            // Table might not exist or RLS context issue
            return 0;
        }
    }

    /**
     * Get summary statistics for an organization's data
     *
     * @param string $orgId Organization ID
     * @return array Summary by category
     */
    public function getOrgDataSummary(string $orgId): array
    {
        $categories = $this->discoverByCategory();
        $summary = [];

        foreach ($categories as $categoryKey => $categoryData) {
            $totalRecords = 0;

            foreach ($categoryData['tables'] as $table) {
                $totalRecords += $this->getTableRowCount($table, $orgId);
            }

            $summary[$categoryKey] = [
                'category' => $categoryKey,
                'label' => $categoryData['label'],
                'table_count' => $categoryData['table_count'],
                'record_count' => $totalRecords,
            ];
        }

        return $summary;
    }

    /**
     * Get primary key column(s) for a table
     *
     * @param string $tableName Fully qualified table name
     * @return array Primary key column(s)
     */
    public function getPrimaryKey(string $tableName): array
    {
        [$schema, $table] = $this->parseTableName($tableName);

        $result = DB::select("
            SELECT a.attname as column_name
            FROM pg_index i
            JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
            WHERE i.indrelid = ?::regclass
            AND i.indisprimary
        ", ["{$schema}.{$table}"]);

        return array_map(fn($row) => $row->column_name, $result);
    }

    /**
     * Check if a table has soft deletes
     *
     * @param string $tableName Fully qualified table name
     * @return bool
     */
    public function hasSoftDeletes(string $tableName): bool
    {
        [$schema, $table] = $this->parseTableName($tableName);

        return DB::table('information_schema.columns')
            ->where('table_schema', $schema)
            ->where('table_name', $table)
            ->where('column_name', 'deleted_at')
            ->exists();
    }

    /**
     * Check if a table has timestamps
     *
     * @param string $tableName Fully qualified table name
     * @return bool
     */
    public function hasTimestamps(string $tableName): bool
    {
        [$schema, $table] = $this->parseTableName($tableName);

        $hasCreatedAt = DB::table('information_schema.columns')
            ->where('table_schema', $schema)
            ->where('table_name', $table)
            ->where('column_name', 'created_at')
            ->exists();

        $hasUpdatedAt = DB::table('information_schema.columns')
            ->where('table_schema', $schema)
            ->where('table_name', $table)
            ->where('column_name', 'updated_at')
            ->exists();

        return $hasCreatedAt && $hasUpdatedAt;
    }

    /**
     * Clear discovery cache
     */
    public function clearCache(): void
    {
        $cacheKey = 'backup:org_tables:' . md5(implode(',', $this->schemas));
        Cache::forget($cacheKey);
    }

    /**
     * Parse a fully qualified table name into schema and table
     *
     * @param string $tableName Format: schema.table
     * @return array [schema, table]
     */
    protected function parseTableName(string $tableName): array
    {
        $parts = explode('.', $tableName);

        if (count($parts) !== 2) {
            throw new \InvalidArgumentException(
                "Invalid table name format: {$tableName}. Expected format: schema.table"
            );
        }

        return $parts;
    }
}
