<?php

namespace App\Models\Core;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

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
    protected $table = 'cmis.users';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

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
    public $timestamps = false; // لأن الجدول يستخدم created_at فقط

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'display_name',
        'name',
        'role',
        'status',
        'provider',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'string',
            'email_verified_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the password for the user (not stored in cmis.users).
     * This is for Sanctum compatibility.
     */
    public function getAuthPassword()
    {
        // في CMIS، المصادقة تتم عبر OAuth أو طرق خارجية
        return null;
    }

    /**
     * العلاقة: المستخدم ينتمي لعدة شركات
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function orgs()
    {
        return $this->belongsToMany(Org::class, 'cmis.user_orgs', 'user_id', 'org_id')
            ->withPivot('role_id', 'is_active', 'joined_at', 'last_accessed', 'invited_by')
            ->wherePivot('is_active', true)
            ->wherePivotNull('deleted_at')
            ->using(UserOrg::class);
    }

    /**
     * العلاقة: الأدوار المباشرة للمستخدم
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userOrgs()
    {
        return $this->hasMany(UserOrg::class, 'user_id');
    }

    /**
     * الحصول على دور المستخدم في شركة معينة
     *
     * @param string $orgId
     * @return string|null
     */
    public function getRoleInOrg(string $orgId): ?string
    {
        $userOrg = $this->orgs()
            ->where('org_id', $orgId)
            ->first();

        return $userOrg ? $userOrg->pivot->role_id : null;
    }

    /**
     * التحقق من صلاحية المستخدم في شركة
     *
     * @param string $orgId
     * @return bool
     */
    public function hasAccessToOrg(string $orgId): bool
    {
        return $this->orgs()
            ->where('org_id', $orgId)
            ->exists();
    }

    /**
     * التحقق من أن المستخدم لديه دور معين في شركة
     *
     * @param string $orgId
     * @param string $roleCode
     * @return bool
     */
    public function hasRoleInOrg(string $orgId, string $roleCode): bool
    {
        return $this->userOrgs()
            ->where('org_id', $orgId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereHas('role', function ($query) use ($roleCode) {
                $query->where('role_code', $roleCode);
            })
            ->exists();
    }

    /**
     * التحقق من أن المستخدم لديه صلاحية معينة في شركة
     *
     * @param string $orgId
     * @param string $permission
     * @return bool
     */
    public function can(string $permission, string $orgId): bool
    {
        // يمكن تطويرها لاحقاً للتحقق من الصلاحيات المفصلة
        return $this->hasAccessToOrg($orgId);
    }

    /**
     * Scope: المستخدمون النشطون فقط
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->whereNull('deleted_at');
    }

    /**
     * Scope: البحث عن مستخدمين
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('email', 'ilike', "%{$search}%")
              ->orWhere('display_name', 'ilike', "%{$search}%")
              ->orWhere('name', 'ilike', "%{$search}%");
        });
    }
}
