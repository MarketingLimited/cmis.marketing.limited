<?php

namespace App\Models;

use App\Models\BaseModel;

class SocialAccountMetric extends BaseModel
{
    protected $table = 'cmis.social_account_metrics';
    protected $primaryKey = 'integration_id';

    public $timestamps = false;

    protected $fillable = [
        'integration_id',
        'period_start',
        'period_end',
        'followers',
        'reach',
        'impressions',
        'profile_views',
        'provider',
    ];

    protected $casts = [
        'integration_id' => 'string',
        'period_start' => 'date',
        'period_end' => 'date',
        'followers' => 'integer',
        'reach' => 'integer',
        'impressions' => 'integer',
        'profile_views' => 'integer',
    ];
}
