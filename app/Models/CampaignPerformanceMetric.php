<?php

namespace App\Models;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignPerformanceMetric extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis.campaign_performance_dashboard';

    protected $primaryKey = 'dashboard_id';

    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'campaign_id',
        'metric_name',
        'metric_value',
        'metric_target',
        'variance',
        'confidence_level',
        'collected_at',
        'insights',
    ];

    protected $casts = [
        'dashboard_id' => 'string',
        'org_id' => 'string',
        'campaign_id' => 'string',
        'metric_value' => 'float',
        'metric_target' => 'float',
        'variance' => 'float',
        'confidence_level' => 'float',
        'collected_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
}
}
