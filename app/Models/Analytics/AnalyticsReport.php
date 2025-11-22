<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class AnalyticsReport extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.analytics_reports';
    protected $primaryKey = 'report_id';

    protected $fillable = [
        'report_id',
        'org_id',
        'type',
        'data',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'data' => 'array',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
