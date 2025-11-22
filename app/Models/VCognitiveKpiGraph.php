<?php

namespace App\Models;

use App\Models\BaseModel;

class VCognitiveKpiGraph extends BaseModel
{
    protected $table = 'cmis_system_health.v_cognitive_kpi_graph';
    protected $guarded = ['*'];
    public $timestamps = false;
}
