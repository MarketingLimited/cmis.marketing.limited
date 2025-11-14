<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledPost extends Model
{
    use HasUuids;

    protected $connection = 'pgsql';

    protected $table = 'cmis.scheduled_posts';

    protected $primaryKey = 'post_id';

    public $incrementing = false;

    protected $keyType = 'string';

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

    public function org(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }
}
