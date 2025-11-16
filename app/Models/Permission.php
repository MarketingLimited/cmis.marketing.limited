<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Permission extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'cmis.permissions';
    protected $primaryKey = 'permission_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

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
        'created_by' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_dangerous' => 'boolean',
    ];

    /**
     * Get the roles that have this permission
     */
    public function roles()
    {
        return $this->belongsToMany(
            \App\Models\Core\Role::class,
            'cmis.role_permissions',
            'permission_id',
            'role_id'
        )
            ->withPivot('granted_by')
            ->withTimestamps();
    }

    /**
     * Get the users that have this permission directly
     */
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'cmis.user_permissions',
            'permission_id',
            'user_id'
        )
            ->withPivot('is_granted', 'expires_at', 'granted_by')
            ->withTimestamps();
    }

    /**
     * Scope to get system permissions
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to get permissions by module
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope to get permissions by resource
     */
    public function scopeByResource($query, string $resource)
    {
        return $query->where('resource', $resource);
    }

    /**
     * Get the full permission string (module.resource.action)
     */
    public function getFullPermissionAttribute(): string
    {
        return "{$this->module}.{$this->resource}.{$this->action}";
    }
}
