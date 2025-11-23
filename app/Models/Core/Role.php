<?php

namespace App\Models\Core;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Role extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.roles';

    protected $primaryKey = 'role_id';

    public $timestamps = true;

    protected $fillable = [
        'role_id',
        'org_id',
        'role_name',
        'role_code',
        'description',
        'is_system',
        'is_active',
        'created_by',
        'provider',
    ];

    protected $casts = [
        'role_id' => 'string',
        'org_id' => 'string',
        'created_by' => 'string',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    

    /**
     * Get the user who created this role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');

    }
    /**
     * Get all user-org relationships with this role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userOrgs(): HasMany
    {
        return $this->hasMany(UserOrg::class, 'role_id', 'role_id');

    }
    /**
     * Get all permissions for this role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Permission::class,
            'cmis.role_permissions',
            'role_id',
            'permission_id'
        )
            ->withPivot('granted_by')
            ->withTimestamps();

    }
    /**
     * Get all role-permission pivot records.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function rolePermissions(): HasMany
    {
        return $this->hasMany(\App\Models\RolePermission::class, 'role_id', 'role_id');
}
}
