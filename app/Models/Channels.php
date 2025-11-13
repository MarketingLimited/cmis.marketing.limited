<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channels extends Model
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
