<?php

namespace App\Models\Concerns;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * HasOrganization Trait
 *
 * Provides standardized organization relationship for models.
 * Eliminates duplicate org() relationship definitions across 99+ models.
 *
 * All models in CMIS are multi-tenant and belong to an organization.
 * This trait ensures consistency and reduces code duplication.
 *
 * Usage:
 * ```php
 * class Campaign extends BaseModel
 * {
 *     use HasOrganization;
 *
 *     // Now you have org() relationship automatically
 * }
 * ```
 *
 * @package App\Models\Concerns
 */
trait HasOrganization
{
    /**
     * Get the organization that owns this record
     *
     * @return BelongsTo
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * Scope a query to only include records for a specific organization
     *
     * ⚠️ WARNING: This method BYPASSES Row-Level Security (RLS)!
     *
     * In most cases, you should NOT use this method. CMIS uses PostgreSQL RLS
     * to automatically filter queries by organization. Manual org_id filtering
     * bypasses this security layer and can lead to data leakage.
     *
     * USE CASES WHERE THIS IS ACCEPTABLE:
     * - Console commands that iterate over multiple organizations
     * - Administrative tools that need cross-org queries
     * - Background jobs where RLS context is not set
     *
     * PREFERRED APPROACH (let RLS handle filtering):
     * ```php
     * // ✅ CORRECT - RLS filters automatically
     * $campaigns = Campaign::all();
     *
     * // ❌ WRONG - Bypasses RLS
     * $campaigns = Campaign::forOrganization($orgId)->get();
     * ```
     *
     * Before using this method, ensure:
     * 1. RLS context is NOT set (or you're intentionally bypassing it)
     * 2. You have a legitimate need for cross-org queries
     * 3. You understand the security implications
     *
     * @deprecated Use RLS context instead of manual org filtering
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $orgId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForOrganization($query, string $orgId): Builder
    {
        // Log warning when this scope is used
        \Illuminate\Support\Facades\Log::warning(
            'HasOrganization::scopeForOrganization() called - this bypasses RLS. ' .
            'Consider using RLS context instead of manual org filtering.',
            [
                'model' => get_class($this),
                'org_id' => $orgId,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
            ]
        );

        return $query->where('org_id', $orgId);
    }

    /**
     * Check if this record belongs to a specific organization
     *
     * @param string $orgId
     * @return bool
     */
    public function belongsToOrganization(string $orgId): bool
    {
        return $this->org_id === $orgId;
    }

    /**
     * Get the organization ID
     *
     * @return string|null
     */
    public function getOrganizationId(): ?string
    {
        return $this->org_id;
    }
}
