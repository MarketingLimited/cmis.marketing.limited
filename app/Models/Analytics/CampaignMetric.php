<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class CampaignMetric extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.campaign_metrics';
    protected $primaryKey = 'metric_id';

    protected $fillable = [
        'metric_id', 'campaign_id', 'org_id', 'metric_name', 'value', 'recorded_at'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
