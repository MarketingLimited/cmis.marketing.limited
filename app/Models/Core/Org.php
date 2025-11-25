<?php

namespace App\Models\Core;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Org extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.orgs';

    protected $primaryKey = 'org_id';

    public $timestamps = true;


    protected $fillable = [
        'org_id',
        'name',
        'default_locale',
        'currency',
        'provider',
    ];

    protected $casts = [
        'org_id' => 'string',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get all users belonging to this organization.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'cmis.user_orgs',
            'org_id',
            'user_id'
        )
        ->withPivot(['role_id', 'is_active', 'joined_at', 'invited_by', 'last_accessed'])
        ->wherePivot('is_active', true)
        ->wherePivotNull('deleted_at');
    }

    /**
     * Get all roles for this organization.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'org_id', 'org_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(\App\Models\Campaign::class, 'org_id', 'org_id');
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(\App\Models\Offering::class, 'org_id', 'org_id');
    }

    public function creativeAssets(): HasMany
    {
        return $this->hasMany(\App\Models\CreativeAsset::class, 'org_id', 'org_id');
    }

    /**
     * Get all integrations for this organization.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class, 'org_id', 'org_id');
    }
}
