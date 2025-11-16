<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class CampaignPerformanceMetric extends Model
{
    use HasUuids;
    protected $connection = 'pgsql';

    protected $table = 'cmis.campaign_performance_dashboard';

    protected $primaryKey = 'dashboard_id';

    public $incrementing = false;

    protected $keyType = 'string';

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

    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }
}
