<?php

namespace App\Models\Analytics;

use App\Models\Campaign;
use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class KpiTarget extends Model
{
    use HasUuids;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cmis.kpi_targets';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'target_id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'org_id',
        'campaign_id',
        'kpi_name',
        'kpi_code',
        'target_value',
        'current_value',
        'unit',
        'period',
        'start_date',
        'end_date',
        'status',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_id' => 'string',
            'org_id' => 'string',
            'campaign_id' => 'string',
            'target_value' => 'decimal:2',
            'current_value' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'metadata' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the organization that owns the KPI target.
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the campaign that this KPI target belongs to.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Update the current value and status.
     *
     * @param float $value
     * @return bool
     */
    public function updateProgress(float $value): bool
    {
        $this->current_value = $value;

        // Calculate completion percentage
        $progress = ($this->target_value > 0)
            ? ($value / $this->target_value) * 100
            : 0;

        // Update status based on progress
        if ($progress >= 100) {
            $this->status = 'achieved';
        } elseif ($progress >= 75) {
            $this->status = 'on_track';
        } elseif ($progress >= 50) {
            $this->status = 'at_risk';
        } else {
            $this->status = 'behind';
        }

        return $this->save();
    }

    /**
     * Get the progress percentage.
     *
     * @return float
     */
    public function getProgressAttribute(): float
    {
        if ($this->target_value <= 0) {
            return 0;
        }

        return min(100, ($this->current_value / $this->target_value) * 100);
    }

    /**
     * Scope active KPIs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'achieved')
            ->whereDate('end_date', '>=', now());
    }

    /**
     * Scope achieved KPIs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAchieved($query)
    {
        return $query->where('status', 'achieved');
    }
}
