<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.roles';

    protected $primaryKey = 'role_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
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
     * Get the organization that owns this role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

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
