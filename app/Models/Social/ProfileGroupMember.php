<?php

namespace App\Models\Social;

use App\Models\BaseModel;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ProfileGroupMember Model
 *
 * Represents a team member assigned to a profile group with role-based permissions.
 * Junction table between profile_groups and users with additional permission data.
 *
 * @property string $id
 * @property string $profile_group_id
 * @property string $user_id
 * @property string $role
 * @property array $permissions
 * @property string $assigned_by
 * @property \Carbon\Carbon $joined_at
 * @property \Carbon\Carbon|null $last_active_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProfileGroupMember extends BaseModel
{
    use HasFactory;

    protected $table = 'cmis.profile_group_members';
    protected $primaryKey = 'id';

    // This model doesn't use SoftDeletes - members are hard deleted

    protected $fillable = [
        'profile_group_id',
        'user_id',
        'role',
        'permissions',
        'assigned_by',
        'joined_at',
        'last_active_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'joined_at' => 'datetime',
        'last_active_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Available roles for profile group members
     */
    const ROLE_OWNER = 'owner';
    const ROLE_ADMIN = 'admin';
    const ROLE_EDITOR = 'editor';
    const ROLE_CONTRIBUTOR = 'contributor';
    const ROLE_VIEWER = 'viewer';

    /**
     * Get the profile group this member belongs to
     */
    public function profileGroup(): BelongsTo
    {
        return $this->belongsTo(ProfileGroup::class, 'profile_group_id', 'group_id');
    }

    /**
     * Get the user who is this member
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the user who assigned this member to the group
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by', 'user_id');
    }

    /**
     * Scope to filter by role
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to get only owners
     */
    public function scopeOwners($query)
    {
        return $query->where('role', self::ROLE_OWNER);
    }

    /**
     * Scope to get only admins
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    /**
     * Scope to get members with specific permission
     */
    public function scopeWithPermission($query, string $permission)
    {
        return $query->whereRaw("permissions->>'$permission' = 'true'");
    }

    /**
     * Scope to get recently active members
     */
    public function scopeRecentlyActive($query, int $days = 7)
    {
        return $query->where('last_active_at', '>=', now()->subDays($days));
    }

    /**
     * Check if member has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return ($this->permissions[$permission] ?? false) === true;
    }

    /**
     * Grant a permission to this member
     */
    public function grantPermission(string $permission): void
    {
        $permissions = $this->permissions;
        $permissions[$permission] = true;
        $this->permissions = $permissions;
        $this->save();
    }

    /**
     * Revoke a permission from this member
     */
    public function revokePermission(string $permission): void
    {
        $permissions = $this->permissions;
        $permissions[$permission] = false;
        $this->permissions = $permissions;
        $this->save();
    }

    /**
     * Check if member is an owner
     */
    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    /**
     * Check if member is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if member is an editor
     */
    public function isEditor(): bool
    {
        return $this->role === self::ROLE_EDITOR;
    }

    /**
     * Check if member is a contributor
     */
    public function isContributor(): bool
    {
        return $this->role === self::ROLE_CONTRIBUTOR;
    }

    /**
     * Check if member is a viewer
     */
    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    /**
     * Check if member can publish content
     */
    public function canPublish(): bool
    {
        return $this->hasPermission('can_publish');
    }

    /**
     * Check if member can schedule content
     */
    public function canSchedule(): bool
    {
        return $this->hasPermission('can_schedule');
    }

    /**
     * Check if member can manage the team
     */
    public function canManageTeam(): bool
    {
        return $this->hasPermission('can_manage_team');
    }

    /**
     * Check if member requires approval for their posts
     */
    public function requiresApproval(): bool
    {
        return $this->hasPermission('requires_approval');
    }

    /**
     * Update last active timestamp
     */
    public function markActive(): void
    {
        $this->update(['last_active_at' => now()]);
    }

    /**
     * Get all available role options
     */
    public static function getAvailableRoles(): array
    {
        return [
            self::ROLE_OWNER,
            self::ROLE_ADMIN,
            self::ROLE_EDITOR,
            self::ROLE_CONTRIBUTOR,
            self::ROLE_VIEWER,
        ];
    }
}
