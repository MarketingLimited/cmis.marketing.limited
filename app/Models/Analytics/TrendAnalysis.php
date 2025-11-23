<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrendAnalysis extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.trend_analysis';
    protected $primaryKey = 'trend_id';
    protected $fillable = [
        'org_id', 'entity_type', 'entity_id', 'metric', 'trend_type',
        'trend_strength', 'confidence', 'period_start', 'period_end',
        'data_points', 'slope', 'seasonality_detected', 'pattern_details',
        'interpretation'
    ];

    protected $casts = [
        'trend_strength' => 'decimal:2',
        'confidence' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'data_points' => 'integer',
        'slope' => 'decimal:4',
        'seasonality_detected' => 'array',
        'pattern_details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    

    public function isPositiveTrend(): bool
    {
        return in_array($this->trend_type, ['upward', 'stable']) && $this->slope >= 0;

        }
    public function isNegativeTrend(): bool
    {
        return $this->trend_type === 'downward' && $this->slope < 0;

        }
    public function hasSeasonality(): bool
    {
        return !empty($this->seasonality_detected);

        }
    public function scopeForEntity($query, string $entityType, string $entityId)
    {
        return $query->where('entity_type', $entityType)
                     ->where('entity_id', $entityId);

                     }
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('period_end', '>=', now()->subDays($days));
}
}
}
