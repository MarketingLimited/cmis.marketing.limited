<?php

namespace App\Models;

use App\Models\BaseModel;

class VCognitiveDashboard extends BaseModel
{
    protected $table = 'cmis_system_health.v_cognitive_dashboard';
    protected $guarded = ['*'];
    public $timestamps = false;
}
