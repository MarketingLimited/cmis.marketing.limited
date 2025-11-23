<?php

namespace App\Models\Content;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledPost extends BaseModel
{
    use HasOrganization;

    protected $table = 'cmis.scheduled_posts';

    protected $primaryKey = 'post_id';

    protected $fillable = [
        'post_id',
        'org_id',
        'platform',
        'content',
        'scheduled_time',
        'status',
        'payload',
        'last_error',
        'processed_at',
    ];

    protected $casts = [
        'post_id' => 'string',
        'org_id' => 'string',
        'scheduled_time' => 'datetime',
        'processed_at' => 'datetime',
        'payload' => 'array',
    ];
}
