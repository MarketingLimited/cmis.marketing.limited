<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VCognitiveAdminLog extends Model
{
    protected $table = 'cmis_system_health.v_cognitive_admin_log';
    public $incrementing = false;

    public $timestamps = false;
}
