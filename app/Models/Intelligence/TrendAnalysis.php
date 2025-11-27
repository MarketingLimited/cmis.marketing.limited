<?php

namespace App\Models\Intelligence;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrendAnalysis extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis_intelligence.trend_analyses';
    protected $primaryKey = 'analysis_id';

    protected $fillable = [
        'analysis_id',
        'org_id',
        'metric_name',
        'entity_type',
        'entity_id',
        'time_period_start',
        'time_period_end',
        'trend_direction',
        'growth_rate',
        'volatility',
        'seasonality_detected',
        'seasonality_pattern',
        'pattern_type',
        'cycle_length',
        'insights',
        'statistical_significance',
        'r_squared',
        'data_points_count',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'time_period_start' => 'date',
        'time_period_end' => 'date',
        'growth_rate' => 'decimal:4',
        'volatility' => 'decimal:4',
        'seasonality_detected' => 'boolean',
        'seasonality_pattern' => 'array',
        'cycle_length' => 'integer',
        'insights' => 'array',
        'statistical_significance' => 'decimal:4',
        'r_squared' => 'decimal:4',
        'data_points_count' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Direction constants
    public const DIRECTION_UPWARD = 'upward';
    public const DIRECTION_DOWNWARD = 'downward';
    public const DIRECTION_STABLE = 'stable';
    public const DIRECTION_VOLATILE = 'volatile';

    // Pattern type constants
    public const PATTERN_LINEAR = 'linear';
    public const PATTERN_EXPONENTIAL = 'exponential';
    public const PATTERN_LOGARITHMIC = 'logarithmic';
    public const PATTERN_CYCLICAL = 'cyclical';
    public const PATTERN_SEASONAL = 'seasonal';
    public const PATTERN_IRREGULAR = 'irregular';

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    // Scopes
    public function scopeUpward($query)
    {
        return $query->where('trend_direction', self::DIRECTION_UPWARD);
    }

    public function scopeDownward($query)
    {
        return $query->where('trend_direction', self::DIRECTION_DOWNWARD);
    }

    public function scopeStable($query)
    {
        return $query->where('trend_direction', self::DIRECTION_STABLE);
    }

    public function scopeWithSeasonality($query)
    {
        return $query->where('seasonality_detected', true);
    }

    public function scopeSignificant($query, float $minSignificance = 0.05)
    {
        return $query->where('statistical_significance', '<=', $minSignificance);
    }

    public function scopeByPattern($query, string $pattern)
    {
        return $query->where('pattern_type', $pattern);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('time_period_end', '>=', now()->subDays($days));
    }

    // Helper Methods
    public function isUpward(): bool
    {
        return $this->trend_direction === self::DIRECTION_UPWARD;
    }

    public function isDownward(): bool
    {
        return $this->trend_direction === self::DIRECTION_DOWNWARD;
    }

    public function isStable(): bool
    {
        return $this->trend_direction === self::DIRECTION_STABLE;
    }

    public function hasSeasonality(): bool
    {
        return $this->seasonality_detected === true;
    }

    public function isStatisticallySignificant(float $alpha = 0.05): bool
    {
        return $this->statistical_significance !== null && $this->statistical_significance <= $alpha;
    }

    public function isHighVolatility(float $threshold = 0.3): bool
    {
        return $this->volatility !== null && $this->volatility >= $threshold;
    }

    public function hasGoodFit(float $minRSquared = 0.7): bool
    {
        return $this->r_squared !== null && $this->r_squared >= $minRSquared;
    }

    public function getTimePeriodDays(): int
    {
        return $this->time_period_start->diffInDays($this->time_period_end);
    }

    public function getGrowthRatePercentage(): float
    {
        return $this->growth_rate * 100;
    }

    public function getTrendDescription(): string
    {
        $direction = ucfirst($this->trend_direction);
        $rate = abs($this->getGrowthRatePercentage());

        if ($this->isStable()) {
            return "Stable (growth rate: ±{$rate}%)";
        }

        return "{$direction} trend (growth rate: {$rate}%)";
    }

    public function getTrendColor(): string
    {
        return match($this->trend_direction) {
            self::DIRECTION_UPWARD => 'green',
            self::DIRECTION_DOWNWARD => 'red',
            self::DIRECTION_STABLE => 'blue',
            self::DIRECTION_VOLATILE => 'yellow',
            default => 'gray',
        };
    }

    public function getPatternDescription(): string
    {
        $descriptions = [
            self::PATTERN_LINEAR => 'Linear growth/decline',
            self::PATTERN_EXPONENTIAL => 'Exponential growth/decline',
            self::PATTERN_LOGARITHMIC => 'Logarithmic growth/decline',
            self::PATTERN_CYCLICAL => 'Cyclical pattern',
            self::PATTERN_SEASONAL => 'Seasonal pattern',
            self::PATTERN_IRREGULAR => 'Irregular pattern',
        ];

        return $descriptions[$this->pattern_type] ?? 'Unknown pattern';
    }

    // Static Methods
    public static function getDirectionOptions(): array
    {
        return [
            self::DIRECTION_UPWARD => 'Upward',
            self::DIRECTION_DOWNWARD => 'Downward',
            self::DIRECTION_STABLE => 'Stable',
            self::DIRECTION_VOLATILE => 'Volatile',
        ];
    }

    public static function getPatternOptions(): array
    {
        return [
            self::PATTERN_LINEAR => 'Linear',
            self::PATTERN_EXPONENTIAL => 'Exponential',
            self::PATTERN_LOGARITHMIC => 'Logarithmic',
            self::PATTERN_CYCLICAL => 'Cyclical',
            self::PATTERN_SEASONAL => 'Seasonal',
            self::PATTERN_IRREGULAR => 'Irregular',
        ];
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'metric_name' => 'required|string|max:255',
            'entity_type' => 'nullable|string|max:255',
            'entity_id' => 'nullable|uuid',
            'time_period_start' => 'required|date',
            'time_period_end' => 'required|date|after:time_period_start',
            'trend_direction' => 'required|in:' . implode(',', array_keys(self::getDirectionOptions())),
            'growth_rate' => 'nullable|numeric',
            'volatility' => 'nullable|numeric|min:0|max:1',
            'seasonality_detected' => 'nullable|boolean',
            'seasonality_pattern' => 'nullable|array',
            'pattern_type' => 'required|in:' . implode(',', array_keys(self::getPatternOptions())),
            'cycle_length' => 'nullable|integer|min:1',
            'insights' => 'nullable|array',
            'statistical_significance' => 'nullable|numeric|min:0|max:1',
            'r_squared' => 'nullable|numeric|min:0|max:1',
            'data_points_count' => 'nullable|integer|min:2',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'insights' => 'sometimes|array',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'org_id.required' => 'Organization is required',
            'metric_name.required' => 'Metric name is required',
            'time_period_start.required' => 'Start date is required',
            'time_period_end.required' => 'End date is required',
            'time_period_end.after' => 'End date must be after start date',
            'trend_direction.required' => 'Trend direction is required',
            'pattern_type.required' => 'Pattern type is required',
        ];
    }
}
