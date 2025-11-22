<?php

namespace App\Models\Content;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ContentPlan extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.content_plans';
    protected $primaryKey = 'plan_id';

    protected $fillable = [
        'plan_id',
        'org_id',
        'campaign_id',
        'name',
        'timeframe_daterange',
        'strategy',
        'brief_id',
        'creative_context_id',
        'provider',
    ];

    protected $casts = [
        'strategy' => 'array',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
