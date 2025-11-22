<?php

namespace App\Models;

use App\Models\BaseModel;

class ChannelFormats extends BaseModel
{
    protected $table = 'cmis.channel_formats';

    protected $fillable = [
        'format_id',
        'channel_id',
        'code',
        'ratio',
        'length_hint',
    ];
    protected $primaryKey = 'format_id';

    public $timestamps = false;
}
