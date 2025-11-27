<?php

namespace App\Models\Influencer;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfluencerPerformance extends BaseModel
{
    use HasFactory, HasOrganization;

    protected $table = 'cmis_influencer.influencer_performance';
    protected $primaryKey = 'performance_id';

    public $timestamps = true;

    protected $fillable = [
        'performance_id',
        'influencer_id',
        'influencer_campaign_id',
        'org_id',
        'metric_date',
        'period_type',
        'impressions',
        'reach',
        'engagement',
        'likes',
        'comments',
        'shares',
        'saves',
        'clicks',
        'conversions',
        'revenue',
        'engagement_rate',
        'click_through_rate',
        'conversion_rate',
        'cost_per_click',
        'cost_per_conversion',
        'roi',
        'follower_growth',
        'audience_sentiment',
        'brand_mentions',
        'top_performing_content',
        'demographic_breakdown',
        'platform_breakdown',
        'metadata',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'impressions' => 'integer',
        'reach' => 'integer',
        'engagement' => 'integer',
        'likes' => 'integer',
        'comments' => 'integer',
        'shares' => 'integer',
        'saves' => 'integer',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'revenue' => 'decimal:2',
        'engagement_rate' => 'decimal:4',
        'click_through_rate' => 'decimal:4',
        'conversion_rate' => 'decimal:4',
        'cost_per_click' => 'decimal:2',
        'cost_per_conversion' => 'decimal:2',
        'roi' => 'decimal:4',
        'follower_growth' => 'integer',
        'audience_sentiment' => 'decimal:2',
        'brand_mentions' => 'integer',
        'top_performing_content' => 'array',
        'demographic_breakdown' => 'array',
        'platform_breakdown' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Period type constants
    public const PERIOD_DAILY = 'daily';
    public const PERIOD_WEEKLY = 'weekly';
    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_QUARTERLY = 'quarterly';
    public const PERIOD_CAMPAIGN = 'campaign';

    // Relationships
    public function influencer(): BelongsTo
    {
        return $this->belongsTo(Influencer::class, 'influencer_id', 'influencer_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(InfluencerCampaign::class, 'influencer_campaign_id', 'influencer_campaign_id');
    }

    // Scopes
    public function scopeByPeriod($query, string $period)
    {
        return $query->where('period_type', $period);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('metric_date', [$startDate, $endDate]);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('metric_date', '>=', now()->subDays($days));
    }

    public function scopeHighPerforming($query, float $minEngagementRate = 0.05)
    {
        return $query->where('engagement_rate', '>=', $minEngagementRate);
    }

    public function scopeProfitable($query)
    {
        return $query->where('roi', '>', 0);
    }

    // Helper Methods
    public function calculateEngagementRate(): float
    {
        if ($this->impressions === 0) {
            return 0;
        }

        $totalEngagement = $this->engagement + $this->likes + $this->comments + $this->shares + $this->saves;
        return round(($totalEngagement / $this->impressions), 4);
    }

    public function calculateClickThroughRate(): float
    {
        if ($this->impressions === 0) {
            return 0;
        }

        return round(($this->clicks / $this->impressions), 4);
    }

    public function calculateConversionRate(): float
    {
        if ($this->clicks === 0) {
            return 0;
        }

        return round(($this->conversions / $this->clicks), 4);
    }

    public function calculateROI(float $cost): float
    {
        if ($cost === 0) {
            return 0;
        }

        return round((($this->revenue - $cost) / $cost), 4);
    }

    public function updateCalculatedMetrics(?float $cost = null): bool
    {
        $updates = [
            'engagement_rate' => $this->calculateEngagementRate(),
            'click_through_rate' => $this->calculateClickThroughRate(),
            'conversion_rate' => $this->calculateConversionRate(),
        ];

        if ($cost !== null) {
            $updates['roi'] = $this->calculateROI($cost);

            if ($this->clicks > 0) {
                $updates['cost_per_click'] = round($cost / $this->clicks, 2);
            }

            if ($this->conversions > 0) {
                $updates['cost_per_conversion'] = round($cost / $this->conversions, 2);
            }
        }

        return $this->update($updates);
    }

    public function getTotalEngagement(): int
    {
        return $this->engagement + $this->likes + $this->comments + $this->shares + $this->saves;
    }

    public function hasPositiveROI(): bool
    {
        return $this->roi > 0;
    }

    public function getEngagementPercentage(): float
    {
        return $this->engagement_rate * 100;
    }

    public function getCTRPercentage(): float
    {
        return $this->click_through_rate * 100;
    }

    public function getConversionPercentage(): float
    {
        return $this->conversion_rate * 100;
    }

    public function getROIPercentage(): float
    {
        return $this->roi * 100;
    }

    public function isHighPerforming(float $engagementThreshold = 0.05): bool
    {
        return $this->engagement_rate >= $engagementThreshold;
    }

    public function getSentimentLabel(): string
    {
        if ($this->audience_sentiment === null) {
            return 'Unknown';
        }

        return match(true) {
            $this->audience_sentiment >= 0.7 => 'Very Positive',
            $this->audience_sentiment >= 0.4 => 'Positive',
            $this->audience_sentiment >= -0.4 => 'Neutral',
            $this->audience_sentiment >= -0.7 => 'Negative',
            default => 'Very Negative',
        };
    }

    public function getSentimentColor(): string
    {
        if ($this->audience_sentiment === null) {
            return 'gray';
        }

        return match(true) {
            $this->audience_sentiment >= 0.7 => 'green',
            $this->audience_sentiment >= 0.4 => 'blue',
            $this->audience_sentiment >= -0.4 => 'yellow',
            $this->audience_sentiment >= -0.7 => 'orange',
            default => 'red',
        };
    }

    public function getPerformanceScore(): float
    {
        // Composite performance score based on multiple factors
        $score = 0;

        // Engagement rate (40%)
        $score += min(1, $this->engagement_rate / 0.1) * 40;

        // CTR (30%)
        $score += min(1, $this->click_through_rate / 0.05) * 30;

        // Conversion rate (20%)
        $score += min(1, $this->conversion_rate / 0.02) * 20;

        // Sentiment (10%)
        if ($this->audience_sentiment !== null) {
            $score += (($this->audience_sentiment + 1) / 2) * 10;
        }

        return round($score, 2);
    }

    public function getPerformanceGrade(): string
    {
        $score = $this->getPerformanceScore();

        return match(true) {
            $score >= 90 => 'A+',
            $score >= 80 => 'A',
            $score >= 70 => 'B',
            $score >= 60 => 'C',
            $score >= 50 => 'D',
            default => 'F',
        };
    }

    // Static Methods
    public static function getPeriodOptions(): array
    {
        return [
            self::PERIOD_DAILY => 'Daily',
            self::PERIOD_WEEKLY => 'Weekly',
            self::PERIOD_MONTHLY => 'Monthly',
            self::PERIOD_QUARTERLY => 'Quarterly',
            self::PERIOD_CAMPAIGN => 'Campaign',
        ];
    }

    public static function aggregateMetrics(string $influencerId, string $periodType, $startDate, $endDate): ?self
    {
        $metrics = static::where('influencer_id', $influencerId)
            ->where('period_type', $periodType)
            ->whereBetween('metric_date', [$startDate, $endDate])
            ->get();

        if ($metrics->isEmpty()) {
            return null;
        }

        return static::create([
            'influencer_id' => $influencerId,
            'org_id' => $metrics->first()->org_id,
            'metric_date' => $endDate,
            'period_type' => $periodType,
            'impressions' => $metrics->sum('impressions'),
            'reach' => $metrics->sum('reach'),
            'engagement' => $metrics->sum('engagement'),
            'likes' => $metrics->sum('likes'),
            'comments' => $metrics->sum('comments'),
            'shares' => $metrics->sum('shares'),
            'saves' => $metrics->sum('saves'),
            'clicks' => $metrics->sum('clicks'),
            'conversions' => $metrics->sum('conversions'),
            'revenue' => $metrics->sum('revenue'),
            'follower_growth' => $metrics->sum('follower_growth'),
            'brand_mentions' => $metrics->sum('brand_mentions'),
        ]);
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'influencer_id' => 'required|uuid|exists:cmis_influencer.influencers,influencer_id',
            'influencer_campaign_id' => 'nullable|uuid|exists:cmis_influencer.influencer_campaigns,influencer_campaign_id',
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'metric_date' => 'required|date',
            'period_type' => 'required|in:' . implode(',', array_keys(self::getPeriodOptions())),
            'impressions' => 'nullable|integer|min:0',
            'reach' => 'nullable|integer|min:0',
            'engagement' => 'nullable|integer|min:0',
            'likes' => 'nullable|integer|min:0',
            'comments' => 'nullable|integer|min:0',
            'shares' => 'nullable|integer|min:0',
            'saves' => 'nullable|integer|min:0',
            'clicks' => 'nullable|integer|min:0',
            'conversions' => 'nullable|integer|min:0',
            'revenue' => 'nullable|numeric|min:0',
            'engagement_rate' => 'nullable|numeric|min:0|max:1',
            'click_through_rate' => 'nullable|numeric|min:0|max:1',
            'conversion_rate' => 'nullable|numeric|min:0|max:1',
            'cost_per_click' => 'nullable|numeric|min:0',
            'cost_per_conversion' => 'nullable|numeric|min:0',
            'roi' => 'nullable|numeric',
            'follower_growth' => 'nullable|integer',
            'audience_sentiment' => 'nullable|numeric|min:-1|max:1',
            'brand_mentions' => 'nullable|integer|min:0',
            'top_performing_content' => 'nullable|array',
            'demographic_breakdown' => 'nullable|array',
            'platform_breakdown' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'impressions' => 'sometimes|integer|min:0',
            'engagement' => 'sometimes|integer|min:0',
            'clicks' => 'sometimes|integer|min:0',
            'conversions' => 'sometimes|integer|min:0',
            'revenue' => 'sometimes|numeric|min:0',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'influencer_id.required' => 'Influencer is required',
            'org_id.required' => 'Organization is required',
            'metric_date.required' => 'Metric date is required',
            'period_type.required' => 'Period type is required',
            'engagement_rate.max' => 'Engagement rate must be between 0 and 1',
            'audience_sentiment.min' => 'Sentiment must be between -1 and 1',
            'audience_sentiment.max' => 'Sentiment must be between -1 and 1',
        ];
    }
}
