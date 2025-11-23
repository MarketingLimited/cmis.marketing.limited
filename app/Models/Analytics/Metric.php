<?php

namespace App\Models\Analytics;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Metric Model
 *
 * Unified metrics model with polymorphic relationships.
 * Consolidates 10 previous metrics tables into single source of truth.
 *
 * Can track metrics for any entity (campaign, ad, post, account, etc.)
 * with time-series partitioning for optimal performance.
 *
 * @property string $id
 * @property string $org_id
 * @property string $entity_type
 * @property string $entity_id
 * @property string $metric_category
 * @property string $metric_name
 * @property float|null $value_numeric
 * @property string|null $value_text
 * @property array|null $value_json
 * @property string|null $platform
 * @property string $source
 * @property Carbon $recorded_at
 * @property array|null $metadata
 *
 * @package App\Models\Analytics
 */
class Metric extends BaseModel
{
    use HasOrganization;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cmis.metrics';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'org_id',
        'entity_type',
        'entity_id',
        'metric_category',
        'metric_name',
        'value_numeric',
        'value_text',
        'value_json',
        'platform',
        'source',
        'recorded_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value_numeric' => 'decimal:4',
        'value_json' => 'array',
        'metadata' => 'array',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Metric categories constants
     */
    const CATEGORY_PERFORMANCE = 'performance';
    const CATEGORY_FINANCIAL = 'financial';
    const CATEGORY_ENGAGEMENT = 'engagement';
    const CATEGORY_CONVERSION = 'conversion';
    const CATEGORY_VIDEO = 'video';
    const CATEGORY_AUDIENCE = 'audience';

    /**
     * Common metric names constants
     */
    const METRIC_IMPRESSIONS = 'impressions';
    const METRIC_CLICKS = 'clicks';
    const METRIC_CTR = 'ctr';
    const METRIC_REACH = 'reach';
    const METRIC_SPEND = 'spend';
    const METRIC_CPC = 'cpc';
    const METRIC_CPA = 'cpa';
    const METRIC_ROAS = 'roas';
    const METRIC_CONVERSIONS = 'conversions';
    const METRIC_ENGAGEMENT_RATE = 'engagement_rate';

    /**
     * Platforms constants
     */
    const PLATFORM_META = 'meta';
    const PLATFORM_GOOGLE = 'google';
    const PLATFORM_TIKTOK = 'tiktok';
    const PLATFORM_LINKEDIN = 'linkedin';
    const PLATFORM_TWITTER = 'twitter';
    const PLATFORM_SNAPCHAT = 'snapchat';

    /**
     * Get the entity that owns this metric (polymorphic)
     *
     * @return MorphTo
     */
    public function entity(): MorphTo
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    /**
     * Get the metric definition
     */
    public function definition(): HasOne
    {
        return $this->hasOne(MetricDefinition::class, 'metric_name', 'metric_name');
    }

    // ==================================================================
    // Scopes
    // ==================================================================

    /**
     * Scope for a specific entity
     *
     * @param Builder $query
     * @param string $entityType
     * @param string $entityId
     * @return Builder
     */
    public function scopeForEntity(Builder $query, string $entityType, string $entityId): Builder
    {
        return $query->where('entity_type', $entityType)
                     ->where('entity_id', $entityId);
    }

    /**
     * Scope for a specific metric name
     *
     * @param Builder $query
     * @param string $metricName
     * @return Builder
     */
    public function scopeMetric(Builder $query, string $metricName): Builder
    {
        return $query->where('metric_name', $metricName);
    }

    /**
     * Scope for a specific metric category
     *
     * @param Builder $query
     * @param string $category
     * @return Builder
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('metric_category', $category);
    }

    /**
     * Scope for a specific platform
     *
     * @param Builder $query
     * @param string $platform
     * @return Builder
     */
    public function scopePlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope for date range
     *
     * @param Builder $query
     * @param string|Carbon $startDate
     * @param string|Carbon $endDate
     * @return Builder
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate): Builder
    {
        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        return $query->whereBetween('recorded_at', [$start, $end]);
    }

    /**
     * Scope for today's metrics
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('recorded_at', today());
    }

    /**
     * Scope for this week's metrics
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('recorded_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope for this month's metrics
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('recorded_at', now()->month)
                     ->whereYear('recorded_at', now()->year);
    }

    /**
     * Scope for latest metrics per entity
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('recorded_at', 'desc');
    }

    /**
     * Scope for numeric metrics only
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeNumeric(Builder $query): Builder
    {
        return $query->whereNotNull('value_numeric');
    }

    // ==================================================================
    // Helper Methods
    // ==================================================================

    /**
     * Get the metric value (auto-detect type)
     *
     * @return mixed
     */
    public function getValue()
    : \Illuminate\Database\Eloquent\Relations\Relation {
        if ($this->value_numeric !== null) {
            return $this->value_numeric;
        }
        if ($this->value_text !== null) {
            return $this->value_text;
        }
        return $this->value_json;
    }

    /**
     * Check if metric is numeric
     *
     * @return bool
     */
    public function isNumeric(): bool
    {
        return $this->value_numeric !== null;
    }

    /**
     * Format value for display
     *
     * @return string
     */
    public function getFormattedValueAttribute(): string
    {
        $value = $this->getValue();

        if ($this->isNumeric()) {
            // Check if it's a percentage metric
            if (str_ends_with($this->metric_name, '_rate') || $this->metric_name === 'ctr' || $this->metric_name === 'roi') {
                return number_format($value, 2) . '%';
            }
            // Check if it's a currency metric
            if (in_array($this->metric_name, ['spend', 'cpc', 'cpa', 'conversion_value'])) {
                return '$' . number_format($value, 2);
            }
            // Default numeric formatting
            return number_format($value);
        }
        if (is_array($value)) {
            return json_encode($value);
        }
        return (string) $value;
    }

    /**
     * Get display name from definition or fallback
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->definition->display_name ?? ucwords(str_replace('_', ' ', $this->metric_name));
    }

    // ==================================================================
    // Static Helper Methods
    // ==================================================================

    /**
     * Record a metric value
     *
     * @param string $entityType
     * @param string $entityId
     * @param string $metricName
     * @param mixed $value
     * @param string|null $platform
     * @param array $options
     * @return self
     */
    public static function record(
        string $entityType,
        string $entityId,
        string $metricName,
        $value,
        ?string $platform = null,
        array $options = []
    ): self {
        $metricCategory = self::inferCategory($metricName);

        return self::create([
            'org_id' => $options['org_id'] ?? auth()->user()?->org_id,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metric_category' => $options['category'] ?? $metricCategory,
            'metric_name' => $metricName,
            'value_numeric' => is_numeric($value) ? $value : null,
            'value_text' => is_string($value) && !is_numeric($value) ? $value : null,
            'value_json' => is_array($value) ? $value : null,
            'platform' => $platform,
            'source' => $options['source'] ?? 'api',
            'recorded_at' => $options['recorded_at'] ?? now(),
            'metadata' => $options['metadata'] ?? null,
        ]);
    }

    /**
     * Infer metric category from metric name
     *
     * @param string $metricName
     * @return string
     */
    protected static function inferCategory(string $metricName): string
    {
        $categoryMap = [
            'impressions' => self::CATEGORY_PERFORMANCE,
            'clicks' => self::CATEGORY_PERFORMANCE,
            'reach' => self::CATEGORY_PERFORMANCE,
            'ctr' => self::CATEGORY_PERFORMANCE,
            'spend' => self::CATEGORY_FINANCIAL,
            'cpc' => self::CATEGORY_FINANCIAL,
            'cpa' => self::CATEGORY_FINANCIAL,
            'roas' => self::CATEGORY_FINANCIAL,
            'roi' => self::CATEGORY_FINANCIAL,
            'likes' => self::CATEGORY_ENGAGEMENT,
            'comments' => self::CATEGORY_ENGAGEMENT,
            'shares' => self::CATEGORY_ENGAGEMENT,
            'engagement_rate' => self::CATEGORY_ENGAGEMENT,
            'conversions' => self::CATEGORY_CONVERSION,
            'conversion_rate' => self::CATEGORY_CONVERSION,
            'video_views' => self::CATEGORY_VIDEO,
            'followers' => self::CATEGORY_AUDIENCE,
        ];

        return $categoryMap[$metricName] ?? 'performance';
    }
}
