<?php

namespace App\Models\Security;

use App\Models\Core\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasFactory;

    protected $table = 'cmis.role_permissions';
    protected $primaryKey = 'id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'role_id',
        'permission_id',
        'granted_by',
        'granted_at',
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
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    /**
     * Get the permission
     */
    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id', 'permission_id');
    }

    /**
     * Get the user who granted this permission
     */
    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by', 'user_id');
    }

    /**
     * Scope to get permissions for a specific role
     */
    public function scopeForRole($query, string $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope to get roles with a specific permission
     */
    public function scopeWithPermission($query, string $permissionId)
    {
        return $query->where('permission_id', $permissionId);
    }
}
