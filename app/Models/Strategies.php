<?php

namespace App\Models;

use App\Models\BaseModel;

class Strategies extends BaseModel
{
    protected $table = 'cmis.strategies';

    protected $fillable = [
        'strategy',
    ];
    public $timestamps = false;
}
