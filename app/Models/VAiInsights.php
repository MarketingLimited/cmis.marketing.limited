<?php

namespace App\Models;

use App\Models\BaseModel;

class VAiInsights extends BaseModel
{
    protected $table = 'cmis.v_ai_insights';
    protected $guarded = ['*'];
    public $timestamps = false;
}
