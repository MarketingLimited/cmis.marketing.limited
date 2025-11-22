<?php

namespace App\Models;

use App\Models\BaseModel;

class VKpiSummary extends BaseModel
{
    protected $table = 'cmis_ai_analytics.v_kpi_summary';
    protected $guarded = ['*'];
    public $timestamps = false;
}
