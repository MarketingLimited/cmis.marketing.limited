<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the role that owns this permission
     */
    public function role()
    {
        return $this->belongsTo(\App\Models\Core\Role::class, 'role_id', 'role_id');

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
}
