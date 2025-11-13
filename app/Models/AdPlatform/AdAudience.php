<?php

namespace App\Models\AdPlatform;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdAudience extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.ad_audiences';
    protected $primaryKey = 'id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'org_id',
        'integration_id',
        'entity_level',
        'entity_external_id',
        'audience_type',
        'platform',
        'demographics',
        'interests',
        'behaviors',
        'location',
        'keywords',
        'custom_audience',
        'lookalike_audience',
        'advantage_plus_settings',
        'provider',
    ];

    protected $casts = ['ad_audience_id' => 'string',
        'ad_account_id' => 'string',
        'audience_size' => 'integer',
        'targeting_spec' => 'array',
        'exclusions' => 'array',
        'lookalike_ratio' => 'float',
        'custom_audience_source' => 'array',
        'retention_days' => 'integer',
        'metadata' => 'array',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'demographics' => 'array',
        'interests' => 'array',
        'behaviors' => 'array',
        'location' => 'array',
        'keywords' => 'array',
        'custom_audience' => 'array',
        'lookalike_audience' => 'array',
        'advantage_plus_settings' => 'array',
    ];

    /**
     * Get the ad account
     */
    public function adAccount()
    {
        return $this->belongsTo(AdAccount::class, 'ad_account_id', 'ad_account_id');
    }

    /**
     * Scope by platform
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope by audience type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('audience_type', $type);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope active audiences
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope custom audiences
     */
    public function scopeCustom($query)
    {
        return $query->where('audience_type', 'custom');
    }

    /**
     * Scope lookalike audiences
     */
    public function scopeLookalike($query)
    {
        return $query->where('audience_type', 'lookalike');
    }

    /**
     * Scope by minimum size
     */
    public function scopeMinimumSize($query, int $size)
    {
        return $query->where('audience_size', '>=', $size);
    }

    /**
     * Check if audience is ready
     */
    public function isReady(): bool
    {
        return $this->status === 'active' && $this->audience_size > 0;
    }

    /**
     * Check if audience needs refresh
     */
    public function needsRefresh(int $hours = 24): bool
    {
        if (!$this->last_synced_at) {
            return true;
        }

        return $this->last_synced_at->diffInHours(now()) >= $hours;
    }
}
