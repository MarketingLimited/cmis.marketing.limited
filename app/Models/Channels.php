<?php

namespace App\Models;

use App\Models\BaseModel;

class Channels extends BaseModel
{
    protected $table = 'cmis.channels';

    protected $fillable = [
        'channel_id',
        'code',
        'name',
        'constraints',
    ];
    protected $casts = [
        'constraints' => 'array',
    ];
    protected $primaryKey = 'channel_id';

    public $timestamps = false;
}
