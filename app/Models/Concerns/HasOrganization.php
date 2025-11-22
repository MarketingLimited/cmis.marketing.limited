<?php

namespace App\Models\Concerns;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $orgId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForOrganization($query, string $orgId)
    {
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
