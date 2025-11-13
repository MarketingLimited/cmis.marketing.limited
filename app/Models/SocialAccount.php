<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'cmis.social_accounts';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'org_id',
        'integration_id',
        'account_external_id',
        'username',
        'display_name',
        'profile_picture_url',
        'biography',
        'followers_count',
        'follows_count',
        'media_count',
        'website',
        'category',
        'is_verified',
        'fetched_at',
    ];

    protected $casts = [
        'id' => 'string',
        'org_id' => 'string',
        'integration_id' => 'string',
        'followers_count' => 'integer',
        'follows_count' => 'integer',
        'media_count' => 'integer',
        'is_verified' => 'boolean',
        'fetched_at' => 'datetime',
    ];
}
