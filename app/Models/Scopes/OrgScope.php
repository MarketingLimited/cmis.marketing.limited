<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrgScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * Ensures all queries are scoped to the current organization
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Get current org_id from application context
        $orgId = app('current_org_id');

        // Skip if no org context (e.g., system operations)
        if (!$orgId) {
            return;

        // Skip for certain tables that don't have org_id
        $excludedTables = ['orgs', 'users', 'roles', 'permissions'];

        if (in_array($model->getTable(), $excludedTables)) {
            return;

        // Apply org filter
        if ($model->hasOrgIdColumn()) {
            $builder->where($model->getTable() . '.org_id', $orgId);
}
