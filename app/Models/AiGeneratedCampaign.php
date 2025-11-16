<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class AiGeneratedCampaign extends Model
{
    use HasUuids;
    protected $connection = 'pgsql';

    protected $table = 'cmis.ai_generated_campaigns';

    protected $primaryKey = 'campaign_id';

    public $incrementing = false;

    protected $keyType = 'string';

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
