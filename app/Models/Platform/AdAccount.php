<?php

namespace App\Models\Platform;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use App\Models\Social\ProfileGroup;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * AdAccount Model
 *
 * Represents a connected advertising account from various platforms (Meta, Google, TikTok, etc.).
 * Stores OAuth tokens, account details, and connection status.
 *
 * @property string $ad_account_id
 * @property string $org_id
 * @property string|null $profile_group_id
 * @property string $platform
 * @property string $platform_account_id
 * @property string $account_name
 * @property string $currency
 * @property string $status
 * @property string $connection_status
 * @property float|null $balance
 * @property float|null $daily_spend_limit
 * @property string $access_token_encrypted
 * @property string|null $refresh_token_encrypted
 * @property \Carbon\Carbon|null $token_expires_at
 * @property string $connected_by
 * @property \Carbon\Carbon $connected_at
 * @property \Carbon\Carbon|null $last_synced_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class AdAccount extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis.ad_accounts';
    protected $primaryKey = 'id';

    protected $fillable = [
        'org_id',
        'profile_group_id',
        'platform',
        'platform_account_id',
        'account_name',
        'currency',
        'timezone',
        'status',
        'connection_status',
        'balance',
        'daily_spend_limit',
        'daily_budget_limit',
        'monthly_budget_limit',
        'is_active',
        'access_token_encrypted',
        'refresh_token_encrypted',
        'token_expires_at',
        'connected_by',
        'connected_at',
        'last_synced_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'daily_spend_limit' => 'decimal:2',
        'token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token_encrypted',
        'refresh_token_encrypted',
    ];

    /**
     * Platform constants
     */
    const PLATFORM_META = 'meta';
    const PLATFORM_GOOGLE = 'google';
    const PLATFORM_TIKTOK = 'tiktok';
    const PLATFORM_LINKEDIN = 'linkedin';
    const PLATFORM_TWITTER = 'twitter';
    const PLATFORM_SNAPCHAT = 'snapchat';
    const PLATFORM_PINTEREST = 'pinterest';

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_DISCONNECTED = 'disconnected';
    const STATUS_ERROR = 'error';

    /**
     * Connection status constants
     */
    const CONNECTION_CONNECTED = 'connected';
    const CONNECTION_NEEDS_REAUTH = 'needs_reauth';
    const CONNECTION_EXPIRED = 'expired';

    /**
     * Get the profile group this ad account belongs to
     */
    public function profileGroup(): BelongsTo
    {
        return $this->belongsTo(ProfileGroup::class, 'profile_group_id', 'group_id');
    }

    /**
     * Get the user who connected this ad account
     */
    public function connector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by', 'user_id');
    }

    /**
     * Get all boost rules using this ad account
     */
    public function boostRules(): HasMany
    {
        return $this->hasMany(BoostRule::class, 'ad_account_id', 'ad_account_id');
    }

    /**
     * Scope to filter by platform
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to get only active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get only connected accounts
     */
    public function scopeConnected($query)
    {
        return $query->where('connection_status', self::CONNECTION_CONNECTED);
    }

    /**
     * Scope to get accounts needing reauth
     */
    public function scopeNeedsReauth($query)
    {
        return $query->where('connection_status', self::CONNECTION_NEEDS_REAUTH);
    }

    /**
     * Scope to get accounts with expired tokens
     */
    public function scopeExpired($query)
    {
        return $query->where('connection_status', self::CONNECTION_EXPIRED)
            ->orWhere('token_expires_at', '<', now());
    }

    /**
     * Get decrypted access token
     */
    public function getAccessTokenAttribute(): ?string
    {
        if (empty($this->access_token_encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->access_token_encrypted);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            \Log::warning('AdAccount access token decryption failed', [
                'ad_account_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Set encrypted access token
     */
    public function setAccessTokenAttribute(string $value): void
    {
        $this->attributes['access_token_encrypted'] = Crypt::encryptString($value);
    }

    /**
     * Get decrypted refresh token
     */
    public function getRefreshTokenAttribute(): ?string
    {
        if (!$this->refresh_token_encrypted) {
            return null;
        }

        return Crypt::decryptString($this->refresh_token_encrypted);
    }

    /**
     * Set encrypted refresh token
     */
    public function setRefreshTokenAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['refresh_token_encrypted'] = null;
            return;
        }

        $this->attributes['refresh_token_encrypted'] = Crypt::encryptString($value);
    }

    /**
     * Check if account is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if account is connected
     */
    public function isConnected(): bool
    {
        return $this->connection_status === self::CONNECTION_CONNECTED;
    }

    /**
     * Check if account needs reauthorization
     */
    public function needsReauth(): bool
    {
        return $this->connection_status === self::CONNECTION_NEEDS_REAUTH;
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Mark account as needing reauth
     */
    public function markNeedsReauth(): void
    {
        $this->update(['connection_status' => self::CONNECTION_NEEDS_REAUTH]);
    }

    /**
     * Mark account as disconnected
     */
    public function markDisconnected(): void
    {
        $this->update([
            'status' => self::STATUS_DISCONNECTED,
            'connection_status' => self::CONNECTION_EXPIRED,
        ]);
    }

    /**
     * Update sync timestamp
     */
    public function markSynced(): void
    {
        $this->update(['last_synced_at' => now()]);
    }

    /**
     * Get all available platforms
     */
    public static function getAvailablePlatforms(): array
    {
        return [
            self::PLATFORM_META,
            self::PLATFORM_GOOGLE,
            self::PLATFORM_TIKTOK,
            self::PLATFORM_LINKEDIN,
            self::PLATFORM_TWITTER,
            self::PLATFORM_SNAPCHAT,
            self::PLATFORM_PINTEREST,
        ];
    }

    /**
     * Get platform display name
     */
    public function getPlatformNameAttribute(): string
    {
        $names = [
            self::PLATFORM_META => 'Meta (Facebook & Instagram)',
            self::PLATFORM_GOOGLE => 'Google Ads',
            self::PLATFORM_TIKTOK => 'TikTok Ads',
            self::PLATFORM_LINKEDIN => 'LinkedIn Ads',
            self::PLATFORM_TWITTER => 'Twitter Ads',
            self::PLATFORM_SNAPCHAT => 'Snapchat Ads',
            self::PLATFORM_PINTEREST => 'Pinterest Ads',
        ];

        return $names[$this->platform] ?? ucfirst($this->platform);
    }

    /**
     * Check if account has sufficient balance
     */
    public function hasSufficientBalance(float $amount): bool
    {
        if ($this->balance === null) {
            return true; // Assume sufficient if balance not tracked
        }

        return $this->balance >= $amount;
    }
}
