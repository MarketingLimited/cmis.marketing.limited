<?php

namespace App\Apps\Backup\Services\Extraction;

use App\Apps\Backup\Services\Discovery\SchemaDiscoveryService;
use App\Apps\Backup\Services\Discovery\DependencyResolver;
use App\Apps\Backup\Services\Export\ExportMapperService;
use Generator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Data Extractor Service
 *
 * Extracts organization data from database using RLS context.
 * Supports chunked extraction for large tables and respects
 * foreign key dependencies for proper ordering.
 */
class DataExtractorService
{
    protected SchemaDiscoveryService $schemaDiscovery;
    protected DependencyResolver $dependencyResolver;
    protected ExportMapperService $exportMapper;

    /**
     * Chunk size for extraction
     */
    protected int $chunkSize;

    /**
     * Memory limit for extraction (MB)
     */
    protected int $memoryLimit;

    public function __construct(
        SchemaDiscoveryService $schemaDiscovery,
        DependencyResolver $dependencyResolver,
        ExportMapperService $exportMapper
    ) {
        $this->schemaDiscovery = $schemaDiscovery;
        $this->dependencyResolver = $dependencyResolver;
        $this->exportMapper = $exportMapper;
        $this->chunkSize = config('backup.extraction.chunk_size', 1000);
        $this->memoryLimit = config('backup.extraction.memory_limit', 512);
    }

    /**
     * Initialize RLS context for an organization
     *
     * @param string $orgId Organization ID
     */
    public function setOrgContext(string $orgId): void
    {
        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            config('cmis.system_user_id'),
            $orgId
        ]);
    }

    /**
     * Extract all data for an organization
     *
     * @param string $orgId Organization ID
     * @param array|null $categories Optional: limit to specific categories
     * @param callable|null $progressCallback Progress callback (table, count)
     * @return array Extracted data by category
     */
    public function extractAllData(
        string $orgId,
        ?array $categories = null,
        ?callable $progressCallback = null
    ): array {
        $this->setOrgContext($orgId);

        // Discover tables by category
        $tablesByCategory = $this->schemaDiscovery->discoverByCategory();

        // Filter categories if specified
        if ($categories !== null) {
            $tablesByCategory = $tablesByCategory->only($categories);
        }

        $extractedData = [];

        foreach ($tablesByCategory as $categoryKey => $categoryInfo) {
            $tables = $categoryInfo['tables'];

            // Order tables by dependencies
            $orderedTables = $this->dependencyResolver->resolveExtractionOrder($tables);

            $categoryData = [];

            foreach ($orderedTables as $table) {
                $tableData = $this->extractTableData($orgId, $table);

                if (!empty($tableData)) {
                    $friendlyName = $this->exportMapper->getTableFriendlyName($table);
                    $categoryData[$friendlyName] = $tableData;

                    if ($progressCallback) {
                        $progressCallback($table, count($tableData));
                    }
                }
            }

            if (!empty($categoryData)) {
                $extractedData[$categoryKey] = [
                    'label' => $categoryInfo['label'],
                    'data' => $categoryData,
                    'table_count' => count($categoryData),
                    'record_count' => array_sum(array_map('count', $categoryData)),
                ];
            }
        }

        return $extractedData;
    }

    /**
     * Extract data from a single table
     *
     * @param string $orgId Organization ID
     * @param string $tableName Fully qualified table name
     * @return array Table data
     */
    public function extractTableData(string $orgId, string $tableName): array
    {
        $this->setOrgContext($orgId);

        try {
            // Get row count first
            $count = DB::table($tableName)->count();

            if ($count === 0) {
                return [];
            }

            // Use cursor for memory efficiency on large tables
            if ($count > $this->chunkSize) {
                return $this->extractLargeTable($tableName);
            }

            // Small table - fetch all at once
            return DB::table($tableName)
                ->whereNull('deleted_at')
                ->orderBy('created_at')
                ->get()
                ->map(fn($row) => $this->processRow($tableName, $row))
                ->toArray();

        } catch (\Exception $e) {
            // Log error but continue with other tables
            \Log::warning("Failed to extract table {$tableName}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Extract data from a large table using cursor
     *
     * @param string $tableName Fully qualified table name
     * @return array Table data
     */
    protected function extractLargeTable(string $tableName): array
    {
        $data = [];
        $hasSoftDeletes = $this->schemaDiscovery->hasSoftDeletes($tableName);

        $query = DB::table($tableName);

        if ($hasSoftDeletes) {
            $query->whereNull('deleted_at');
        }

        if ($this->schemaDiscovery->hasTimestamps($tableName)) {
            $query->orderBy('created_at');
        }

        foreach ($query->cursor() as $row) {
            $data[] = $this->processRow($tableName, $row);

            // Check memory usage
            if (count($data) % $this->chunkSize === 0) {
                $this->checkMemoryUsage();
            }
        }

        return $data;
    }

    /**
     * Extract table data as a generator (for streaming)
     *
     * @param string $orgId Organization ID
     * @param string $tableName Fully qualified table name
     * @return Generator
     */
    public function extractTableDataStream(string $orgId, string $tableName): Generator
    {
        $this->setOrgContext($orgId);

        $hasSoftDeletes = $this->schemaDiscovery->hasSoftDeletes($tableName);

        $query = DB::table($tableName);

        if ($hasSoftDeletes) {
            $query->whereNull('deleted_at');
        }

        if ($this->schemaDiscovery->hasTimestamps($tableName)) {
            $query->orderBy('created_at');
        }

        foreach ($query->cursor() as $row) {
            yield $this->processRow($tableName, $row);
        }
    }

    /**
     * Process a single row for export
     *
     * @param string $tableName Table name
     * @param object $row Database row
     * @return array Processed row
     */
    protected function processRow(string $tableName, object $row): array
    {
        $row = (array) $row;

        // Map column names to friendly names
        $processed = [];
        foreach ($row as $column => $value) {
            $friendlyColumn = $this->exportMapper->getColumnFriendlyName($tableName, $column);
            $processed[$friendlyColumn] = $this->processValue($value);
        }

        // Add metadata
        $processed['_source_table'] = $tableName;
        $processed['_exported_at'] = now()->toISOString();

        return $processed;
    }

    /**
     * Process a value for export (handle special types)
     *
     * @param mixed $value Raw value
     * @return mixed Processed value
     */
    protected function processValue($value)
    {
        // Handle JSON strings
        if (is_string($value) && $this->isJson($value)) {
            return json_decode($value, true);
        }

        // Handle DateTime objects
        if ($value instanceof \DateTime) {
            return $value->format('c');
        }

        return $value;
    }

    /**
     * Check if a string is valid JSON
     *
     * @param string $string
     * @return bool
     */
    protected function isJson(string $string): bool
    {
        if (empty($string) || !in_array($string[0], ['{', '['])) {
            return false;
        }

        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Check memory usage and throw if exceeded
     */
    protected function checkMemoryUsage(): void
    {
        $used = memory_get_usage(true) / 1024 / 1024; // MB

        if ($used > $this->memoryLimit) {
            throw new \RuntimeException(
                "Memory limit exceeded during extraction. " .
                "Used: {$used}MB, Limit: {$this->memoryLimit}MB"
            );
        }
    }

    /**
     * Get extraction statistics for an organization
     *
     * @param string $orgId Organization ID
     * @return array Statistics by category
     */
    public function getExtractionStats(string $orgId): array
    {
        return $this->schemaDiscovery->getOrgDataSummary($orgId);
    }

    /**
     * Export extracted data to JSON format
     *
     * @param array $data Extracted data
     * @param bool $pretty Use pretty formatting
     * @return string JSON string
     */
    public function toJson(array $data, bool $pretty = false): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($data, $flags);
    }

    /**
     * Export a single category to JSON
     *
     * @param string $categoryKey Category key
     * @param array $categoryData Category data
     * @return string JSON string
     */
    public function categoryToJson(string $categoryKey, array $categoryData): string
    {
        return $this->toJson([
            'category' => $categoryKey,
            'label' => $categoryData['label'] ?? null,
            'exported_at' => now()->toISOString(),
            'data' => $categoryData['data'] ?? $categoryData,
        ]);
    }

    /**
     * Estimate backup size for an organization (in bytes)
     *
     * @param string $orgId Organization ID
     * @return int Estimated size in bytes
     */
    public function estimateBackupSize(string $orgId): int
    {
        $stats = $this->getExtractionStats($orgId);
        $totalRecords = array_sum(array_column($stats, 'record_count'));

        // Rough estimate: 1KB per record average
        return $totalRecords * 1024;
    }
}
