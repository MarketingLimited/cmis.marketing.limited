<?php

namespace App\Models;

use App\Models\BaseModel;

class VCognitiveAdminLog extends BaseModel
{
    protected $table = 'cmis_system_health.v_cognitive_admin_log';
    protected $guarded = ['*'];
    public $timestamps = false;
}
