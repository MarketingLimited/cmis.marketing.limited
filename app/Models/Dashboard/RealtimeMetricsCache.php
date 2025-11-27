<?php

namespace App\Models\Dashboard;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RealtimeMetricsCache extends BaseModel
{
    use HasFactory, HasOrganization;

    protected $table = 'cmis_dashboard.realtime_metrics_cache';
    protected $primaryKey = 'cache_id';

    public $timestamps = true;

    protected $fillable = [
        'cache_id',
        'widget_id',
        'org_id',
        'entity_type',
        'entity_id',
        'metric_name',
        'metric_value',
        'aggregation_type',
        'time_window',
        'dimensions',
        'filters',
        'calculated_at',
        'expires_at',
        'hit_count',
        'last_hit_at',
        'is_stale',
        'metadata',
    ];

    protected $casts = [
        'metric_value' => 'decimal:4',
        'dimensions' => 'array',
        'filters' => 'array',
        'calculated_at' => 'datetime',
        'expires_at' => 'datetime',
        'hit_count' => 'integer',
        'last_hit_at' => 'datetime',
        'is_stale' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Aggregation type constants (same as DashboardWidget)
    public const AGG_SUM = 'sum';
    public const AGG_AVG = 'average';
    public const AGG_COUNT = 'count';
    public const AGG_MIN = 'min';
    public const AGG_MAX = 'max';
    public const AGG_MEDIAN = 'median';
    public const AGG_DISTINCT_COUNT = 'distinct_count';

    // Time window constants
    public const WINDOW_REALTIME = 'realtime'; // Last 5 minutes
    public const WINDOW_LAST_HOUR = 'last_hour';
    public const WINDOW_LAST_24_HOURS = 'last_24_hours';
    public const WINDOW_LAST_7_DAYS = 'last_7_days';
    public const WINDOW_LAST_30_DAYS = 'last_30_days';
    public const WINDOW_CUSTOM = 'custom';

    // Relationships
    public function widget(): BelongsTo
    {
        return $this->belongsTo(DashboardWidget::class, 'widget_id', 'widget_id');
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeStale($query)
    {
        return $query->where('is_stale', true);
    }

    public function scopeFresh($query)
    {
        return $query->where('is_stale', false)
                     ->where('expires_at', '>', now());
    }

    public function scopeByMetric($query, string $metric)
    {
        return $query->where('metric_name', $metric);
    }

    public function scopeByWindow($query, string $window)
    {
        return $query->where('time_window', $window);
    }

    public function scopeByEntity($query, string $type, string $id)
    {
        return $query->where('entity_type', $type)
                     ->where('entity_id', $id);
    }

    public function scopePopular($query, int $minHits = 10)
    {
        return $query->where('hit_count', '>=', $minHits)
                     ->orderBy('hit_count', 'desc');
    }

    // Helper Methods
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isStale(): bool
    {
        return $this->is_stale === true;
    }

    public function isFresh(): bool
    {
        return !$this->isStale() && !$this->isExpired();
    }

    public function markAsStale(): bool
    {
        return $this->update(['is_stale' => true]);
    }

    public function refresh(float $newValue, array $metadata = []): bool
    {
        return $this->update([
            'metric_value' => $newValue,
            'calculated_at' => now(),
            'expires_at' => $this->calculateExpiration(),
            'is_stale' => false,
            'metadata' => array_merge($this->metadata ?? [], $metadata),
        ]);
    }

    public function recordHit(): bool
    {
        return $this->increment('hit_count', 1, [
            'last_hit_at' => now()
        ]);
    }

    public function extend(int $seconds = 300): bool
    {
        $newExpiration = now()->addSeconds($seconds);

        return $this->update(['expires_at' => $newExpiration]);
    }

    public function calculateExpiration(): \DateTime
    {
        $ttl = match($this->time_window) {
            self::WINDOW_REALTIME => 300,        // 5 minutes
            self::WINDOW_LAST_HOUR => 900,       // 15 minutes
            self::WINDOW_LAST_24_HOURS => 3600,  // 1 hour
            self::WINDOW_LAST_7_DAYS => 7200,    // 2 hours
            self::WINDOW_LAST_30_DAYS => 14400,  // 4 hours
            default => 1800,                      // 30 minutes default
        };

        return now()->addSeconds($ttl);
    }

    public function getTTL(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return $this->expires_at->diffInSeconds(now());
    }

    public function getAge(): int
    {
        return $this->calculated_at->diffInSeconds(now());
    }

    public function getHitRate(): float
    {
        $ageInMinutes = $this->created_at->diffInMinutes(now());

        if ($ageInMinutes === 0) {
            return 0;
        }

        return round($this->hit_count / $ageInMinutes, 2);
    }

    public function hasDimensions(): bool
    {
        return !empty($this->dimensions);
    }

    public function hasFilters(): bool
    {
        return !empty($this->filters);
    }

    public function matchesFilters(array $filters): bool
    {
        if (empty($this->filters) && empty($filters)) {
            return true;
        }

        return $this->filters === $filters;
    }

    public function matchesDimensions(array $dimensions): bool
    {
        if (empty($this->dimensions) && empty($dimensions)) {
            return true;
        }

        return $this->dimensions === $dimensions;
    }

    public function getWindowDescription(): string
    {
        return match($this->time_window) {
            self::WINDOW_REALTIME => 'Real-time (Last 5 minutes)',
            self::WINDOW_LAST_HOUR => 'Last Hour',
            self::WINDOW_LAST_24_HOURS => 'Last 24 Hours',
            self::WINDOW_LAST_7_DAYS => 'Last 7 Days',
            self::WINDOW_LAST_30_DAYS => 'Last 30 Days',
            self::WINDOW_CUSTOM => 'Custom Window',
            default => 'Unknown Window',
        };
    }

    public function getStatusColor(): string
    {
        if ($this->isExpired()) {
            return 'red';
        }

        if ($this->isStale()) {
            return 'yellow';
        }

        return 'green';
    }

    public function getStatusText(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }

        if ($this->isStale()) {
            return 'Stale';
        }

        return 'Fresh';
    }

    // Static Methods
    public static function getAggregationOptions(): array
    {
        return [
            self::AGG_SUM => 'Sum',
            self::AGG_AVG => 'Average',
            self::AGG_COUNT => 'Count',
            self::AGG_MIN => 'Minimum',
            self::AGG_MAX => 'Maximum',
            self::AGG_MEDIAN => 'Median',
            self::AGG_DISTINCT_COUNT => 'Distinct Count',
        ];
    }

    public static function getWindowOptions(): array
    {
        return [
            self::WINDOW_REALTIME => 'Real-time (5 min)',
            self::WINDOW_LAST_HOUR => 'Last Hour',
            self::WINDOW_LAST_24_HOURS => 'Last 24 Hours',
            self::WINDOW_LAST_7_DAYS => 'Last 7 Days',
            self::WINDOW_LAST_30_DAYS => 'Last 30 Days',
            self::WINDOW_CUSTOM => 'Custom Window',
        ];
    }

    public static function findOrCreateCache(
        string $widgetId,
        string $orgId,
        string $metricName,
        string $aggregationType,
        string $timeWindow,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $dimensions = null,
        ?array $filters = null
    ): self {
        $query = static::where('widget_id', $widgetId)
                       ->where('org_id', $orgId)
                       ->where('metric_name', $metricName)
                       ->where('aggregation_type', $aggregationType)
                       ->where('time_window', $timeWindow);

        if ($entityType) {
            $query->where('entity_type', $entityType);
        }

        if ($entityId) {
            $query->where('entity_id', $entityId);
        }

        if ($dimensions) {
            $query->where('dimensions', json_encode($dimensions));
        }

        if ($filters) {
            $query->where('filters', json_encode($filters));
        }

        $cache = $query->fresh()->first();

        if (!$cache) {
            $cache = static::create([
                'widget_id' => $widgetId,
                'org_id' => $orgId,
                'metric_name' => $metricName,
                'aggregation_type' => $aggregationType,
                'time_window' => $timeWindow,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'dimensions' => $dimensions,
                'filters' => $filters,
                'metric_value' => 0,
                'calculated_at' => now(),
                'expires_at' => now()->addMinutes(5),
                'hit_count' => 0,
                'is_stale' => true,
            ]);
        }

        return $cache;
    }

    public static function cleanupExpired(): int
    {
        return static::expired()->delete();
    }

    public static function cleanupStale(int $olderThanHours = 24): int
    {
        return static::where('is_stale', true)
                     ->where('updated_at', '<', now()->subHours($olderThanHours))
                     ->delete();
    }

    public static function cleanupUnused(int $maxHitCount = 5, int $olderThanDays = 7): int
    {
        return static::where('hit_count', '<=', $maxHitCount)
                     ->where('created_at', '<', now()->subDays($olderThanDays))
                     ->delete();
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'widget_id' => 'required|uuid|exists:cmis_dashboard.dashboard_widgets,widget_id',
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'entity_type' => 'nullable|string|max:255',
            'entity_id' => 'nullable|uuid',
            'metric_name' => 'required|string|max:255',
            'metric_value' => 'required|numeric',
            'aggregation_type' => 'required|in:' . implode(',', array_keys(self::getAggregationOptions())),
            'time_window' => 'required|in:' . implode(',', array_keys(self::getWindowOptions())),
            'dimensions' => 'nullable|array',
            'filters' => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'metric_value' => 'sometimes|numeric',
            'is_stale' => 'sometimes|boolean',
            'expires_at' => 'sometimes|date|after:now',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'widget_id.required' => 'Widget is required',
            'org_id.required' => 'Organization is required',
            'metric_name.required' => 'Metric name is required',
            'metric_value.required' => 'Metric value is required',
            'aggregation_type.required' => 'Aggregation type is required',
            'time_window.required' => 'Time window is required',
        ];
    }
}
