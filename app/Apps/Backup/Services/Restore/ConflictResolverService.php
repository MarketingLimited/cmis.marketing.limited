<?php

namespace App\Apps\Backup\Services\Restore;

use App\Apps\Backup\Services\Discovery\SchemaDiscoveryService;
use App\Apps\Backup\Services\Export\ExportMapperService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Conflict Resolver Service
 *
 * Handles detection and resolution of conflicts between backup data
 * and existing data during restore operations.
 */
class ConflictResolverService
{
    protected SchemaDiscoveryService $discovery;
    protected ExportMapperService $mapper;

    public function __construct(
        SchemaDiscoveryService $discovery,
        ExportMapperService $mapper
    ) {
        $this->discovery = $discovery;
        $this->mapper = $mapper;
    }

    /**
     * Preview potential conflicts before restore
     */
    public function previewConflicts(string $orgId, string $extractedPath, array $manifest): array
    {
        $conflicts = [
            'total' => 0,
            'by_category' => [],
            'sample_conflicts' => [],
        ];

        $dataFiles = $manifest['data_files'] ?? [];

        foreach ($dataFiles as $category => $info) {
            $dataPath = $extractedPath . '/data/' . $category . '.json';

            if (!file_exists($dataPath)) {
                continue;
            }

            $data = json_decode(file_get_contents($dataPath), true);

            if (!is_array($data)) {
                continue;
            }

            // Get the internal table name
            $tableName = $this->mapper->getTableInternalName($category);

            if (!$tableName) {
                continue;
            }

            // Count conflicts
            $categoryConflicts = $this->countCategoryConflicts($orgId, $tableName, $data);

            if ($categoryConflicts['count'] > 0) {
                $conflicts['by_category'][$category] = $categoryConflicts;
                $conflicts['total'] += $categoryConflicts['count'];

                // Add sample conflicts (first 5)
                $conflicts['sample_conflicts'][$category] = $categoryConflicts['samples'];
            }
        }

        return $conflicts;
    }

    /**
     * Count conflicts for a category
     */
    protected function countCategoryConflicts(string $orgId, string $tableName, array $data): array
    {
        $conflicts = [
            'count' => 0,
            'samples' => [],
        ];

        // Set RLS context
        DB::statement("SELECT cmis.init_transaction_context(?, ?)", [
            config('cmis.system_user_id'),
            $orgId
        ]);

        foreach ($data as $record) {
            $id = $record['id'] ?? null;

            if (!$id) {
                continue;
            }

            try {
                // Check if record exists
                $existing = DB::table($tableName)->where('id', $id)->first();

                if ($existing) {
                    $conflicts['count']++;

                    // Add to samples (max 5)
                    if (count($conflicts['samples']) < 5) {
                        $conflicts['samples'][] = [
                            'id' => $id,
                            'backup_data' => $this->sanitizeRecord($record),
                            'existing_data' => $this->sanitizeRecord((array) $existing),
                            'different_fields' => $this->findDifferentFields($record, (array) $existing),
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Table might not exist, skip
                continue;
            }
        }

        return $conflicts;
    }

    /**
     * Resolve a single conflict
     */
    public function resolve(
        array $backupRecord,
        ?array $existingRecord,
        string $strategy
    ): ResolvedRecord {
        if (!$existingRecord) {
            // No conflict - insert new record
            return new ResolvedRecord('insert', $backupRecord);
        }

        return match ($strategy) {
            'skip' => $this->skip($backupRecord, $existingRecord),
            'replace' => $this->replace($backupRecord, $existingRecord),
            'merge' => $this->merge($backupRecord, $existingRecord),
            'ask' => $this->markForUserDecision($backupRecord, $existingRecord),
            default => $this->skip($backupRecord, $existingRecord),
        };
    }

    /**
     * Skip strategy - keep existing record
     */
    protected function skip(array $backup, array $existing): ResolvedRecord
    {
        return new ResolvedRecord('skip', null, [
            'reason' => 'Record already exists',
            'backup_id' => $backup['id'] ?? null,
            'existing_id' => $existing['id'] ?? null,
        ]);
    }

    /**
     * Replace strategy - overwrite existing with backup
     */
    protected function replace(array $backup, array $existing): ResolvedRecord
    {
        // Preserve system fields from existing
        $systemFields = ['org_id', 'created_at'];
        foreach ($systemFields as $field) {
            if (isset($existing[$field])) {
                $backup[$field] = $existing[$field];
            }
        }

        // Update timestamp
        $backup['updated_at'] = now();

        return new ResolvedRecord('update', $backup, [
            'replaced_record_id' => $existing['id'] ?? null,
        ]);
    }

    /**
     * Merge strategy - combine fields from both records
     */
    protected function merge(array $backup, array $existing): ResolvedRecord
    {
        $merged = $existing;

        foreach ($backup as $field => $value) {
            // Skip system fields
            if (in_array($field, ['id', 'org_id', 'created_at', 'deleted_at'])) {
                continue;
            }

            // Compare timestamps for merge decision
            $backupUpdated = $backup['updated_at'] ?? null;
            $existingUpdated = $existing['updated_at'] ?? null;

            if ($backupUpdated && $existingUpdated) {
                // Keep newer value
                if (strtotime($backupUpdated) > strtotime($existingUpdated)) {
                    $merged[$field] = $value;
                }
            } elseif ($value !== null && ($existing[$field] ?? null) === null) {
                // Fill in empty fields
                $merged[$field] = $value;
            }
        }

        $merged['updated_at'] = now();

        return new ResolvedRecord('update', $merged, [
            'merge_strategy' => 'newer_wins',
        ]);
    }

    /**
     * Mark for user decision
     */
    protected function markForUserDecision(array $backup, array $existing): ResolvedRecord
    {
        return new ResolvedRecord('pending', null, [
            'requires_decision' => true,
            'backup_record' => $this->sanitizeRecord($backup),
            'existing_record' => $this->sanitizeRecord($existing),
            'different_fields' => $this->findDifferentFields($backup, $existing),
        ]);
    }

    /**
     * Apply user decisions to pending conflicts
     */
    public function applyUserDecisions(array $decisions): array
    {
        $results = [];

        foreach ($decisions as $recordId => $decision) {
            $action = $decision['action'] ?? 'skip';
            $backup = $decision['backup_data'] ?? [];
            $existing = $decision['existing_data'] ?? [];

            $results[$recordId] = match ($action) {
                'keep_existing' => $this->skip($backup, $existing),
                'use_backup' => $this->replace($backup, $existing),
                'merge' => $this->merge($backup, $existing),
                'custom' => $this->applyCustomMerge($backup, $existing, $decision['custom_values'] ?? []),
                default => $this->skip($backup, $existing),
            };
        }

        return $results;
    }

    /**
     * Apply custom field-level merge
     */
    protected function applyCustomMerge(array $backup, array $existing, array $customValues): ResolvedRecord
    {
        $result = $existing;

        foreach ($customValues as $field => $source) {
            if ($source === 'backup') {
                $result[$field] = $backup[$field] ?? null;
            } elseif ($source === 'existing') {
                $result[$field] = $existing[$field] ?? null;
            } elseif (is_array($source) && isset($source['value'])) {
                // Custom value provided
                $result[$field] = $source['value'];
            }
        }

        $result['updated_at'] = now();

        return new ResolvedRecord('update', $result, [
            'merge_strategy' => 'custom',
            'custom_fields' => array_keys($customValues),
        ]);
    }

    /**
     * Find fields that differ between backup and existing record
     */
    protected function findDifferentFields(array $backup, array $existing): array
    {
        $different = [];
        $skipFields = ['created_at', 'updated_at', 'deleted_at'];

        foreach ($backup as $field => $value) {
            if (in_array($field, $skipFields)) {
                continue;
            }

            $existingValue = $existing[$field] ?? null;

            if ($this->valuesAreDifferent($value, $existingValue)) {
                $different[] = [
                    'field' => $field,
                    'backup' => $value,
                    'existing' => $existingValue,
                ];
            }
        }

        return $different;
    }

    /**
     * Check if two values are different
     */
    protected function valuesAreDifferent($a, $b): bool
    {
        // Handle JSON comparison
        if (is_array($a) || is_object($a)) {
            $a = json_encode($a);
        }
        if (is_array($b) || is_object($b)) {
            $b = json_encode($b);
        }

        return (string) $a !== (string) $b;
    }

    /**
     * Sanitize record for display (remove sensitive fields)
     */
    protected function sanitizeRecord(array $record): array
    {
        $sensitiveFields = [
            'password', 'token', 'secret', 'credential', 'api_key',
            'access_token', 'refresh_token', 'private_key',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($record[$field])) {
                $record[$field] = '***HIDDEN***';
            }
        }

        return $record;
    }
}

/**
 * Resolved Record
 *
 * Represents the result of conflict resolution for a single record.
 */
class ResolvedRecord
{
    protected string $action;
    protected ?array $data;
    protected array $metadata;

    public function __construct(string $action, ?array $data = null, array $metadata = [])
    {
        $this->action = $action;
        $this->data = $data;
        $this->metadata = $metadata;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isSkipped(): bool
    {
        return $this->action === 'skip';
    }

    public function isPending(): bool
    {
        return $this->action === 'pending';
    }

    public function isInsert(): bool
    {
        return $this->action === 'insert';
    }

    public function isUpdate(): bool
    {
        return $this->action === 'update';
    }

    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'data' => $this->data,
            'metadata' => $this->metadata,
        ];
    }
}
