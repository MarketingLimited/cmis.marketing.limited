<?php

namespace App\Models\Context;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Core\Org;
use App\Models\User;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class ContextBase extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.contexts_base';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'context_type',
        'name',
        'org_id',
        'provider',
    ];

    protected $casts = [
        'id' => 'string',
        'org_id' => 'string',
        'created_by' => 'string',
        'metadata' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    

    /**
     * Get the user who created this context
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');

    /**
     * Scope to get active contexts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('deleted_at');

    /**
     * Scope by context type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('context_type', $type);

    /**
     * Scope by organization
     */
    public function scopeForOrg(Builder $query, string $orgId): Builder
    {
        return $query->where('org_id', $orgId);
}
