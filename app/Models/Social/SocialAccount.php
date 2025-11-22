<?php

namespace App\Models\Social;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\Integration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialAccount extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.social_accounts';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'org_id',
        'integration_id',
        'account_external_id',
        'username',
        'display_name',
        'profile_picture_url',
        'biography',
        'followers_count',
        'follows_count',
        'media_count',
        'website',
        'category',
        'fetched_at',
        'provider',
    ];

    protected $casts = [
        'id' => 'string',
        'org_id' => 'string',
        'integration_id' => 'string',
        'followers_count' => 'integer',
        'follows_count' => 'integer',
        'media_count' => 'integer',
        'fetched_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the integration that this account belongs to.
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class, 'integration_id', 'integration_id');
    }

    /**
     * Get all posts for this social account.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(SocialPost::class, 'integration_id', 'integration_id');
    }

    /**
     * Get all metrics for this social account.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(SocialAccountMetric::class, 'account_id', 'id');
    }

    /**
     * Scope to filter by provider/platform
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to filter by integration
     */
    public function scopeForIntegration($query, string $integrationId)
    {
        return $query->where('integration_id', $integrationId);
    }

    /**
     * Get the account's follower growth rate
     */
    public function getFollowerGrowthRate(int $days = 30): float
    {
        $metrics = $this->metrics()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at')
            ->get(['followers_count', 'created_at']);

        if ($metrics->count() < 2) {
            return 0.0;
        }

        $first = $metrics->first();
        $last = $metrics->last();

        if ($first->followers_count == 0) {
            return 0.0;
        }

        return (($last->followers_count - $first->followers_count) / $first->followers_count) * 100;
    }

    /**
     * Check if the account is verified (if supported by platform)
     */
    public function isVerified(): bool
    {
        // This would typically come from platform-specific data
        // For now, return false - can be extended based on provider
        return false;
    }
}
