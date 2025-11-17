<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Security\Permission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (app()->environment('testing')) {
            $this->setTable('users');
            $this->primaryKey = 'id';
            $this->incrementing = true;
            $this->keyType = 'int';
        }
    }

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (app()->environment('testing')) {
                return;
            }

            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

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
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'email',
        'password',
        'role',
        'status',
        'current_org_id',
        'email_verified_at',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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
            'deleted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the organizations that the user belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function orgs(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Core\Org::class,
            'cmis.user_orgs',
            'user_id',
            'org_id'
        )
        ->withPivot(['role_id', 'is_active', 'invited_at', 'joined_at'])
        ->withTimestamps()
        ->wherePivot('is_active', true)
        ->wherePivotNull('deleted_at');
    }

    /**
     * Check if user has a specific role in an organization.
     *
     * @param string $orgId
     * @param string $roleCode
     * @return bool
     */
    public function hasRoleInOrg(string $orgId, string $roleCode): bool
    {
        return $this->orgs()
            ->where('cmis.orgs.org_id', $orgId)
            ->whereHas('roles', function($query) use ($roleCode) {
                $query->where('role_code', $roleCode);
            })
            ->exists();
    }

    /**
     * Check if user belongs to an organization.
     *
     * @param string $orgId
     * @return bool
     */
    public function belongsToOrg(string $orgId): bool
    {
        return $this->orgs()->where('cmis.orgs.org_id', $orgId)->exists();
    }

    /**
     * Get the user's direct permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'cmis.user_permissions',
            'user_id',
            'permission_id'
        )
            ->withPivot('is_granted', 'expires_at', 'granted_by')
            ->withTimestamps();
    }

    /**
     * Check if user has a specific permission using DB function.
     *
     * @param string $permissionCode
     * @return bool
     */
    public function hasPermission(string $permissionCode): bool
    {
        $orgId = session('current_org_id');
        if (!$orgId) {
            return false;
        }

        try {
            $result = \DB::selectOne(
                'SELECT cmis.check_permission(?, ?, ?) as has_permission',
                [$this->id, $orgId, $permissionCode]
            );

            return (bool) $result->has_permission;
        } catch (\Exception $e) {
            \Log::error('Permission check failed', [
                'user_id' => $this->id,
                'org_id' => $orgId,
                'permission' => $permissionCode,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if user can perform an action (integrate with Laravel's Gate).
     *
     * @param string $ability
     * @param mixed $arguments
     * @return bool
     */
    public function can($ability, $arguments = [])
    {
        // If it's a CMIS permission, use the DB function
        if (str_starts_with($ability, 'cmis.')) {
            return $this->hasPermission($ability);
        }

        // Otherwise, use Laravel's default authorization
        return parent::can($ability, $arguments);
    }
}
