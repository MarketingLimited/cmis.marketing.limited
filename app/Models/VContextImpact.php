<?php

namespace App\Models;

use App\Models\BaseModel;

class VContextImpact extends BaseModel
{
    protected $table = 'cmis_ai_analytics.v_context_impact';
    protected $guarded = ['*'];
    public $timestamps = false;
}
