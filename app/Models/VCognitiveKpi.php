<?php

namespace App\Models;

use App\Models\BaseModel;

class VCognitiveKpi extends BaseModel
{
    protected $table = 'cmis_system_health.v_cognitive_kpi';
    protected $guarded = ['*'];
    public $timestamps = false;
}
