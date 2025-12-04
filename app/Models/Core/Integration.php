<?php

namespace App\Models\Core;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Integration extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.integrations';

    protected $primaryKey = 'integration_id';

    public $timestamps = true;

    protected $fillable = [
        'org_id',
        'platform',
        'account_id',
        'username',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'token_refreshed_at',
        'is_active',
        'business_id',
        'created_by',
        'updated_by',
        'provider',
        'last_synced_at',
        'sync_status',
        'sync_errors',
        'sync_retry_count',
        'metadata', // JSONB field for platform-specific data
        // Profile management fields
        'profile_group_id',
        'account_name',
        'platform_handle',
        'avatar_url',
        'status',
        'industry',
        'custom_fields',
        'is_enabled',
        'display_name',
        'bio',
        'website_url',
        'profile_type',
        'connected_by',
        'auto_boost_enabled',
        'timezone',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'integration_id' => 'string',
        'org_id' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
        'is_active' => 'boolean',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'token_refreshed_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'sync_errors' => 'array',
        'metadata' => 'array', // JSONB field for platform-specific data
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        // Profile management casts
        'profile_group_id' => 'string',
        'custom_fields' => 'array',
        'is_enabled' => 'boolean',
        'connected_by' => 'string',
        'auto_boost_enabled' => 'boolean',
    ];

    // ===== Profile Management Relationships =====

    /**
     * Get the profile group this integration belongs to.
     */
    public function profileGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Social\ProfileGroup::class, 'profile_group_id', 'group_id');
    }

    /**
     * Get the user who connected this integration.
     */
    public function connectedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Core\User::class, 'connected_by', 'user_id');
    }

    /**
     * Get the queue settings for this integration.
     */
    public function queueSettings()
    {
        return $this->hasOne(\App\Models\Social\IntegrationQueueSettings::class, 'integration_id', 'integration_id');
    }

    /**
     * Get the boost rules that apply to this integration.
     * Note: Boost rules are linked via profile_group_id, but can filter by integration.
     */
    public function boostRules()
    {
        return $this->hasMany(\App\Models\Platform\BoostRule::class, 'profile_group_id', 'profile_group_id');
    }

    // ===== Profile Management Scopes =====

    /**
     * Scope to get only enabled profiles.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope to filter by platform.
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to filter by profile group.
     */
    public function scopeInGroup($query, string $groupId)
    {
        return $query->where('profile_group_id', $groupId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to search by name or username.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('account_name', 'ilike', "%{$search}%")
              ->orWhere('display_name', 'ilike', "%{$search}%")
              ->orWhere('username', 'ilike', "%{$search}%")
              ->orWhere('platform_handle', 'ilike', "%{$search}%");
        });
    }

    // ===== Profile Management Accessors =====

    /**
     * Get the display name (prefers display_name, falls back to account_name).
     */
    public function getEffectiveNameAttribute(): string
    {
        return $this->display_name ?: $this->account_name ?: $this->username ?: 'Unknown';
    }

    /**
     * Get the effective status for display.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active', 'connected' => 'active',
            'inactive', 'disconnected' => 'inactive',
            'error', 'failed' => 'error',
            default => $this->status ?? 'active',
        };
    }

    /**
     * Check if profile is active and enabled.
     */
    public function isActiveAndEnabled(): bool
    {
        return $this->is_active && $this->is_enabled && $this->status !== 'error';
    }

    /**
     * Get the effective timezone for this profile.
     * Inheritance hierarchy: Profile -> Profile Group -> Organization -> UTC
     */
    public function getEffectiveTimezoneAttribute(): string
    {
        // Profile's own timezone
        if ($this->timezone) {
            return $this->timezone;
        }

        // Profile Group's timezone
        if ($this->profileGroup && $this->profileGroup->timezone) {
            return $this->profileGroup->timezone;
        }

        // Organization's timezone (uses HasOrganization trait's org() relationship)
        if ($this->org && $this->org->timezone) {
            return $this->org->timezone;
        }

        return 'UTC';
    }

    /**
     * Get the timezone inheritance source for display.
     * Returns the name of where the timezone is inherited from.
     */
    public function getTimezoneSourceAttribute(): ?string
    {
        if ($this->timezone) {
            return null; // No inheritance, profile has its own timezone
        }

        if ($this->profileGroup && $this->profileGroup->timezone) {
            return $this->profileGroup->name;
        }

        if ($this->org) {
            return $this->org->name ?? __('Organization');
        }

        return __('System Default');
    }

    // ===== Existing Relationships =====

    /**
     * Get the user who created this integration.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');
    }

    /**
     * Get ad campaigns associated with this integration
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function adCampaigns()
    {
        return $this->hasMany(\App\Models\AdPlatform\AdCampaign::class, 'integration_id', 'integration_id');
    }

    /**
     * Get ad accounts associated with this integration
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function adAccounts()
    {
        return $this->hasMany(\App\Models\AdPlatform\AdAccount::class, 'integration_id', 'integration_id');
    }

    /**
     * Get ad sets associated with this integration
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function adSets()
    {
        return $this->hasMany(\App\Models\AdPlatform\AdSet::class, 'integration_id', 'integration_id');
    }

    /**
     * Get ad entities associated with this integration
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function adEntities()
    {
        return $this->hasMany(\App\Models\AdPlatform\AdEntity::class, 'integration_id', 'integration_id');
    }

    /**
     * Check if the access token is expired or about to expire
     *
     * @param int $minutesBuffer Buffer time before actual expiration (default: 10 minutes)
     * @return bool
     */
    public function isTokenExpired(int $minutesBuffer = 10): bool
    {
        if (!$this->token_expires_at) {
            return false; // If no expiration set, assume token is valid
        }

        return $this->token_expires_at->subMinutes($minutesBuffer)->isPast();
    }

    /**
     * Check if token needs refresh (expired or about to expire)
     *
     * @return bool
     */
    public function needsTokenRefresh(): bool
    {
        return $this->isTokenExpired(10); // Refresh if less than 10 minutes remaining
    }

    /**
     * Refresh the access token using the refresh token
     *
     * @return bool
     * @throws \Exception
     */
    public function refreshAccessToken(): bool
    {
        if (!$this->refresh_token) {
            Log::warning("No refresh token available for integration {$this->integration_id}");
            return false;
        }

        try {
            // Platform-specific token refresh logic
            $tokenData = $this->performTokenRefresh();

            if (!$tokenData) {
                throw new \Exception("Token refresh failed");
            }

            // Update the integration with new tokens
            $this->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? $this->refresh_token,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
                'token_refreshed_at' => now(),
            ]);

            Log::info("Token refreshed successfully for integration {$this->integration_id}");

            return true;

        } catch (\Exception $e) {
            Log::error("Token refresh failed for integration {$this->integration_id}: {$e->getMessage()}");

            // Update sync status to indicate token issue
            $this->update([
                'sync_status' => 'failed',
                'sync_errors' => ['token_refresh_failed' => $e->getMessage()],
            ]);

            return false;
        }
    }

    /**
     * Perform platform-specific token refresh
     * Override this method in platform-specific services
     *
     * @return array|null
     */
    protected function performTokenRefresh(): ?array
    {
        $refreshUrls = [
            'google' => 'https://oauth2.googleapis.com/token',
            'meta' => 'https://graph.facebook.com/v18.0/oauth/access_token',
            'tiktok' => 'https://business-api.tiktok.com/open_api/v1.3/oauth2/refresh_token/',
            'linkedin' => 'https://www.linkedin.com/oauth/v2/accessToken',
            'twitter' => 'https://api.twitter.com/2/oauth2/token',
        ];

        $url = $refreshUrls[$this->provider] ?? null;

        if (!$url) {
            Log::warning("No refresh URL configured for provider: {$this->provider}");
            return null;
        }

        // Platform-specific request parameters
        $params = $this->getRefreshTokenParams();

        $response = Http::asForm()->post($url, $params);

        if ($response->failed()) {
            Log::error("Token refresh HTTP request failed: " . $response->body());
            return null;
        }

        return $response->json();
    }

    /**
     * Get platform-specific parameters for token refresh
     *
     * @return array
     */
    protected function getRefreshTokenParams(): array
    {
        $baseParams = [
            'refresh_token' => $this->refresh_token,
            'grant_type' => 'refresh_token',
        ];

        // Add platform-specific client credentials from config
        $platformConfig = config("services.{$this->provider}");

        if ($platformConfig) {
            $baseParams['client_id'] = $platformConfig['client_id'] ?? null;
            $baseParams['client_secret'] = $platformConfig['client_secret'] ?? null;
        }

        return array_filter($baseParams); // Remove null values
    }

    /**
     * Update sync status
     *
     * @param string $status
     * @param array|null $errors
     * @return void
     */
    public function updateSyncStatus(string $status, ?array $errors = null): void
    {
        $this->update([
            'sync_status' => $status,
            'sync_errors' => $errors,
            'last_synced_at' => $status === 'success' ? now() : $this->last_synced_at,
            'sync_retry_count' => $status === 'failed' ? $this->sync_retry_count + 1 : 0,
        ]);
    }
}
