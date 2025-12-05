<?php

namespace App\Apps\Backup\Services\Export;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

/**
 * Export Mapper Service
 *
 * Maps internal database table and column names to user-friendly
 * export names for backup files. Supports bidirectional mapping
 * for both export and import operations.
 */
class ExportMapperService
{
    /**
     * Cache TTL for mappings (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Table name mappings (internal => friendly)
     */
    protected array $tableMappings;

    /**
     * Column name mappings by table (table => [internal => friendly])
     */
    protected array $columnMappings;

    /**
     * Category labels for export
     */
    protected array $categoryLabels;

    public function __construct()
    {
        $this->loadMappings();
    }

    /**
     * Load mappings from config
     */
    protected function loadMappings(): void
    {
        $this->tableMappings = config('backup.export.table_mappings', []);
        $this->columnMappings = config('backup.export.column_mappings', []);
        $this->categoryLabels = config('backup.category_mapping', []);
    }

    /**
     * Get friendly name for a table
     *
     * @param string $tableName Fully qualified table name (schema.table)
     * @return string Friendly name for export
     */
    public function getTableFriendlyName(string $tableName): string
    {
        // Check explicit mapping first
        if (isset($this->tableMappings[$tableName])) {
            return $this->tableMappings[$tableName];
        }

        // Extract table name without schema
        $parts = explode('.', $tableName);
        $shortName = end($parts);

        // Generate friendly name from table name
        return $this->humanize($shortName);
    }

    /**
     * Get internal table name from friendly name
     *
     * @param string $friendlyName Friendly export name
     * @param string|null $schema Optional schema hint
     * @return string|null Internal table name or null if not found
     */
    public function getInternalTableName(string $friendlyName, ?string $schema = null): ?string
    {
        // Check reverse mapping
        $reversed = array_flip($this->tableMappings);
        if (isset($reversed[$friendlyName])) {
            return $reversed[$friendlyName];
        }

        // Try to find by humanized name match
        foreach ($this->tableMappings as $internal => $friendly) {
            if (strcasecmp($friendly, $friendlyName) === 0) {
                return $internal;
            }
        }

        // Generate internal name from friendly name
        $snakeName = Str::snake(str_replace(' ', '', $friendlyName));

        if ($schema) {
            return "{$schema}.{$snakeName}";
        }

        return $snakeName;
    }

    /**
     * Get friendly name for a column
     *
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @return string Friendly column name
     */
    public function getColumnFriendlyName(string $tableName, string $columnName): string
    {
        // Check explicit mapping
        if (isset($this->columnMappings[$tableName][$columnName])) {
            return $this->columnMappings[$tableName][$columnName];
        }

        // Check global column mappings
        $globalMappings = $this->getGlobalColumnMappings();
        if (isset($globalMappings[$columnName])) {
            return $globalMappings[$columnName];
        }

        // Generate friendly name
        return $this->humanize($columnName);
    }

    /**
     * Get internal column name from friendly name
     *
     * @param string $tableName Table name
     * @param string $friendlyName Friendly column name
     * @return string Internal column name
     */
    public function getInternalColumnName(string $tableName, string $friendlyName): string
    {
        // Check reverse mapping for this table
        if (isset($this->columnMappings[$tableName])) {
            $reversed = array_flip($this->columnMappings[$tableName]);
            if (isset($reversed[$friendlyName])) {
                return $reversed[$friendlyName];
            }
        }

        // Check global mappings
        $globalMappings = array_flip($this->getGlobalColumnMappings());
        if (isset($globalMappings[$friendlyName])) {
            return $globalMappings[$friendlyName];
        }

        // Generate snake_case from friendly name
        return Str::snake(str_replace(' ', '', $friendlyName));
    }

    /**
     * Get global column name mappings
     *
     * @return array Common column mappings
     */
    protected function getGlobalColumnMappings(): array
    {
        return [
            // System columns
            'id' => 'ID',
            'org_id' => 'Organization ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'deleted_at' => 'Deleted At',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',

            // Common business columns
            'name' => 'Name',
            'title' => 'Title',
            'description' => 'Description',
            'status' => 'Status',
            'is_active' => 'Is Active',
            'is_enabled' => 'Is Enabled',
            'type' => 'Type',
            'category' => 'Category',

            // Contact columns
            'email' => 'Email',
            'phone' => 'Phone',
            'address' => 'Address',
            'city' => 'City',
            'country' => 'Country',
            'postal_code' => 'Postal Code',

            // Financial columns
            'amount' => 'Amount',
            'currency' => 'Currency',
            'budget' => 'Budget',
            'spent' => 'Spent',
            'cost' => 'Cost',
            'price' => 'Price',

            // Date columns
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'started_at' => 'Started At',
            'completed_at' => 'Completed At',
            'expires_at' => 'Expires At',
            'scheduled_at' => 'Scheduled At',
            'published_at' => 'Published At',

            // Media columns
            'file_path' => 'File Path',
            'file_url' => 'File URL',
            'image_url' => 'Image URL',
            'thumbnail_url' => 'Thumbnail URL',
            'media_url' => 'Media URL',

            // Platform columns
            'platform' => 'Platform',
            'platform_id' => 'Platform ID',
            'external_id' => 'External ID',
            'account_id' => 'Account ID',

            // Metrics columns
            'impressions' => 'Impressions',
            'clicks' => 'Clicks',
            'conversions' => 'Conversions',
            'reach' => 'Reach',
            'engagement' => 'Engagement',
            'spend' => 'Spend',
            'ctr' => 'CTR',
            'cpc' => 'CPC',
            'cpm' => 'CPM',
            'roas' => 'ROAS',
        ];
    }

    /**
     * Get friendly category label
     *
     * @param string $categoryKey Category key
     * @return string Friendly label
     */
    public function getCategoryLabel(string $categoryKey): string
    {
        if (isset($this->categoryLabels[$categoryKey]['label'])) {
            return $this->categoryLabels[$categoryKey]['label'];
        }

        return $this->humanize($categoryKey);
    }

    /**
     * Map an entire record to friendly column names
     *
     * @param string $tableName Table name
     * @param array $record Database record
     * @return array Record with friendly column names
     */
    public function mapRecordToFriendly(string $tableName, array $record): array
    {
        $mapped = [];

        foreach ($record as $column => $value) {
            $friendlyColumn = $this->getColumnFriendlyName($tableName, $column);
            $mapped[$friendlyColumn] = $value;
        }

        return $mapped;
    }

    /**
     * Map a record from friendly names back to internal names
     *
     * @param string $tableName Table name
     * @param array $record Record with friendly names
     * @return array Record with internal column names
     */
    public function mapRecordToInternal(string $tableName, array $record): array
    {
        $mapped = [];

        foreach ($record as $column => $value) {
            $internalColumn = $this->getInternalColumnName($tableName, $column);
            $mapped[$internalColumn] = $value;
        }

        return $mapped;
    }

    /**
     * Get export metadata for a table
     *
     * @param string $tableName Table name
     * @param int $recordCount Number of records
     * @return array Export metadata
     */
    public function getTableExportMetadata(string $tableName, int $recordCount): array
    {
        return [
            'internal_name' => $tableName,
            'friendly_name' => $this->getTableFriendlyName($tableName),
            'record_count' => $recordCount,
            'exported_at' => now()->toISOString(),
        ];
    }

    /**
     * Build export manifest for a category
     *
     * @param string $categoryKey Category key
     * @param array $tables Tables with their data
     * @return array Category manifest
     */
    public function buildCategoryManifest(string $categoryKey, array $tables): array
    {
        $manifest = [
            'category' => $categoryKey,
            'label' => $this->getCategoryLabel($categoryKey),
            'tables' => [],
            'total_records' => 0,
        ];

        foreach ($tables as $tableName => $records) {
            $count = is_array($records) ? count($records) : 0;
            $manifest['tables'][] = $this->getTableExportMetadata($tableName, $count);
            $manifest['total_records'] += $count;
        }

        return $manifest;
    }

    /**
     * Convert a snake_case or camelCase string to human-readable
     *
     * @param string $value Input string
     * @return string Human-readable string
     */
    protected function humanize(string $value): string
    {
        // Handle common prefixes
        $value = preg_replace('/^(cmis_|cmis\.)/', '', $value);

        // Convert snake_case to Title Case
        $value = str_replace('_', ' ', $value);

        // Convert camelCase to Title Case
        $value = preg_replace('/([a-z])([A-Z])/', '$1 $2', $value);

        // Capitalize words
        $value = ucwords(strtolower($value));

        // Handle common abbreviations
        $abbreviations = ['Id' => 'ID', 'Url' => 'URL', 'Api' => 'API', 'Rls' => 'RLS', 'Ctr' => 'CTR', 'Cpc' => 'CPC', 'Cpm' => 'CPM', 'Roas' => 'ROAS'];

        foreach ($abbreviations as $from => $to) {
            $value = preg_replace('/\b' . $from . '\b/', $to, $value);
        }

        return $value;
    }

    /**
     * Get all registered table mappings
     *
     * @return array Table mappings
     */
    public function getTableMappings(): array
    {
        return $this->tableMappings;
    }

    /**
     * Register a custom table mapping
     *
     * @param string $internalName Internal table name
     * @param string $friendlyName Friendly export name
     * @return self
     */
    public function registerTableMapping(string $internalName, string $friendlyName): self
    {
        $this->tableMappings[$internalName] = $friendlyName;
        return $this;
    }

    /**
     * Register a custom column mapping
     *
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @param string $friendlyName Friendly name
     * @return self
     */
    public function registerColumnMapping(string $tableName, string $columnName, string $friendlyName): self
    {
        if (!isset($this->columnMappings[$tableName])) {
            $this->columnMappings[$tableName] = [];
        }

        $this->columnMappings[$tableName][$columnName] = $friendlyName;
        return $this;
    }

    /**
     * Clear all custom mappings and reload from config
     *
     * @return self
     */
    public function resetMappings(): self
    {
        $this->loadMappings();
        return $this;
    }

    /**
     * Export mappings to array (for manifest)
     *
     * @return array All mappings
     */
    public function exportMappings(): array
    {
        return [
            'tables' => $this->tableMappings,
            'columns' => $this->columnMappings,
            'categories' => $this->categoryLabels,
            'global_columns' => $this->getGlobalColumnMappings(),
        ];
    }

    /**
     * Import mappings from backup manifest
     *
     * @param array $mappings Mappings from manifest
     * @return self
     */
    public function importMappings(array $mappings): self
    {
        if (isset($mappings['tables'])) {
            $this->tableMappings = array_merge($this->tableMappings, $mappings['tables']);
        }

        if (isset($mappings['columns'])) {
            foreach ($mappings['columns'] as $table => $columns) {
                if (!isset($this->columnMappings[$table])) {
                    $this->columnMappings[$table] = [];
                }
                $this->columnMappings[$table] = array_merge($this->columnMappings[$table], $columns);
            }
        }

        return $this;
    }
}
