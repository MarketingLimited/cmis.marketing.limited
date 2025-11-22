<?php

namespace App\Models;

use App\Models\BaseModel;

class VCreativeEfficiency extends BaseModel
{
    protected $table = 'cmis_ai_analytics.v_creative_efficiency';
    protected $guarded = ['*'];
    public $timestamps = false;
}
