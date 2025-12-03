<?php

namespace App\Models\Platform;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
class PlatformConnection extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.platform_connections';
    protected $primaryKey = 'connection_id';

    protected $fillable = [
        'connection_id',
        'org_id',
        'platform',
        'account_id',
        'account_name',
        'status',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'scopes',
        'account_metadata',
        'last_sync_at',
        'last_error_at',
        'last_error_message',
        'auto_sync',
        'sync_frequency_minutes',
    ];

    protected $casts = [
        'scopes' => 'array',
        'account_metadata' => 'array',
        'token_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'last_error_at' => 'datetime',
        'auto_sync' => 'boolean',
        'sync_frequency_minutes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    // ===== Relationships =====

    

    // ===== Token Management =====

    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAccessTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setRefreshTokenAttribute($value): void
    {
        $this->attributes['refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getRefreshTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function isTokenExpired(): bool
    {
        $expiresAt = $this->token_expires_at ?? $this->expires_at;
        if (!$expiresAt) {
            return false;
        }
        return now()->isAfter($expiresAt);
    }

    public function isTokenExpiringSoon(int $minutes = 10): bool
    {
        $expiresAt = $this->token_expires_at ?? $this->expires_at;
        if (!$expiresAt) {
            return false;
        }
        return now()->addMinutes($minutes)->isAfter($expiresAt);
    }

    // ===== Connection Status =====

    public function markAsActive(): void
    {
        $this->update([
            'status' => 'active',
            'last_error_at' => null,
            'last_error_message' => null
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'expired',
        ]);
    }

    public function markAsError(string $errorMessage): void
    {
        $this->update([
            'status' => 'error',
            'last_error_at' => now(),
            'last_error_message' => $errorMessage
        ]);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isTokenExpired();
    }

    // ===== Sync Management =====

    public function markSynced(): void
    {
        $this->update(['last_sync_at' => now()]);
    }

    public function shouldSync(): bool
    {
        $autoSync = $this->auto_sync ?? true;
        if (!$autoSync || !$this->isActive()) {
            return false;
        }

        if (!$this->last_sync_at) {
            return true;
        }

        $frequency = $this->sync_frequency_minutes ?? 15;
        $nextSync = $this->last_sync_at->addMinutes($frequency);
        return now()->isAfter($nextSync);
    }

    // ===== Platform Helpers =====

    public function getPlatformName(): string
    {
        return match($this->platform) {
            'meta' => 'Meta (Facebook/Instagram)',
            'google' => 'Google Ads',
            'tiktok' => 'TikTok Ads',
            'linkedin' => 'LinkedIn Ads',
            'twitter' => 'Twitter (X) Ads',
            'snapchat' => 'Snapchat Ads',
            default => ucfirst($this->platform)
        };
    }

    // ===== Scopes =====

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeNeedingSync($query)
    {
        return $query->where('auto_sync', true)
            ->where(function ($q) {
                $q->whereNull('last_sync_at')
                    ->orWhereRaw('last_sync_at + (sync_frequency_minutes || \' minutes\')::interval < NOW()');
            });
    }

    public function scopeExpiredTokens($query)
    {
        return $query->where('token_expires_at', '<', now())
            ->whereNotNull('token_expires_at');
    }
}
