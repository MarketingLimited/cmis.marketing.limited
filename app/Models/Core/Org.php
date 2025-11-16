<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Org extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.orgs';

    protected $primaryKey = 'org_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    /**
     * Boot function to auto-generate UUID
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

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
        ->withTimestamps()
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
