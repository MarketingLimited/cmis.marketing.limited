<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelFormat extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'cmis.channel_formats';

    protected $primaryKey = 'format_id';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'format_id',
        'channel_id',
        'code',
        'ratio',
        'length_hint',
    ];

    protected $casts = [
        'format_id' => 'int',
        'channel_id' => 'int',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id', 'channel_id');
    }
}
