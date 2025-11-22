<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class AnalyticsSnapshot extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.analytics_snapshots';
    protected $primaryKey = 'snapshot_id';

    protected $fillable = [
        'snapshot_id',
        'org_id',
        'campaign_id',
        'metrics',
        'snapshot_date',
    ];

    protected $casts = [
        'metrics' => 'array',
        'snapshot_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
