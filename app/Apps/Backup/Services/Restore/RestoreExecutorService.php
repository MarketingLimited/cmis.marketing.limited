<?php

namespace App\Apps\Backup\Services\Restore;

use App\Apps\Backup\Services\Discovery\DependencyResolver;
use App\Apps\Backup\Services\Export\ExportMapperService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Restore Executor Service
 *
 * Executes the actual data restoration within a transaction,
 * respecting RLS policies and handling conflicts.
 */
class RestoreExecutorService
{
    protected DependencyResolver $dependencyResolver;
    protected ConflictResolverService $conflictResolver;
    protected ExportMapperService $mapper;

    public function __construct(
        DependencyResolver $dependencyResolver,
        ConflictResolverService $conflictResolver,
        ExportMapperService $mapper
    ) {
        $this->dependencyResolver = $dependencyResolver;
        $this->conflictResolver = $conflictResolver;
        $this->mapper = $mapper;
    }

    /**
     * Execute restore operation
     */
    public function execute(
        string $orgId,
        string $extractedPath,
        array $manifest,
        array $categories,
        array $conflictResolution,
        string $restoreType
    ): array {
        $report = [
            'started_at' => now()->toIso8601String(),
            'categories_processed' => 0,
            'records_restored' => 0,
            'records_skipped' => 0,
            'records_updated' => 0,
            'files_restored' => 0,
            'errors' => [],
            'warnings' => [],
            'by_category' => [],
        ];

        // Start transaction
        DB::beginTransaction();

        try {
            // Set RLS context
            DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
                config('cmis.system_user_id'),
                $orgId
            ]);

            // For full restore, clear existing data first
            if ($restoreType === 'full') {
                $this->clearExistingData($orgId, $categories, $manifest);
            }

            // Get dependency order for tables
            $tableOrder = $this->getRestoreOrder($categories, $manifest);

            // Process each category in dependency order
            foreach ($tableOrder as $category) {
                if (!in_array($category, $categories)) {
                    continue;
                }

                $categoryReport = $this->restoreCategory(
                    $orgId,
                    $extractedPath,
                    $category,
                    $manifest,
                    $conflictResolution,
                    $restoreType
                );

                $report['by_category'][$category] = $categoryReport;
                $report['records_restored'] += $categoryReport['inserted'];
                $report['records_updated'] += $categoryReport['updated'];
                $report['records_skipped'] += $categoryReport['skipped'];
                $report['categories_processed']++;

                if (!empty($categoryReport['errors'])) {
                    $report['errors'] = array_merge($report['errors'], $categoryReport['errors']);
                }
            }

            // Restore files
            $filesReport = $this->restoreFiles($orgId, $extractedPath, $manifest);
            $report['files_restored'] = $filesReport['restored'];
            if (!empty($filesReport['errors'])) {
                $report['errors'] = array_merge($report['errors'], $filesReport['errors']);
            }

            // Verify referential integrity
            $integrityCheck = $this->verifyIntegrity($orgId);
            if (!$integrityCheck['success']) {
                $report['warnings'][] = 'Some referential integrity issues detected';
                $report['integrity_issues'] = $integrityCheck['issues'];
            }

            // Commit transaction
            DB::commit();

            $report['completed_at'] = now()->toIso8601String();
            $report['success'] = empty($report['errors']);

            return $report;
        } catch (\Exception $e) {
            DB::rollBack();

            $report['completed_at'] = now()->toIso8601String();
            $report['success'] = false;
            $report['errors'][] = [
                'type' => 'fatal',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];

            throw $e;
        }
    }

    /**
     * Clear existing data for full restore
     */
    protected function clearExistingData(string $orgId, array $categories, array $manifest): void
    {
        // Get tables in reverse dependency order (children first)
        $tableOrder = $this->getRestoreOrder($categories, $manifest);
        $tableOrder = array_reverse($tableOrder);

        foreach ($tableOrder as $category) {
            $tableName = $this->mapper->getTableInternalName($category);

            if (!$tableName) {
                continue;
            }

            try {
                // Use soft delete if available, otherwise truncate
                $hasDeletedAt = DB::select("
                    SELECT column_name FROM information_schema.columns
                    WHERE table_schema = ?
                    AND table_name = ?
                    AND column_name = 'deleted_at'
                ", [
                    explode('.', $tableName)[0],
                    explode('.', $tableName)[1]
                ]);

                if (!empty($hasDeletedAt)) {
                    // Soft delete all records for this org
                    DB::table($tableName)
                        ->where('org_id', $orgId)
                        ->whereNull('deleted_at')
                        ->update(['deleted_at' => now()]);
                }
                // We don't hard delete to maintain audit trail
            } catch (\Exception $e) {
                // Log but continue - table might not have org_id
                \Log::warning("Could not clear {$tableName}: " . $e->getMessage());
            }
        }
    }

    /**
     * Get restore order based on dependencies
     */
    protected function getRestoreOrder(array $categories, array $manifest): array
    {
        $tables = [];

        foreach ($categories as $category) {
            $tableName = $this->mapper->getTableInternalName($category);
            if ($tableName) {
                $tables[] = $tableName;
            }
        }

        // Sort by dependencies (parents first)
        $ordered = $this->dependencyResolver->resolveDependencyOrder($tables);

        // Map back to category names
        $orderedCategories = [];
        foreach ($ordered as $tableName) {
            $category = $this->mapper->getTableFriendlyName($tableName);
            if ($category && in_array($category, $categories)) {
                $orderedCategories[] = $category;
            }
        }

        return $orderedCategories;
    }

    /**
     * Restore a single category
     */
    protected function restoreCategory(
        string $orgId,
        string $extractedPath,
        string $category,
        array $manifest,
        array $conflictResolution,
        string $restoreType
    ): array {
        $report = [
            'category' => $category,
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $dataPath = $extractedPath . '/data/' . $category . '.json';

        if (!file_exists($dataPath)) {
            $report['errors'][] = "Data file not found for {$category}";
            return $report;
        }

        $data = json_decode(file_get_contents($dataPath), true);

        if (!is_array($data)) {
            $report['errors'][] = "Invalid data format for {$category}";
            return $report;
        }

        $tableName = $this->mapper->getTableInternalName($category);

        if (!$tableName) {
            $report['errors'][] = "Unknown table for category {$category}";
            return $report;
        }

        $strategy = $conflictResolution['strategy'] ?? 'skip';
        $decisions = $conflictResolution['decisions'] ?? [];

        foreach ($data as $record) {
            try {
                $result = $this->restoreRecord(
                    $orgId,
                    $tableName,
                    $record,
                    $strategy,
                    $decisions,
                    $restoreType
                );

                switch ($result['action']) {
                    case 'insert':
                        $report['inserted']++;
                        break;
                    case 'update':
                        $report['updated']++;
                        break;
                    case 'skip':
                        $report['skipped']++;
                        break;
                }
            } catch (\Exception $e) {
                $report['errors'][] = [
                    'record_id' => $record['id'] ?? 'unknown',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $report;
    }

    /**
     * Restore a single record
     */
    protected function restoreRecord(
        string $orgId,
        string $tableName,
        array $record,
        string $strategy,
        array $decisions,
        string $restoreType
    ): array {
        $recordId = $record['id'] ?? null;

        if (!$recordId) {
            throw new \Exception('Record missing ID');
        }

        // Ensure org_id matches
        $record['org_id'] = $orgId;

        // Check for existing record
        $existing = DB::table($tableName)->where('id', $recordId)->first();
        $existingArray = $existing ? (array) $existing : null;

        // Check for specific decision for this record
        if (isset($decisions[$recordId])) {
            $resolved = $this->conflictResolver->applyUserDecisions([
                $recordId => array_merge($decisions[$recordId], [
                    'backup_data' => $record,
                    'existing_data' => $existingArray,
                ])
            ])[$recordId];
        } else {
            // Use default strategy
            $resolved = $this->conflictResolver->resolve($record, $existingArray, $strategy);
        }

        // Execute based on resolution
        if ($resolved->isSkipped()) {
            return ['action' => 'skip'];
        }

        if ($resolved->isPending()) {
            // Shouldn't happen if decisions are properly provided
            return ['action' => 'skip'];
        }

        $data = $resolved->getData();

        if (!$data) {
            return ['action' => 'skip'];
        }

        // Map friendly names back to internal names
        $data = $this->mapper->mapRecordToInternal($tableName, $data);

        // Clean up data for insert/update
        $data = $this->prepareRecordForDatabase($data, $tableName);

        if ($resolved->isInsert()) {
            DB::table($tableName)->insert($data);
            return ['action' => 'insert', 'id' => $recordId];
        }

        if ($resolved->isUpdate()) {
            unset($data['id']); // Don't update primary key
            DB::table($tableName)->where('id', $recordId)->update($data);
            return ['action' => 'update', 'id' => $recordId];
        }

        return ['action' => 'skip'];
    }

    /**
     * Prepare record for database insert/update
     */
    protected function prepareRecordForDatabase(array $record, string $tableName): array
    {
        // Get column info for the table
        $schema = explode('.', $tableName)[0];
        $table = explode('.', $tableName)[1];

        $columns = DB::select("
            SELECT column_name, data_type, is_nullable
            FROM information_schema.columns
            WHERE table_schema = ? AND table_name = ?
        ", [$schema, $table]);

        $columnNames = collect($columns)->pluck('column_name')->toArray();

        // Filter to only valid columns
        $record = array_filter($record, function ($key) use ($columnNames) {
            return in_array($key, $columnNames);
        }, ARRAY_FILTER_USE_KEY);

        // Handle JSON columns
        foreach ($columns as $col) {
            if (in_array($col->data_type, ['json', 'jsonb'])) {
                if (isset($record[$col->column_name]) && is_array($record[$col->column_name])) {
                    $record[$col->column_name] = json_encode($record[$col->column_name]);
                }
            }
        }

        return $record;
    }

    /**
     * Restore files from backup
     */
    protected function restoreFiles(string $orgId, string $extractedPath, array $manifest): array
    {
        $report = [
            'restored' => 0,
            'errors' => [],
        ];

        $filesPath = $extractedPath . '/files';

        if (!is_dir($filesPath)) {
            return $report;
        }

        $fileManifest = $manifest['files'] ?? [];
        $disk = Storage::disk('public');

        foreach ($fileManifest as $file) {
            $sourcePath = $filesPath . '/' . $file['relative_path'];
            $targetPath = $file['original_path'];

            if (!file_exists($sourcePath)) {
                $report['errors'][] = "File not found in backup: {$file['relative_path']}";
                continue;
            }

            try {
                // Ensure directory exists
                $dir = dirname($targetPath);
                if (!$disk->exists($dir)) {
                    $disk->makeDirectory($dir);
                }

                // Copy file
                $disk->put($targetPath, file_get_contents($sourcePath));
                $report['restored']++;
            } catch (\Exception $e) {
                $report['errors'][] = "Failed to restore file {$targetPath}: " . $e->getMessage();
            }
        }

        return $report;
    }

    /**
     * Verify referential integrity after restore
     */
    protected function verifyIntegrity(string $orgId): array
    {
        $issues = [];

        // This is a simplified check - in production you'd check actual FK constraints
        // For now, we'll rely on the database's FK constraints to catch issues

        return [
            'success' => empty($issues),
            'issues' => $issues,
        ];
    }
}
