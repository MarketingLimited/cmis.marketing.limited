<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGeneratedCampaign extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'cmis.ai_generated_campaigns';

    protected $primaryKey = 'campaign_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'objective_code',
        'recommended_principle',
        'linked_kpi',
        'ai_summary',
        'ai_design_guideline',
        'created_at',
        'engine',
    ];

    protected $casts = [
        'campaign_id' => 'string',
        'org_id' => 'string',
        'created_at' => 'datetime',
    ];
}
