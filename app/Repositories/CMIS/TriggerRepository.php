<?php

namespace App\Repositories\CMIS;

use Illuminate\Support\Facades\DB;

/**
 * Repository for CMIS Trigger Functions
 * These functions are normally called by database triggers but can be invoked manually
 * for testing, debugging, or special operations
 */
class TriggerRepository
{
    /**
     * Audit creative changes (normally called by trigger)
     * Corresponds to: cmis.audit_creative_changes()
     *
     * Note: This is typically called automatically by a trigger on creative tables
     * Manual invocation is useful for testing or audit log generation
     *
     * @return bool Success status
     */
    public function auditCreativeChanges(): bool
    {
        return DB::statement('SELECT cmis.audit_creative_changes()');
    }

    /**
     * Auto refresh cache on field change (normally called by trigger)
     * Corresponds to: cmis.auto_refresh_cache_on_field_change()
     *
     * Note: Automatically refreshes the required fields cache when field definitions change
     * Can be called manually to force a cache refresh
     *
     * @return bool Success status
     */
    public function autoRefreshCacheOnFieldChange(): bool
    {
        return DB::statement('SELECT cmis.auto_refresh_cache_on_field_change()');
    }

    /**
     * Update unified search vector for contexts (normally called by trigger)
     * Corresponds to: cmis.contexts_unified_search_vector_update()
     *
     * Note: Updates full-text search vectors for context tables
     * Can be called manually to rebuild search indexes
     *
     * @return bool Success status
     */
    public function contextsUnifiedSearchVectorUpdate(): bool
    {
        return DB::statement('SELECT cmis.contexts_unified_search_vector_update()');
    }

    /**
     * Creative contexts delete trigger function (normally called by trigger)
     * Corresponds to: cmis.creative_contexts_delete()
     *
     * Note: Handles soft delete for creative contexts
     * Manual invocation useful for testing cascade operations
     *
     * @return bool Success status
     */
    public function creativeContextsDelete(): bool
    {
        return DB::statement('SELECT cmis.creative_contexts_delete()');
    }

    /**
     * Creative contexts insert trigger function (normally called by trigger)
     * Corresponds to: cmis.creative_contexts_insert()
     *
     * Note: Synchronizes creative contexts with unified contexts table
     * Manual invocation useful for data migration or repair
     *
     * @return bool Success status
     */
    public function creativeContextsInsert(): bool
    {
        return DB::statement('SELECT cmis.creative_contexts_insert()');
    }

    /**
     * Creative contexts update trigger function (normally called by trigger)
     * Corresponds to: cmis.creative_contexts_update()
     *
     * Note: Synchronizes updates between creative contexts and unified contexts
     * Manual invocation useful for data synchronization
     *
     * @return bool Success status
     */
    public function creativeContextsUpdate(): bool
    {
        return DB::statement('SELECT cmis.creative_contexts_update()');
    }

    /**
     * Enforce creative context requirement (normally called by trigger)
     * Corresponds to: cmis.enforce_creative_context()
     *
     * Note: Validates that entities have required creative context
     * Manual invocation useful for data validation checks
     *
     * @return bool Success status
     */
    public function enforceCreativeContext(): bool
    {
        return DB::statement('SELECT cmis.enforce_creative_context()');
    }

    /**
     * Prevent incomplete briefs (normally called by trigger)
     * Corresponds to: cmis.prevent_incomplete_briefs()
     *
     * Note: Validates brief completeness before saving
     * Manual invocation useful for data quality checks
     *
     * @return bool Success status
     */
    public function preventIncompleteBriefs(): bool
    {
        return DB::statement('SELECT cmis.prevent_incomplete_briefs()');
    }

    /**
     * Prevent incomplete briefs (optimized version, normally called by trigger)
     * Corresponds to: cmis.prevent_incomplete_briefs_optimized()
     *
     * Note: Optimized version of brief validation
     * Manual invocation useful for performance testing
     *
     * @return bool Success status
     */
    public function preventIncompleteBriefsOptimized(): bool
    {
        return DB::statement('SELECT cmis.prevent_incomplete_briefs_optimized()');
    }

    /**
     * Update updated_at column (normally called by trigger)
     * Corresponds to: cmis.update_updated_at_column()
     *
     * Note: Automatically updates timestamps on row changes
     * Manual invocation useful for fixing missing timestamps
     *
     * @return bool Success status
     */
    public function updateUpdatedAtColumn(): bool
    {
        return DB::statement('SELECT cmis.update_updated_at_column()');
    }
}
