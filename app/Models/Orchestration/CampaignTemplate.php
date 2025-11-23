<?php

namespace App\Models\Orchestration;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\Core\User;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class CampaignTemplate extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.campaign_templates';
    protected $primaryKey = 'template_id';

    protected $fillable = [
        'template_id',
        'org_id',
        'created_by',
        'name',
        'description',
        'category',
        'objective',
        'platforms',
        'base_config',
        'platform_specific_config',
        'creative_requirements',
        'targeting_template',
        'budget_template',
        'is_global',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'platforms' => 'array',
        'base_config' => 'array',
        'platform_specific_config' => 'array',
        'creative_requirements' => 'array',
        'targeting_template' => 'array',
        'budget_template' => 'array',
        'is_global' => 'boolean',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');

        }
    public function orchestrations(): HasMany
    {
        return $this->hasMany(CampaignOrchestration::class, 'template_id', 'template_id');


        }
    public function incrementUsage(): void
    {
        $this->increment('usage_count');

    public function activate(): void
    {
        $this->update(['is_active' => true]);

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);

    public function isActive(): bool
    {
        return $this->is_active;

        }
    public function isGlobal(): bool
    {
        return $this->is_global;


        }
    public function getPlatformConfig(string $platform): array
    {
        $baseConfig = $this->base_config ?? [];
        $platformSpecific = $this->platform_specific_config[$platform] ?? [];

        return array_merge($baseConfig, $platformSpecific);

        }
    public function supportsPlatform(string $platform): bool
    {
        return in_array($platform, $this->platforms ?? []);

        }
    public function getPlatformCount(): int
    {
        return count($this->platforms ?? []);

        }
    public function getCategoryLabel(): string
    {
        return match($this->category) {
            'awareness' => 'Brand Awareness',
            'consideration' => 'Consideration',
            'conversion' => 'Conversion',
            'retention' => 'Customer Retention',
            default => ucfirst($this->category)
        };

    public function getObjectiveLabel(): string
    {
        return match($this->objective) {
            'brand_awareness' => 'Brand Awareness',
            'reach' => 'Reach',
            'traffic' => 'Traffic',
            'engagement' => 'Engagement',
            'app_installs' => 'App Installs',
            'video_views' => 'Video Views',
            'lead_generation' => 'Lead Generation',
            'conversions' => 'Conversions',
            'catalog_sales' => 'Catalog Sales',
            'store_traffic' => 'Store Traffic',
            default => ucfirst(str_replace('_', ' ', $this->objective))
        };

    public function getBudgetDistribution(): array
    {
        $template = $this->budget_template ?? [];
        $distribution = $template['distribution'] ?? 'equal';

        if ($distribution === 'equal') {
            $platformCount = count($this->platforms ?? []);
            $percentage = $platformCount > 0 ? 100 / $platformCount : 0;

            $result = [];
            foreach ($this->platforms as $platform) {
                $result[$platform] = $percentage;
            return $result;



            }
    public function scopeActive($query)
    {
        return $query->where('is_active', true);

        }
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);

        }
    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);

        }
    public function scopeForObjective($query, string $objective)
    {
        return $query->where('objective', $objective);

        }
    public function scopeForPlatform($query, string $platform)
    {
        return $query->whereJsonContains('platforms', $platform);
}
}
}
}
}
}
}
}
}
