<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'pgsql';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cmis.roles';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'role_id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'org_id',
        'role_name',
        'role_code',
        'description',
        'is_system',
        'is_active',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role_id' => 'string',
            'org_id' => 'string',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * العلاقة: الدور ينتمي لشركة (أو يكون عام)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function org()
    {
        return $this->belongsTo(Org::class, 'org_id');
    }

    /**
     * العلاقة: المستخدمون الذين لديهم هذا الدور
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userOrgs()
    {
        return $this->hasMany(UserOrg::class, 'role_id');
    }

    /**
     * العلاقة: الصلاحيات المرتبطة بهذا الدور
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permissions()
    {
        return $this->hasMany(RolePermission::class, 'role_id');
    }

    /**
     * Scope: الأدوار النظامية فقط
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope: الأدوار المخصصة فقط
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false)
            ->whereNotNull('org_id');
    }

    /**
     * Scope: الأدوار النشطة فقط
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->whereNull('deleted_at');
    }

    /**
     * Scope: الأدوار لشركة معينة
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $orgId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForOrg($query, string $orgId)
    {
        return $query->where(function ($q) use ($orgId) {
            $q->where('org_id', $orgId)
              ->orWhereNull('org_id'); // الأدوار العامة
        });
    }

    /**
     * التحقق من أن الدور يحتوي على صلاحية معينة
     *
     * @param string $permissionCode
     * @return bool
     */
    public function hasPermission(string $permissionCode): bool
    {
        return $this->permissions()
            ->whereHas('permission', function ($query) use ($permissionCode) {
                $query->where('permission_code', $permissionCode);
            })
            ->where('is_granted', true)
            ->exists();
    }
}
