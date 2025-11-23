<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasOrganization;

use App\Models\Campaign;
use App\Models\Core\Org;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class KpiTarget extends BaseModel
{
    
    

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

        return min(100, ($this->current_value / $this->target_value) * 100);

    }
    /**
     * Scope active KPIs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query): Builder
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
    public function scopeAchieved($query): Builder
    {
        return $query->where('status', 'achieved');
}
}
}
}
