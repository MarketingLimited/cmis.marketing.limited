<?php

namespace App\Models;

use App\Models\BaseModel;

class AwarenessStages extends BaseModel
{
    protected $table = 'cmis.awareness_stages';

    protected $fillable = [
        'stage',
    ];
    public $timestamps = false;
}
