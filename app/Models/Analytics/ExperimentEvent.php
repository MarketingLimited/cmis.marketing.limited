<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperimentEvent extends BaseModel
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.experiment_events';
    protected $primaryKey = 'event_id';
    protected $fillable = [
        'experiment_id', 'variant_id', 'event_type', 'user_id',
        'session_id', 'value', 'properties', 'occurred_at'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'properties' => 'array',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class, 'experiment_id', 'experiment_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ExperimentVariant::class, 'variant_id', 'variant_id');
    }

    /**
     * Scope: By event type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope: Recent events
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('occurred_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope: By date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Conversions only
     */
    public function scopeConversions($query)
    {
        return $query->where('event_type', 'conversion');
    }
}
