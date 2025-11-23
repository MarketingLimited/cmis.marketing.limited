<?php

namespace App\Models\Security;

use App\Models\Core\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Permission extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'cmis.permissions';
    protected $primaryKey = 'permission_id';
    protected $fillable = [
        'permission_id',
        'permission_code',
        'permission_name',
        'category',
        'description',
        'is_dangerous',
        'provider',
    ];

    protected $casts = ['permission_id' => 'string',
        'is_system' => 'boolean',
        'requires_org_context' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_dangerous' => 'boolean',
    ];

    /**
     * Get roles that have this permission
     */
    public function roles()
    : \Illuminate\Database\Eloquent\Relations\Relation {
        return $this->belongsToMany(Role::class, 'cmis.role_permissions',
            'permission_id', 'role_id')
            ->withPivot('granted_by', 'granted_at')
            ->withTimestamps();

    }
    /**
     * Get users that have this permission directly assigned
     */
    public function users()
    : \Illuminate\Database\Eloquent\Relations\Relation {
        return $this->belongsToMany(User::class, 'cmis.user_permissions',
            'permission_id', 'user_id')
            ->withPivot('is_granted', 'expires_at', 'granted_by', 'granted_at')
            ->withTimestamps();

    }
    /**
     * Scope to get system permissions
     */
    public function scopeSystem($query): Builder
    {
        return $query->where('is_system', true);

    }
    /**
     * Scope to get custom permissions
     */
    public function scopeCustom($query): Builder
    {
        return $query->where('is_system', false);

    }
    /**
     * Scope to filter by module
     */
    public function scopeForModule($query, string $module): Builder
    {
        return $query->where('module', $module);

    }
    /**
     * Scope to filter by resource
     */
    public function scopeForResource($query, string $resource): Builder
    {
        return $query->where('resource', $resource);

    }
    /**
     * Get full permission string (module.resource.action)
     */
    public function getFullCodeAttribute(): string
    {
        return "{$this->module}.{$this->resource}.{$this->action}";
    }

    /**
     * Check if permission requires organization context
     */
    public function requiresOrgContext(): bool
    {
        return $this->requires_org_context ?? true;
}
}
