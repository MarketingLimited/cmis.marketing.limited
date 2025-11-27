<?php

namespace App\Models\Intelligence;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Campaign;
use App\Models\Core\Org;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Forecast extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis_intelligence.forecasts';
    protected $primaryKey = 'forecast_id';

    protected $fillable = [
        'forecast_id',
        'org_id',
        'model_id',
        'campaign_id',
        'target_metric',
        'forecast_date',
        'predicted_value',
        'confidence_interval_lower',
        'confidence_interval_upper',
        'confidence_level',
        'actuals',
        'accuracy_score',
        'forecast_horizon',
        'model_version',
        'features_used',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'predicted_value' => 'decimal:2',
        'confidence_interval_lower' => 'decimal:2',
        'confidence_interval_upper' => 'decimal:2',
        'confidence_level' => 'decimal:2',
        'actuals' => 'decimal:2',
        'accuracy_score' => 'decimal:4',
        'forecast_horizon' => 'integer',
        'features_used' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Metric constants
    public const METRIC_IMPRESSIONS = 'impressions';
    public const METRIC_CLICKS = 'clicks';
    public const METRIC_CONVERSIONS = 'conversions';
    public const METRIC_REVENUE = 'revenue';
    public const METRIC_CTR = 'ctr';
    public const METRIC_CPC = 'cpc';
    public const METRIC_ROAS = 'roas';
    public const METRIC_ENGAGEMENT = 'engagement';

    // Relationships
    public function predictionModel(): BelongsTo
    {
        return $this->belongsTo(PredictionModel::class, 'model_id', 'model_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Core\User::class, 'created_by', 'user_id');
    }

    // Scopes
    public function scopeByMetric($query, string $metric)
    {
        return $query->where('target_metric', $metric);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('forecast_date', [$startDate, $endDate]);
    }

    public function scopeAccurate($query, float $minAccuracy = 0.8)
    {
        return $query->where('accuracy_score', '>=', $minAccuracy);
    }

    // Helper Methods
    public function isAccurate(float $threshold = 0.8): bool
    {
        return $this->accuracy_score !== null && $this->accuracy_score >= $threshold;
    }

    public function hasActuals(): bool
    {
        return $this->actuals !== null;
    }

    public function calculateAccuracy(): ?float
    {
        if (!$this->hasActuals()) {
            return null;
        }

        $error = abs($this->predicted_value - $this->actuals);
        $mape = ($this->actuals != 0) ? ($error / $this->actuals) * 100 : 0;

        return max(0, 100 - $mape) / 100;
    }

    public function getConfidenceIntervalWidth(): ?float
    {
        if ($this->confidence_interval_lower === null || $this->confidence_interval_upper === null) {
            return null;
        }

        return $this->confidence_interval_upper - $this->confidence_interval_lower;
    }

    public function isWithinConfidenceInterval(): ?bool
    {
        if (!$this->hasActuals() || $this->confidence_interval_lower === null || $this->confidence_interval_upper === null) {
            return null;
        }

        return $this->actuals >= $this->confidence_interval_lower
            && $this->actuals <= $this->confidence_interval_upper;
    }

    // Static Methods
    public static function getAvailableMetrics(): array
    {
        return [
            self::METRIC_IMPRESSIONS => 'Impressions',
            self::METRIC_CLICKS => 'Clicks',
            self::METRIC_CONVERSIONS => 'Conversions',
            self::METRIC_REVENUE => 'Revenue',
            self::METRIC_CTR => 'Click-Through Rate',
            self::METRIC_CPC => 'Cost Per Click',
            self::METRIC_ROAS => 'Return on Ad Spend',
            self::METRIC_ENGAGEMENT => 'Engagement',
        ];
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'model_id' => 'required|uuid|exists:cmis_intelligence.prediction_models,model_id',
            'campaign_id' => 'nullable|uuid|exists:cmis.campaigns,campaign_id',
            'target_metric' => 'required|string|in:' . implode(',', array_keys(self::getAvailableMetrics())),
            'forecast_date' => 'required|date',
            'predicted_value' => 'required|numeric|min:0',
            'confidence_interval_lower' => 'nullable|numeric|min:0',
            'confidence_interval_upper' => 'nullable|numeric|min:0',
            'confidence_level' => 'nullable|numeric|min:0|max:1',
            'forecast_horizon' => 'nullable|integer|min:1|max:365',
            'features_used' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'actuals' => 'sometimes|numeric|min:0',
            'accuracy_score' => 'sometimes|numeric|min:0|max:1',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'org_id.required' => 'Organization is required',
            'model_id.required' => 'Prediction model is required',
            'target_metric.required' => 'Target metric is required',
            'target_metric.in' => 'Invalid target metric',
            'forecast_date.required' => 'Forecast date is required',
            'predicted_value.required' => 'Predicted value is required',
            'predicted_value.min' => 'Predicted value must be positive',
        ];
    }
}
