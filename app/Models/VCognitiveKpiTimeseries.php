<?php

namespace App\Models;

use App\Models\BaseModel;

class VCognitiveKpiTimeseries extends BaseModel
{
    protected $table = 'cmis_system_health.v_cognitive_kpi_timeseries';
    protected $guarded = ['*'];
    public $timestamps = false;
}
