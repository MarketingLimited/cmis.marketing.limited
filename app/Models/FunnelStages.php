<?php

namespace App\Models;

use App\Models\BaseModel;

class FunnelStages extends BaseModel
{
    protected $table = 'cmis.funnel_stages';

    protected $fillable = [
        'stage',
    ];
    public $timestamps = false;
}
