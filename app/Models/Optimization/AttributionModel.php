<?php

namespace App\Models\Optimization;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\Core\User;
use App\Models\Campaign\Campaign;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
class AttributionModel extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.attribution_models';
    protected $primaryKey = 'attribution_id';

    protected $fillable = [
        'attribution_id',
        'org_id',
        'campaign_id',
        'model_type',
        'conversion_id',
        'conversion_value',
        'conversion_date',
        'touchpoints',
        'touchpoint_count',
        'attribution_weights',
        'attributed_revenue',
        'first_touch_campaign_id',
        'last_touch_campaign_id',
        'lookback_window_days',
        'click_through_weight',
        'view_through_weight',
        'time_decay_half_life',
        'position_weights',
        'shapley_values',
        'markov_contribution',
        'confidence_score',
        'created_by',
    ];

    protected $casts = [
        'conversion_value' => 'decimal:2',
        'conversion_date' => 'datetime',
        'touchpoints' => 'array',
        'touchpoint_count' => 'integer',
        'attribution_weights' => 'array',
        'attributed_revenue' => 'decimal:2',
        'lookback_window_days' => 'integer',
        'click_through_weight' => 'float',
        'view_through_weight' => 'float',
        'time_decay_half_life' => 'float',
        'position_weights' => 'array',
        'shapley_values' => 'array',
        'markov_contribution' => 'array',
        'confidence_score' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');

        }
    public function firstTouchCampaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'first_touch_campaign_id', 'campaign_id');

        }
    public function lastTouchCampaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'last_touch_campaign_id', 'campaign_id');

        }
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');


        }
    public function getModelTypeLabel(): string
    {
        return match($this->model_type) {
            'first_touch' => 'First Touch',
            'last_touch' => 'Last Touch',
            'linear' => 'Linear',
            'time_decay' => 'Time Decay',
            'position_based' => 'Position Based (U-Shaped)',
            'data_driven' => 'Data-Driven (Algorithmic)',
            default => ucfirst(str_replace('_', ' ', $this->model_type))
        };
    }

    public function getAttributionForCampaign(string $campaignId): ?float
    {
        if (!$this->attribution_weights || !is_array($this->attribution_weights)) {
            return null;
        }

        return $this->attribution_weights[$campaignId] ?? null;
    }
    public function getShapleyValue(string $campaignId): ?float
    {
        if (!$this->shapley_values || !is_array($this->shapley_values)) {
            return null;
        }

        return $this->shapley_values[$campaignId] ?? null;
    }

    public function getMarkovContribution(string $campaignId): ?float
    {
        if (!$this->markov_contribution || !is_array($this->markov_contribution)) {
            return null;
        }

        return $this->markov_contribution[$campaignId] ?? null;
    }

    public function getTouchpointPath(): string
    {
        if (!$this->touchpoints || !is_array($this->touchpoints)) {
            return 'N/A';
        }

        $path = array_map(fn($tp) => $tp['campaign_name'] ?? $tp['campaign_id'] ?? 'Unknown', $this->touchpoints);
        return implode(' → ', $path);
    }

    public function getAverageTimeToConversion(): ?float
    {
        if (!$this->touchpoints || !is_array($this->touchpoints) || count($this->touchpoints) === 0) {
            return null;
        }

        $firstTouchTime = strtotime($this->touchpoints[0]['timestamp'] ?? 'now');
        $lastTouchTime = strtotime($this->conversion_date);
        $hoursDiff = ($lastTouchTime - $firstTouchTime) / 3600;

        return max($hoursDiff, 0);
    }
    public function isMultiTouch(): bool
    {
        return $this->touchpoint_count > 1;

        }
    public function isDataDriven(): bool
    {
        return $this->model_type === 'data_driven';


        }
    public function scopeForModelType($query, string $modelType): Builder
    {
        return $query->where('model_type', $modelType);

        }
    public function scopeMultiTouch($query): Builder
    {
        return $query->where('touchpoint_count', '>', 1);

        }
    public function scopeDataDriven($query): Builder
    {
        return $query->where('model_type', 'data_driven');

        }
    public function scopeHighValue($query, float $threshold = 100): Builder
    {
        return $query->where('conversion_value', '>=', $threshold);

        }
    public function scopeWithinLookback($query, int $days): Builder
    {
        return $query->where('conversion_date', '>=', now()->subDays($days));
    }
}
