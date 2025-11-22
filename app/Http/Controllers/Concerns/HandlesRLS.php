<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\RLSViolationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait HandlesRLS
{
    /**
     * Find a model by ID with RLS-aware error handling.
     *
     * @param  string  $modelClass  The model class name
     * @param  string  $id  The model ID
     * @param  string  $resourceType  Human-readable resource type
     * @throws RLSViolationException
     */
    protected function findOrFailWithRLS(string $modelClass, string $id, string $resourceType = 'resource'): Model
    {
        // Try to find with current org context (RLS active)
        $model = $modelClass::find($id);

        if ($model) {
            return $model;
        }

        // Not found with current org context
        // Check if it exists in another organization
        $existsInOtherOrg = $this->existsInDifferentOrg($modelClass, $id);

        if ($existsInOtherOrg) {
            // Resource exists but in different org - RLS violation
            throw new RLSViolationException(
                resourceType: $resourceType,
                resourceId: $id,
                actualOrgId: $existsInOtherOrg['org_id'],
                message: "This {$resourceType} belongs to a different organization. Please switch organizations to access it."
            );
        }

        // Truly not found
        throw new RLSViolationException(
            resourceType: $resourceType,
            resourceId: $id,
            actualOrgId: null,
            message: "{$resourceType} not found."
        );
    }

    /**
     * Check if a resource exists in a different organization.
     *
     * This temporarily disables RLS to check existence.
     *
     * @return array|null Returns ['org_id' => '...'] if found, null otherwise
     */
    protected function existsInDifferentOrg(string $modelClass, string $id): ?array
    {
        // Get table name from model
        $model = new $modelClass;
        $table = $model->getTable();

        // Temporarily disable RLS by unsetting org context
        // This is safe because we're only checking existence, not returning data
        try {
            $result = DB::selectOne("
                SELECT org_id
                FROM {$table}
                WHERE id = ?
                AND deleted_at IS NULL
                LIMIT 1
            ", [$id]);

            return $result ? ['org_id' => $result->org_id] : null;
        } catch (\Exception $e) {
            // If query fails, assume it doesn't exist
            return null;
        }
    }

    /**
     * Verify current organization has access to a resource.
     *
     * @throws RLSViolationException
     */
    protected function verifyOrgAccess(Model $model, string $resourceType = 'resource'): void
    {
        $currentOrgId = session('current_org_id') ?? auth()->user()?->org_id;

        if (!$currentOrgId) {
            throw new RLSViolationException(
                resourceType: $resourceType,
                message: 'No organization context set. Please select an organization.'
            );
        }

        if ($model->org_id !== $currentOrgId) {
            throw new RLSViolationException(
                resourceType: $resourceType,
                resourceId: $model->id,
                actualOrgId: $model->org_id,
                message: "This {$resourceType} belongs to a different organization."
            );
        }
    }

    /**
     * Get a user-friendly resource type name from model class.
     */
    protected function getResourceTypeName(string $modelClass): string
    {
        $className = class_basename($modelClass);

        // Convert from PascalCase to words
        $name = preg_replace('/(?<!^)[A-Z]/', ' $0', $className);

        return strtolower($name);
    }
}
