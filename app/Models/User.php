<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

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
        'email',
        'name',
        'display_name',
        'role',
        'provider',
        'status',
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
            'email_verified_at' => 'datetime',
            'deleted_at' => 'datetime',
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
}
