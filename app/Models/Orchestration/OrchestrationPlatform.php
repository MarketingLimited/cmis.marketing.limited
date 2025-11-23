<?php

namespace App\Models\Orchestration;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\Platform\PlatformConnection;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class OrchestrationPlatform extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.orchestration_platforms';
    protected $primaryKey = 'platform_mapping_id';

    protected $fillable = [
        'platform_mapping_id',
        'org_id',
        'orchestration_id',
        'connection_id',
        'platform',
        'platform_campaign_id',
        'platform_campaign_name',
        'status',
        'platform_config',
        'allocated_budget',
        'spend',
        'impressions',
        'clicks',
        'conversions',
        'revenue',
        'created_on_platform_at',
        'last_synced_at',
        'last_error_message',
        'sync_metadata',
    ];

    protected $casts = [
        'platform_config' => 'array',
        'sync_metadata' => 'array',
        'allocated_budget' => 'decimal:2',
        'spend' => 'decimal:2',
        'revenue' => 'decimal:2',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'created_on_platform_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    

    public function orchestration(): BelongsTo
    {
        return $this->belongsTo(CampaignOrchestration::class, 'orchestration_id', 'orchestration_id');

        }
    public function connection(): BelongsTo
    {
        return $this->belongsTo(PlatformConnection::class, 'connection_id', 'connection_id');


        }
    public function markAsCreating(): void
    {
        $this->update(['status' => 'creating']);

    public function markAsActive(string $platformCampaignId, ?string $platformCampaignName = null): void
    {
        $this->update([
            'status' => 'active',
            'platform_campaign_id' => $platformCampaignId,
            'platform_campaign_name' => $platformCampaignName,
            'created_on_platform_at' => now(),
        ]);

    public function markAsPaused(): void
    {
        $this->update(['status' => 'paused']);

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'last_error_message' => $errorMessage,
        ]);

    public function markSynced(): void
    {
        $this->update(['last_synced_at' => now()]);

    // ===== Performance Methods =====

    public function updateMetrics(array $metrics): void
    {
        $this->update([
            'spend' => $metrics['spend'] ?? $this->spend,
            'impressions' => $metrics['impressions'] ?? $this->impressions,
            'clicks' => $metrics['clicks'] ?? $this->clicks,
            'conversions' => $metrics['conversions'] ?? $this->conversions,
            'revenue' => $metrics['revenue'] ?? $this->revenue,
        ]);

    public function getCTR(): float
    {
        return $this->impressions > 0 ? ($this->clicks / $this->impressions) * 100 : 0;

        }
    public function getConversionRate(): float
    {
        return $this->clicks > 0 ? ($this->conversions / $this->clicks) * 100 : 0;

        }
    public function getCPA(): float
    {
        return $this->conversions > 0 ? $this->spend / $this->conversions : 0;

        }
    public function getROAS(): float
    {
        return $this->spend > 0 ? $this->revenue / $this->spend : 0;

        }
    public function getPlatformLabel(): string
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

    // ===== Scopes =====

    public function scopeActive($query)
    {
        return $query->where('status', 'active');

        }
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
}
}
}
}
}
}
}
}
}
