<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrgScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * DISABLED: Global OrgScope is disabled to allow multi-org access via URL.
     *
     * Organization filtering is now handled explicitly:
     * - Controllers receive org_id from route parameters (/orgs/{org}/campaigns)
     * - Middleware validates org access permissions
     * - Queries explicitly filter by org_id when needed
     *
     * This approach allows users to work with multiple organizations simultaneously
     * by accessing different URLs in different browser tabs.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Scope is disabled - no automatic filtering
        return;

        // ========== OLD SESSION-BASED IMPLEMENTATION (DISABLED) ==========
        // Get current org_id from session
        // $orgId = session('current_org_id');
        //
        // // Skip if no org context (e.g., system operations)
        // if (!$orgId) {
        //     return;
        // }
        //
        // // Skip for certain tables that don't have org_id
        // $excludedTables = ['orgs', 'users', 'roles', 'permissions'];
        //
        // if (in_array($model->getTable(), $excludedTables)) {
        //     return;
        // }
        //
        // // Apply org filter
        // if ($model->hasOrgIdColumn()) {
        //     $builder->where($model->getTable() . '.org_id', $orgId);
        // }
    }
}
