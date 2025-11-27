<?php

namespace App\Models;

use App\Models\Concerns\HasOrganization;
use App\Models\BaseModel;
use App\Models\Social\ProfileGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Integration Model
 *
 * Represents a social media platform integration/connection.
 * Can be associated with a ProfileGroup for organizational purposes.
 *
 * @property string $integration_id
 * @property string $org_id
 * @property string|null $profile_group_id
 * @property string $platform
 * @property string $account_id
 * @property string $account_name
 * @property string|null $account_username
 * @property string|null $platform_handle
 * @property string|null $avatar_url
 * @property string $status
 * @property bool $is_active
 */
class Integration extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.integrations';
    protected $primaryKey = 'integration_id';

    protected $fillable = [
        'integration_id',
        'org_id',
        'profile_group_id',
        'platform',
        'account_id',
        'account_name',
        'account_username',
        'username',
        'platform_handle',
        'avatar_url',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'expires_at',
        'is_active',
        'status',
        'scopes',
        'metadata',
    ];

    protected $casts = [
        'scopes' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the profile group this integration belongs to
     */
    public function profileGroup(): BelongsTo
    {
        return $this->belongsTo(ProfileGroup::class, 'profile_group_id', 'group_id');
    }

    /**
     * Check if this integration is connected and active
     */
    public function isConnected(): bool
    {
        return $this->is_active && $this->status === 'active';
    }

    /**
     * Check if the token needs refresh
     */
    public function needsTokenRefresh(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }
        // Refresh if token expires within the next 5 minutes
        return $this->token_expires_at->subMinutes(5)->isPast();
    }

    /**
     * Get the display name for this integration
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->account_name ?: $this->account_username ?: $this->platform_handle ?: $this->platform;
    }

    /**
     * Scope to filter by platform
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to get only active integrations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    /**
     * Scope to filter by profile group
     */
    public function scopeInProfileGroup($query, string $profileGroupId)
    {
        return $query->where('profile_group_id', $profileGroupId);
    }
}
