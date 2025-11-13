<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VKpiSummary extends Model
{
    protected $table = 'cmis_ai_analytics.v_kpi_summary';
    protected $guarded = ['*'];
    public $incrementing = false;

    public $timestamps = false;
}
