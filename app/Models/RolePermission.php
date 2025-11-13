<?php

namespace App\Models;

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
