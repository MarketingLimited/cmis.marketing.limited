<?php

namespace App\Models\Security;

use App\Models\Core\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class RolePermission extends BaseModel
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.role_permissions';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'role_id',
        'permission_id',
        'granted_at',
        'granted_by',
        'provider',
    ];

    protected $casts = [
        'id' => 'string',
        'role_id' => 'string',
        'permission_id' => 'string',
        'granted_by' => 'string',
        'granted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the role
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');

    }
    /**
     * Get the permission
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id', 'permission_id');

    }
    /**
     * Get the user who granted this permission
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by', 'user_id');

    }
    /**
     * Scope to get permissions for a specific role
     */
    public function scopeForRole($query, string $roleId): Builder
    {
        return $query->where('role_id', $roleId);

    }
    /**
     * Scope to get roles with a specific permission
     */
    public function scopeWithPermission($query, string $permissionId): Builder
    {
        return $query->where('permission_id', $permissionId);
}
}
