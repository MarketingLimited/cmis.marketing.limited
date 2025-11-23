<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Scopes\OrgScope;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
abstract class BaseModel extends Model
{
    use SoftDeletes, HasUuids;

    /**
     * The connection name for the model.
     */
    protected $connection = 'pgsql';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * Boot the model and apply global scopes
     */
    protected static function booted(): void
    {
        // Apply OrgScope to ensure multi-tenancy isolation
        static::addGlobalScope(new OrgScope);
    }

    /**
     * Check if the model has an org_id column
     */
    public function hasOrgIdColumn(): bool
    {
        return in_array('org_id', $this->getFillable()) ||
               in_array('org_id', array_keys($this->getAttributes()));
    }

    /**
     * Get the current organization ID from context
     */
    protected function getCurrentOrgId(): ?string
    {
        return app()->bound('current_org_id') ? app('current_org_id') : null;
    }

    /**
     * Scope a query to a specific organization
     */
    public function scopeForOrg(Builder $query, string $orgId): Builder
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Scope to get records without org filter (use carefully!)
     */
    public function scopeWithoutOrgFilter(Builder $query): Builder
    {
        return $query->withoutGlobalScope(OrgScope::class);
    }
}
