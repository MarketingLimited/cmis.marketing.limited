<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class PerformanceMetric extends Model
{
    use HasUuids;
    protected $connection = 'pgsql';

    protected $table = 'cmis.performance_metrics';

    protected $primaryKey = 'metric_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'metric_id',
        'org_id',
        'campaign_id',
        'output_id',
        'kpi',
        'observed',
        'target',
        'baseline',
        'observed_at',
        'provider',
    ];

    protected $casts = [
        'metric_id' => 'string',
        'org_id' => 'string',
        'campaign_id' => 'string',
        'observed' => 'float',
        'target' => 'float',
        'baseline' => 'float',
        'observed_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }
}
