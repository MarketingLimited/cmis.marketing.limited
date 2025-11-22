<?php

namespace App\Models;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;

class AiRecommendation extends BaseModel
{
    
    protected $table = 'cmis.predictive_visual_engine';

    protected $primaryKey = 'prediction_id';

    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'campaign_id',
        'predicted_ctr',
        'predicted_engagement',
        'predicted_trust_index',
        'confidence_level',
        'visual_factor_weight',
        'prediction_summary',
        'created_at',
    ];

    protected $casts = [
        'prediction_id' => 'string',
        'org_id' => 'string',
        'campaign_id' => 'string',
        'predicted_ctr' => 'float',
        'predicted_engagement' => 'float',
        'predicted_trust_index' => 'float',
        'confidence_level' => 'float',
        'visual_factor_weight' => 'array',
        'created_at' => 'datetime',
    ];
}
