<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VUnifiedAdTargeting extends Model
{
    protected $table = 'cmis.v_unified_ad_targeting';
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
    public $incrementing = false;

    public $timestamps = false;
}
