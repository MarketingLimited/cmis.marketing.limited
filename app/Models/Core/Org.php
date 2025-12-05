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
        'timezone',
        // Status fields
        'status',
        // Suspension fields
        'suspended_at',
        'suspended_by',
        'suspension_reason',
        // Block fields
        'blocked_at',
        'blocked_by',
        'block_reason',
    ];

    protected $casts = [
        'org_id' => 'string',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
        'suspended_at' => 'datetime',
        'blocked_at' => 'datetime',
    ];

    /**
     * Organization status constants.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_BLOCKED = 'blocked';

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

    /**
     * Get the subscription for this organization.
     */
    public function subscription()
    {
        return $this->hasOne(\App\Models\Subscription\Subscription::class, 'org_id', 'org_id')
            ->where('status', 'active')
            ->latest();
    }

    /**
     * Get all subscriptions for this organization.
     */
    public function subscriptions()
    {
        return $this->hasMany(\App\Models\Subscription\Subscription::class, 'org_id', 'org_id');
    }

    // ===== Status Methods =====

    /**
     * Check if organization is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if organization is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Check if organization is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    /**
     * Check if organization is restricted (suspended or blocked).
     */
    public function isRestricted(): bool
    {
        return $this->isSuspended() || $this->isBlocked();
    }

    /**
     * Suspend the organization.
     */
    public function suspend(string $reason, ?string $suspendedBy = null): void
    {
        $this->update([
            'status' => self::STATUS_SUSPENDED,
            'suspended_at' => now(),
            'suspended_by' => $suspendedBy,
            'suspension_reason' => $reason,
        ]);
    }

    /**
     * Unsuspend the organization.
     */
    public function unsuspend(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'suspended_at' => null,
            'suspended_by' => null,
            'suspension_reason' => null,
        ]);
    }

    /**
     * Block the organization (permanent).
     */
    public function block(string $reason, ?string $blockedBy = null): void
    {
        $this->update([
            'status' => self::STATUS_BLOCKED,
            'blocked_at' => now(),
            'blocked_by' => $blockedBy,
            'block_reason' => $reason,
        ]);
    }

    /**
     * Unblock the organization.
     */
    public function unblock(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'blocked_at' => null,
            'blocked_by' => null,
            'block_reason' => null,
        ]);
    }

    /**
     * Restore organization to active status.
     */
    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'suspended_at' => null,
            'suspended_by' => null,
            'suspension_reason' => null,
            'blocked_at' => null,
            'blocked_by' => null,
            'block_reason' => null,
        ]);
    }

    /**
     * Get the user who suspended this organization.
     */
    public function suspendedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'suspended_by', 'user_id');
    }

    /**
     * Get the user who blocked this organization.
     */
    public function blockedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'blocked_by', 'user_id');
    }

    // ===== Scopes =====

    /**
     * Scope to get only active organizations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get suspended organizations.
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', self::STATUS_SUSPENDED);
    }

    /**
     * Scope to get blocked organizations.
     */
    public function scopeBlocked($query)
    {
        return $query->where('status', self::STATUS_BLOCKED);
    }

    /**
     * Scope to get restricted organizations (suspended or blocked).
     */
    public function scopeRestricted($query)
    {
        return $query->whereIn('status', [self::STATUS_SUSPENDED, self::STATUS_BLOCKED]);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
