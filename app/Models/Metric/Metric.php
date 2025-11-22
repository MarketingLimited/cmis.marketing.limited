<?php

namespace App\Models\Metric;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Metric extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.metrics';
    protected $primaryKey = 'metric_id';

    protected $fillable = [
        'metric_id',
        'org_id',
        'campaign_id',
        'metric_type',
        'value',
        'recorded_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
