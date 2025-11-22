<?php

namespace App\Models;

use App\Models\BaseModel;

class VTemporalDashboard extends BaseModel
{
    protected $table = 'cmis_knowledge.v_temporal_dashboard';
    protected $guarded = ['*'];
    public $timestamps = false;
}
