<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialPostMetric extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'cmis.social_post_metrics';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'org_id',
        'integration_id',
        'post_external_id',
        'social_post_id',
        'metric',
        'value',
        'fetched_at',
        'provider',
    ];

    protected $casts = [
        'integration_id' => 'string',
        'social_post_id' => 'string',
        'metric_date' => 'date',
        'impressions' => 'integer',
        'reach' => 'integer',
        'likes' => 'integer',
        'comments' => 'integer',
        'saves' => 'integer',
        'shares' => 'integer',
    ];
}
