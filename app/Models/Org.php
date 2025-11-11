<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Core\{User, UserOrg, Role};

class Org extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.orgs';

    protected $primaryKey = 'org_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
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
     * العلاقة: الشركة لديها عدة مستخدمين
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'cmis.user_orgs', 'org_id', 'user_id')
            ->withPivot('role_id', 'is_active', 'joined_at', 'last_accessed')
            ->wherePivot('is_active', true)
            ->wherePivotNull('deleted_at')
            ->using(UserOrg::class);
    }

    /**
     * العلاقة: الأدوار المخصصة للشركة
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, 'org_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'org_id', 'org_id');
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(Offering::class, 'org_id', 'org_id');
    }

    public function creativeAssets(): HasMany
    {
        return $this->hasMany(CreativeAsset::class, 'org_id', 'org_id');
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class, 'org_id', 'org_id');
    }

    /**
     * Scope: الشركات النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}
