<?php

namespace App\Models;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceMetric extends BaseModel
{
    
    protected $table = 'cmis.performance_metrics';

    protected $primaryKey = 'metric_id';

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
