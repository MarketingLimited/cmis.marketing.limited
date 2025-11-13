<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialPost extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'cmis.social_posts';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'org_id',
        'integration_id',
        'post_external_id',
        'caption',
        'media_url',
        'permalink',
        'media_type',
        'posted_at',
        'metrics',
        'fetched_at',
        'created_at',
    ];

    protected $casts = [
        'id' => 'string',
        'org_id' => 'string',
        'integration_id' => 'string',
        'metrics' => 'array',
        'posted_at' => 'datetime',
        'fetched_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
