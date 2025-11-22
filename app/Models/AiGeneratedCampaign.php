<?php

namespace App\Models;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;

class AiGeneratedCampaign extends BaseModel
{
    
    protected $table = 'cmis.ai_generated_campaigns';

    protected $primaryKey = 'campaign_id';

    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'org_id',
        'objective_code',
        'recommended_principle',
        'linked_kpi',
        'ai_summary',
        'ai_design_guideline',
        'engine',
        'provider',
    ];

    protected $casts = [
        'campaign_id' => 'string',
        'org_id' => 'string',
        'created_at' => 'datetime',
    ];
}
