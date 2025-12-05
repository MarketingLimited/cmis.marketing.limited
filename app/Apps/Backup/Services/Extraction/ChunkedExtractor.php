<?php

namespace App\Apps\Backup\Services\Extraction;

use App\Apps\Backup\Services\Discovery\SchemaDiscoveryService;
use Generator;
use Illuminate\Support\Facades\DB;

/**
 * Chunked Extractor
 *
 * Handles extraction of large datasets (>100k records) with
 * memory-efficient streaming and chunking strategies.
 */
class ChunkedExtractor
{
    protected SchemaDiscoveryService $schemaDiscovery;

    /**
     * Default chunk size
     */
    protected int $chunkSize;

    /**
     * Memory usage threshold (percentage)
     */
    protected int $memoryThreshold;

    /**
     * Maximum rows before switching to streaming
     */
    protected int $streamingThreshold;

    public function __construct(SchemaDiscoveryService $schemaDiscovery)
    {
        $this->schemaDiscovery = $schemaDiscovery;
        $this->chunkSize = config('backup.extraction.chunk_size', 1000);
        $this->memoryThreshold = 80; // 80% of memory limit
        $this->streamingThreshold = 10000;
    }

    /**
     * Extract data using the appropriate strategy based on table size
     *
     * @param string $orgId Organization ID
     * @param string $tableName Fully qualified table name
     * @param callable|null $rowProcessor Optional row processor
     * @return Generator|array
     */
    public function extract(
        string $orgId,
        string $tableName,
        ?callable $rowProcessor = null
    ) {
        $rowCount = $this->schemaDiscovery->getTableRowCount($tableName, $orgId);

        // Choose extraction strategy based on table size
        if ($rowCount > $this->streamingThreshold) {
            return $this->streamExtract($orgId, $tableName, $rowProcessor);
        }

        if ($rowCount > $this->chunkSize) {
            return $this->chunkedExtract($orgId, $tableName, $rowProcessor);
        }

        return $this->simpleExtract($orgId, $tableName, $rowProcessor);
    }

    /**
     * Simple extraction for small tables
     *
     * @param string $orgId Organization ID
     * @param string $tableName Table name
     * @param callable|null $rowProcessor Row processor
     * @return array
     */
    protected function simpleExtract(
        string $orgId,
        string $tableName,
        ?callable $rowProcessor = null
    ): array {
        $this->setOrgContext($orgId);

        $query = $this->buildBaseQuery($tableName);
        $rows = $query->get();

        if ($rowProcessor) {
            return $rows->map($rowProcessor)->toArray();
        }

        return $rows->map(fn($row) => (array) $row)->toArray();
    }

    /**
     * Chunked extraction for medium tables
     *
     * @param string $orgId Organization ID
     * @param string $tableName Table name
     * @param callable|null $rowProcessor Row processor
     * @return array
     */
    protected function chunkedExtract(
        string $orgId,
        string $tableName,
        ?callable $rowProcessor = null
    ): array {
        $this->setOrgContext($orgId);

        $data = [];
        $query = $this->buildBaseQuery($tableName);

        $query->chunkById($this->chunkSize, function ($rows) use (&$data, $rowProcessor) {
            foreach ($rows as $row) {
                $processed = $rowProcessor ? $rowProcessor($row) : (array) $row;
                $data[] = $processed;
            }

            // Check memory after each chunk
            $this->checkMemory();
        });

        return $data;
    }

    /**
     * Streaming extraction for very large tables
     *
     * @param string $orgId Organization ID
     * @param string $tableName Table name
     * @param callable|null $rowProcessor Row processor
     * @return Generator
     */
    protected function streamExtract(
        string $orgId,
        string $tableName,
        ?callable $rowProcessor = null
    ): Generator {
        $this->setOrgContext($orgId);

        $query = $this->buildBaseQuery($tableName);

        foreach ($query->cursor() as $row) {
            $processed = $rowProcessor ? $rowProcessor($row) : (array) $row;
            yield $processed;

            // Periodic memory check
            static $counter = 0;
            if (++$counter % 1000 === 0) {
                $this->checkMemory();
            }
        }
    }

    /**
     * Stream extract to a file (for very large tables)
     *
     * @param string $orgId Organization ID
     * @param string $tableName Table name
     * @param string $filePath Output file path
     * @param callable|null $rowProcessor Row processor
     * @return int Number of rows written
     */
    public function streamToFile(
        string $orgId,
        string $tableName,
        string $filePath,
        ?callable $rowProcessor = null
    ): int {
        $this->setOrgContext($orgId);

        $handle = fopen($filePath, 'w');
        if (!$handle) {
            throw new \RuntimeException("Cannot open file for writing: {$filePath}");
        }

        // Write JSON array opening
        fwrite($handle, "[\n");

        $query = $this->buildBaseQuery($tableName);
        $count = 0;
        $first = true;

        foreach ($query->cursor() as $row) {
            $processed = $rowProcessor ? $rowProcessor($row) : (array) $row;

            // Add comma for all but first row
            if (!$first) {
                fwrite($handle, ",\n");
            }
            $first = false;

            fwrite($handle, json_encode($processed, JSON_UNESCAPED_UNICODE));
            $count++;

            // Periodic memory check
            if ($count % 1000 === 0) {
                $this->checkMemory();
            }
        }

        // Write JSON array closing
        fwrite($handle, "\n]");
        fclose($handle);

        return $count;
    }

    /**
     * Extract data in batches with callback
     *
     * @param string $orgId Organization ID
     * @param string $tableName Table name
     * @param callable $batchCallback Called with each batch
     * @param int|null $batchSize Optional batch size
     * @return int Total rows processed
     */
    public function extractBatches(
        string $orgId,
        string $tableName,
        callable $batchCallback,
        ?int $batchSize = null
    ): int {
        $this->setOrgContext($orgId);

        $batchSize = $batchSize ?? $this->chunkSize;
        $totalProcessed = 0;

        $query = $this->buildBaseQuery($tableName);

        $query->chunkById($batchSize, function ($rows) use ($batchCallback, &$totalProcessed) {
            $batch = $rows->map(fn($row) => (array) $row)->toArray();
            $batchCallback($batch, $totalProcessed);
            $totalProcessed += count($batch);
            $this->checkMemory();
        });

        return $totalProcessed;
    }

    /**
     * Set RLS context for organization
     *
     * @param string $orgId Organization ID
     */
    protected function setOrgContext(string $orgId): void
    {
        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            config('cmis.system_user_id'),
            $orgId
        ]);
    }

    /**
     * Build base query for a table
     *
     * @param string $tableName Fully qualified table name
     * @return \Illuminate\Database\Query\Builder
     */
    protected function buildBaseQuery(string $tableName)
    {
        $query = DB::table($tableName);

        // Exclude soft-deleted records
        if ($this->schemaDiscovery->hasSoftDeletes($tableName)) {
            $query->whereNull('deleted_at');
        }

        // Order by created_at for consistent ordering
        if ($this->schemaDiscovery->hasTimestamps($tableName)) {
            $query->orderBy('created_at');
        }

        return $query;
    }

    /**
     * Check memory usage and handle if near limit
     */
    protected function checkMemory(): void
    {
        $limit = $this->getMemoryLimit();
        $used = memory_get_usage(true);
        $percentage = ($used / $limit) * 100;

        if ($percentage > $this->memoryThreshold) {
            // Try garbage collection
            gc_collect_cycles();

            // Check again
            $used = memory_get_usage(true);
            $percentage = ($used / $limit) * 100;

            if ($percentage > 95) {
                throw new \RuntimeException(
                    "Memory limit approaching during extraction. " .
                    "Used: " . round($used / 1024 / 1024, 2) . "MB"
                );
            }
        }
    }

    /**
     * Get memory limit in bytes
     *
     * @return int
     */
    protected function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');

        if ($limit === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * Get current memory usage info
     *
     * @return array
     */
    public function getMemoryInfo(): array
    {
        $limit = $this->getMemoryLimit();
        $used = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);

        return [
            'limit_bytes' => $limit,
            'used_bytes' => $used,
            'peak_bytes' => $peak,
            'usage_percent' => round(($used / $limit) * 100, 2),
            'available_bytes' => $limit - $used,
        ];
    }

    /**
     * Set chunk size
     *
     * @param int $size Chunk size
     * @return self
     */
    public function setChunkSize(int $size): self
    {
        $this->chunkSize = $size;
        return $this;
    }

    /**
     * Set streaming threshold
     *
     * @param int $threshold Row count threshold
     * @return self
     */
    public function setStreamingThreshold(int $threshold): self
    {
        $this->streamingThreshold = $threshold;
        return $this;
    }
}
