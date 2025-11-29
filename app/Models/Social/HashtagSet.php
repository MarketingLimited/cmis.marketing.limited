<?php

namespace App\Models\Social;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;

class HashtagSet extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis.hashtag_sets';

    protected $fillable = [
        'org_id',
        'name',
        'hashtags',
        'usage_count',
    ];

    protected $casts = [
        'hashtags' => 'array',
        'usage_count' => 'integer',
    ];
}
