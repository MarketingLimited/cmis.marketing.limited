<?php

namespace App\Apps\Backup\Services\Restore;

use App\Apps\Backup\Services\Discovery\SchemaDiscoveryService;
use Illuminate\Support\Collection;

/**
 * Schema Reconciler Service
 *
 * Compares backup schema snapshot with current database schema
 * to identify compatibility issues before restore.
 */
class SchemaReconcilerService
{
    protected SchemaDiscoveryService $discovery;

    public function __construct(SchemaDiscoveryService $discovery)
    {
        $this->discovery = $discovery;
    }

    /**
     * Reconcile backup schema with current schema
     */
    public function reconcile(array $backupSchema): ReconciliationReport
    {
        $currentSchema = $this->getCurrentSchema();
        $report = new ReconciliationReport();

        foreach ($backupSchema as $category => $tables) {
            foreach ($tables as $tableName => $tableDef) {
                $currentTable = $currentSchema[$tableName] ?? null;

                if (!$currentTable) {
                    // Table no longer exists in current schema
                    $report->addIncompatible(
                        $category,
                        $tableName,
                        'table_missing',
                        __('backup.reconcile_table_missing', ['table' => $tableName])
                    );
                    continue;
                }

                // Compare columns
                $columnDiff = $this->compareColumns(
                    $tableDef['columns'] ?? [],
                    $currentTable['columns']
                );

                if ($columnDiff->hasBreakingChanges()) {
                    // Breaking changes detected
                    $report->addPartiallyCompatible(
                        $category,
                        $tableName,
                        $columnDiff
                    );
                } elseif ($columnDiff->hasWarnings()) {
                    // Non-breaking but needs attention
                    $report->addCompatibleWithWarnings(
                        $category,
                        $tableName,
                        $columnDiff
                    );
                } else {
                    // Fully compatible
                    $report->addCompatible($category, $tableName);
                }
            }
        }

        return $report;
    }

    /**
     * Get current database schema
     */
    protected function getCurrentSchema(): array
    {
        $schema = [];
        $tables = $this->discovery->discoverOrgTables();

        foreach ($tables as $tableName) {
            $columns = $this->discovery->getTableSchema($tableName);
            $schema[$tableName] = [
                'name' => $tableName,
                'columns' => $columns,
            ];
        }

        return $schema;
    }

    /**
     * Compare columns between backup and current schema
     */
    protected function compareColumns(array $backupColumns, array $currentColumns): ColumnDiff
    {
        $diff = new ColumnDiff();

        // Index current columns by name
        $currentByName = collect($currentColumns)->keyBy('column_name');

        foreach ($backupColumns as $backupCol) {
            $colName = $backupCol['column_name'];
            $currentCol = $currentByName->get($colName);

            if (!$currentCol) {
                // Column removed in current schema
                $diff->addRemovedColumn($colName, $backupCol);
                continue;
            }

            // Check data type compatibility
            if (!$this->isTypeCompatible($backupCol['data_type'], $currentCol['data_type'])) {
                $diff->addTypeChange($colName, $backupCol['data_type'], $currentCol['data_type']);
            }

            // Check nullable change
            if ($backupCol['is_nullable'] === 'YES' && $currentCol['is_nullable'] === 'NO') {
                // Column became NOT NULL - potential issue
                $diff->addNullabilityChange($colName, true, false);
            }
        }

        // Check for new required columns
        foreach ($currentColumns as $currentCol) {
            $colName = $currentCol['column_name'];
            $backupCol = collect($backupColumns)->firstWhere('column_name', $colName);

            if (!$backupCol && $currentCol['is_nullable'] === 'NO' && !$currentCol['column_default']) {
                // New required column without default - breaking
                $diff->addNewRequiredColumn($colName, $currentCol);
            }
        }

        return $diff;
    }

    /**
     * Check if data types are compatible
     */
    protected function isTypeCompatible(string $backupType, string $currentType): bool
    {
        // Normalize types
        $backupType = strtolower($backupType);
        $currentType = strtolower($currentType);

        // Exact match
        if ($backupType === $currentType) {
            return true;
        }

        // Compatible type mappings
        $compatibleTypes = [
            'int4' => ['int', 'integer', 'int4', 'bigint', 'int8'],
            'int8' => ['bigint', 'int8'],
            'varchar' => ['varchar', 'character varying', 'text'],
            'text' => ['text', 'varchar', 'character varying'],
            'boolean' => ['boolean', 'bool'],
            'timestamp' => ['timestamp', 'timestamptz', 'timestamp with time zone', 'timestamp without time zone'],
            'uuid' => ['uuid'],
            'jsonb' => ['jsonb', 'json'],
            'json' => ['json', 'jsonb'],
        ];

        foreach ($compatibleTypes as $type => $compatible) {
            if (in_array($backupType, $compatible) && in_array($currentType, $compatible)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get detailed compatibility report for a specific category
     */
    public function getCategoryDetails(array $backupSchema, string $category): array
    {
        $currentSchema = $this->getCurrentSchema();
        $details = [
            'category' => $category,
            'tables' => [],
            'total_tables' => 0,
            'compatible_tables' => 0,
            'incompatible_tables' => 0,
        ];

        if (!isset($backupSchema[$category])) {
            return $details;
        }

        foreach ($backupSchema[$category] as $tableName => $tableDef) {
            $currentTable = $currentSchema[$tableName] ?? null;
            $tableDetail = [
                'name' => $tableName,
                'status' => 'compatible',
                'issues' => [],
            ];

            if (!$currentTable) {
                $tableDetail['status'] = 'incompatible';
                $tableDetail['issues'][] = __('backup.reconcile_table_missing');
                $details['incompatible_tables']++;
            } else {
                $columnDiff = $this->compareColumns(
                    $tableDef['columns'] ?? [],
                    $currentTable['columns']
                );

                if ($columnDiff->hasBreakingChanges()) {
                    $tableDetail['status'] = 'partial';
                    $tableDetail['issues'] = $columnDiff->getIssues();
                    $details['incompatible_tables']++;
                } else {
                    $details['compatible_tables']++;
                }
            }

            $details['tables'][] = $tableDetail;
            $details['total_tables']++;
        }

        return $details;
    }
}

/**
 * Reconciliation Report
 *
 * Contains the results of schema reconciliation.
 */
class ReconciliationReport
{
    protected array $compatible = [];
    protected array $partiallyCompatible = [];
    protected array $incompatible = [];
    protected array $warnings = [];

    public function addCompatible(string $category, string $tableName): void
    {
        if (!isset($this->compatible[$category])) {
            $this->compatible[$category] = [];
        }
        $this->compatible[$category][] = $tableName;
    }

    public function addCompatibleWithWarnings(string $category, string $tableName, ColumnDiff $diff): void
    {
        if (!isset($this->compatible[$category])) {
            $this->compatible[$category] = [];
        }
        $this->compatible[$category][] = $tableName;
        $this->warnings[$tableName] = $diff->getWarnings();
    }

    public function addPartiallyCompatible(string $category, string $tableName, ColumnDiff $diff): void
    {
        if (!isset($this->partiallyCompatible[$category])) {
            $this->partiallyCompatible[$category] = [];
        }
        $this->partiallyCompatible[$category][$tableName] = $diff->toArray();
    }

    public function addIncompatible(string $category, string $tableName, string $reason, string $message): void
    {
        if (!isset($this->incompatible[$category])) {
            $this->incompatible[$category] = [];
        }
        $this->incompatible[$category][$tableName] = [
            'reason' => $reason,
            'message' => $message,
        ];
    }

    public function isFullyCompatible(): bool
    {
        return empty($this->incompatible) && empty($this->partiallyCompatible);
    }

    public function isPartiallyCompatible(): bool
    {
        return !empty($this->partiallyCompatible) && empty($this->incompatible);
    }

    public function toArray(): array
    {
        return [
            'compatible' => $this->compatible,
            'partially_compatible' => $this->partiallyCompatible,
            'incompatible' => $this->incompatible,
            'warnings' => $this->warnings,
            'is_fully_compatible' => $this->isFullyCompatible(),
            'is_partially_compatible' => $this->isPartiallyCompatible(),
            'summary' => [
                'compatible_count' => $this->countTables($this->compatible),
                'partial_count' => $this->countTables($this->partiallyCompatible),
                'incompatible_count' => $this->countTables($this->incompatible),
            ],
        ];
    }

    protected function countTables(array $categories): int
    {
        $count = 0;
        foreach ($categories as $tables) {
            $count += is_array($tables) ? count($tables) : 1;
        }
        return $count;
    }
}

/**
 * Column Diff
 *
 * Contains column-level differences between backup and current schema.
 */
class ColumnDiff
{
    protected array $removedColumns = [];
    protected array $typeChanges = [];
    protected array $nullabilityChanges = [];
    protected array $newRequiredColumns = [];

    public function addRemovedColumn(string $name, array $definition): void
    {
        $this->removedColumns[$name] = $definition;
    }

    public function addTypeChange(string $name, string $oldType, string $newType): void
    {
        $this->typeChanges[$name] = [
            'old' => $oldType,
            'new' => $newType,
        ];
    }

    public function addNullabilityChange(string $name, bool $wasNullable, bool $isNullable): void
    {
        $this->nullabilityChanges[$name] = [
            'was_nullable' => $wasNullable,
            'is_nullable' => $isNullable,
        ];
    }

    public function addNewRequiredColumn(string $name, array $definition): void
    {
        $this->newRequiredColumns[$name] = $definition;
    }

    public function hasBreakingChanges(): bool
    {
        // Breaking changes:
        // - Type changes that can't be auto-converted
        // - New required columns without defaults
        return !empty($this->typeChanges) || !empty($this->newRequiredColumns);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->removedColumns) || !empty($this->nullabilityChanges);
    }

    public function getIssues(): array
    {
        $issues = [];

        foreach ($this->typeChanges as $col => $change) {
            $issues[] = __('backup.reconcile_type_change', [
                'column' => $col,
                'old' => $change['old'],
                'new' => $change['new'],
            ]);
        }

        foreach ($this->newRequiredColumns as $col => $def) {
            $issues[] = __('backup.reconcile_new_required', ['column' => $col]);
        }

        return $issues;
    }

    public function getWarnings(): array
    {
        $warnings = [];

        foreach ($this->removedColumns as $col => $def) {
            $warnings[] = __('backup.reconcile_column_removed', ['column' => $col]);
        }

        foreach ($this->nullabilityChanges as $col => $change) {
            if ($change['was_nullable'] && !$change['is_nullable']) {
                $warnings[] = __('backup.reconcile_not_nullable', ['column' => $col]);
            }
        }

        return $warnings;
    }

    public function toArray(): array
    {
        return [
            'removed_columns' => $this->removedColumns,
            'type_changes' => $this->typeChanges,
            'nullability_changes' => $this->nullabilityChanges,
            'new_required_columns' => $this->newRequiredColumns,
            'has_breaking_changes' => $this->hasBreakingChanges(),
            'has_warnings' => $this->hasWarnings(),
            'issues' => $this->getIssues(),
            'warnings' => $this->getWarnings(),
        ];
    }
}
