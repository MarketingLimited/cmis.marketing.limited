<?php

namespace App\Models\AdPlatform;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.ad_accounts';
    protected $primaryKey = 'ad_account_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id',
        'integration_id',
        'platform',
        'account_name',
        'account_external_id',
        'account_status',
        'currency',
        'timezone',
        'billing_info',
        'spend_limit',
        'capabilities',
        'metadata',
        'last_synced_at',
        'sync_status',
        'is_active',
        'provider',
    ];

    protected $casts = [
        'ad_account_id' => 'string',
        'org_id' => 'string',
        'integration_id' => 'string',
        'billing_info' => 'array',
        'spend_limit' => 'decimal:2',
        'capabilities' => 'array',
        'metadata' => 'array',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the integration
     */
    public function integration()
    {
        return $this->belongsTo(\App\Models\Core\Integration::class, 'integration_id', 'integration_id');
    }

    /**
     * Get ad campaigns
     */
    public function campaigns()
    {
        return $this->hasMany(AdCampaign::class, 'ad_account_id', 'ad_account_id');
    }

    /**
     * Get ad audiences
     */
    public function audiences()
    {
        return $this->hasMany(AdAudience::class, 'ad_account_id', 'ad_account_id');
    }

    /**
     * Scope active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('account_status', 'active');
    }

    /**
     * Scope by platform
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope synced recently
     */
    public function scopeRecentlySynced($query, int $hours = 24)
    {
        return $query->where('last_synced_at', '>=', now()->subHours($hours));
    }

    /**
     * Check if account needs sync
     */
    public function needsSync(int $hours = 1): bool
    {
        if (!$this->last_synced_at) {
            return true;
        }

        return $this->last_synced_at->diffInHours(now()) >= $hours;
    }

    /**
     * Mark as synced
     */
    public function markSynced(string $status = 'success'): void
    {
        $this->update([
            'last_synced_at' => now(),
            'sync_status' => $status,
        ]);
    }
}
