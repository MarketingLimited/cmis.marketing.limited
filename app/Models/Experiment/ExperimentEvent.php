<?php

namespace App\Models\Experiment;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExperimentEvent extends BaseModel
{
    use HasFactory;

    protected $table = 'cmis.experiment_events';
    protected $primaryKey = 'event_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'experiment_id',
        'variant_id',
        'event_type',
        'user_id',
        'session_id',
        'value',
        'properties',
        'occurred_at',
    ];

    protected $casts = [
        'event_id' => 'string',
        'experiment_id' => 'string',
        'variant_id' => 'string',
        'value' => 'decimal:2',
        'properties' => 'array',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the experiment
     */
    public function experiment()
    {
        return $this->belongsTo(Experiment::class, 'experiment_id', 'experiment_id');
    }

    /**
     * Get the variant
     */
    public function variant()
    {
        return $this->belongsTo(ExperimentVariant::class, 'variant_id', 'variant_id');
    }

    /**
     * Scope for specific event type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope for impressions
     */
    public function scopeImpressions($query)
    {
        return $query->where('event_type', 'impression');
    }

    /**
     * Scope for clicks
     */
    public function scopeClicks($query)
    {
        return $query->where('event_type', 'click');
    }

    /**
     * Scope for conversions
     */
    public function scopeConversions($query)
    {
        return $query->where('event_type', 'conversion');
    }

    /**
     * Scope for events within a date range
     */
    public function scopeOccurredBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }

    /**
     * Scope for recent events
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('occurred_at', '>=', now()->subHours($hours));
    }
}
