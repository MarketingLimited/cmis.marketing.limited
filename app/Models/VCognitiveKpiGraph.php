<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VCognitiveKpiGraph extends Model
{
    protected $table = 'cmis_system_health.v_cognitive_kpi_graph';
    protected $guarded = ['*'];
    public $incrementing = false;

    public $timestamps = false;
}
