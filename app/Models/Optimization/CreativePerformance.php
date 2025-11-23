<?php

namespace App\Models\Optimization;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\Campaign\Campaign;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CreativePerformance extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.creative_performance';
    protected $primaryKey = 'performance_id';

    protected $fillable = [
        'performance_id',
        'org_id',
        'campaign_id',
        'creative_id',
        'creative_type',
        'creative_url',
        'creative_metadata',
        'impressions',
        'clicks',
        'conversions',
        'spend',
        'revenue',
        'ctr',
        'cvr',
        'cpc',
        'cpa',
        'roas',
        'engagement_rate',
        'video_view_rate',
        'completion_rate',
        'visual_features',
        'text_features',
        'performance_score',
        'fatigue_score',
        'freshness_days',
        'recommendation',
        'recommendation_confidence',
        'analyzed_at',
    ];

    protected $casts = [
        'creative_metadata' => 'array',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'spend' => 'decimal:2',
        'revenue' => 'decimal:2',
        'ctr' => 'float',
        'cvr' => 'float',
        'cpc' => 'decimal:2',
        'cpa' => 'decimal:2',
        'roas' => 'float',
        'engagement_rate' => 'float',
        'video_view_rate' => 'float',
        'completion_rate' => 'float',
        'visual_features' => 'array',
        'text_features' => 'array',
        'performance_score' => 'float',
        'fatigue_score' => 'float',
        'freshness_days' => 'integer',
        'recommendation_confidence' => 'float',
        'analyzed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');


        }
    public function calculatePerformanceScore(): float
    {
        $weights = [
            'roas' => 0.3,
            'cvr' => 0.25,
            'ctr' => 0.2,
            'engagement_rate' => 0.15,
            'freshness' => 0.1,
        ];

        $scores = [];

        // Normalize ROAS (assume good ROAS is 3.0+)
        if ($this->roas !== null) {
            $scores['roas'] = min($this->roas / 3.0, 1.0);

        // CVR is already a percentage
        if ($this->cvr !== null) {
            $scores['cvr'] = min($this->cvr * 10, 1.0); // Assume 10% CVR is excellent

        // CTR is already a percentage
        if ($this->ctr !== null) {
            $scores['ctr'] = min($this->ctr * 5, 1.0); // Assume 20% CTR is excellent

        // Engagement rate
        if ($this->engagement_rate !== null) {
            $scores['engagement_rate'] = min($this->engagement_rate * 5, 1.0);

        // Freshness (inverse of fatigue)
        if ($this->freshness_days !== null) {
            $scores['freshness'] = max(1.0 - ($this->freshness_days / 90), 0); // 90 days = stale

        // Calculate weighted average
        $totalScore = 0;
        $totalWeight = 0;

        foreach ($weights as $metric => $weight) {
            if (isset($scores[$metric])) {
                $totalScore += $scores[$metric] * $weight;
                $totalWeight += $weight;

        return $totalWeight > 0 ? round($totalScore / $totalWeight, 4) : 0.0;

        }
    public function calculateFatigueScore(): float
    {
        // Higher fatigue = more stale creative
        $impressionFactor = $this->impressions > 0 ? min($this->impressions / 100000, 1.0) : 0;
        $ageFactor = $this->freshness_days !== null ? min($this->freshness_days / 60, 1.0) : 0;
        $performanceDecline = $this->performance_score !== null ? (1.0 - $this->performance_score) : 0;

        return round(($impressionFactor * 0.4) + ($ageFactor * 0.4) + ($performanceDecline * 0.2), 4);

        }
    public function isFatigued(): bool
    {
        return $this->fatigue_score > 0.7;

        }
    public function isHighPerforming(): bool
    {
        return $this->performance_score >= 0.7;

        }
    public function needsRefresh(): bool
    {
        return $this->isFatigued() || ($this->freshness_days !== null && $this->freshness_days > 60);

        }
    public function getCreativeTypeLabel(): string
    {
        return match($this->creative_type) {
            'image' => 'Image',
            'video' => 'Video',
            'carousel' => 'Carousel',
            'collection' => 'Collection',
            'story' => 'Story',
            'reels' => 'Reels',
            default => ucfirst($this->creative_type)
        };

    public function getRecommendationLabel(): string
    {
        return match($this->recommendation) {
            'scale_up' => 'Scale Up (High Performer)',
            'maintain' => 'Maintain Current Spend',
            'refresh' => 'Refresh Creative (Fatigued)',
            'pause' => 'Pause (Poor Performance)',
            'test_variation' => 'Test Variations',
            default => ucfirst(str_replace('_', ' ', $this->recommendation ?? 'none'))
        };

    public function getVisualFeatureSummary(): string
    {
        if (!$this->visual_features || !is_array($this->visual_features)) {
            return 'N/A';



            }
    public function getTextFeatureSummary(): string
    {
        if (!$this->text_features || !is_array($this->text_features)) {
            return 'N/A';




            }
    public function scopeHighPerforming($query)
    {
        return $query->where('performance_score', '>=', 0.7);

        }
    public function scopeFatigued($query)
    {
        return $query->where('fatigue_score', '>', 0.7);

        }
    public function scopeForCreativeType($query, string $type)
    {
        return $query->where('creative_type', $type);

        }
    public function scopeNeedsRefresh($query)
    {
        return $query->where(function ($q) {
            $q->where('fatigue_score', '>', 0.7)
              ->orWhere('freshness_days', '>', 60);
}
}
}
}
}
}
}
}
}
}
}
}
}
}
