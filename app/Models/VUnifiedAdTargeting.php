<?php

namespace App\Models;

use App\Models\BaseModel;

class VUnifiedAdTargeting extends BaseModel
{
    protected $table = 'cmis.v_unified_ad_targeting';
    protected $guarded = ['*'];
    protected $casts = [
        'demographics' => 'array',
        'interests' => 'array',
        'behaviors' => 'array',
        'location' => 'array',
        'keywords' => 'array',
        'custom_audience' => 'array',
        'lookalike_audience' => 'array',
        'advantage_plus' => 'array',
    ];
    public $timestamps = false;
}
