<?php

namespace App\Models;

use App\Models\BaseModel;

class Tones extends BaseModel
{
    protected $table = 'cmis.tones';

    protected $fillable = [
        'tone',
    ];
    public $timestamps = false;
}
