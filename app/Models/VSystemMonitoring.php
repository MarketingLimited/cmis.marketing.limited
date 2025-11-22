<?php

namespace App\Models;

use App\Models\BaseModel;

class VSystemMonitoring extends BaseModel
{
    protected $table = 'cmis.v_system_monitoring';
    protected $guarded = ['*'];
    public $timestamps = false;
}
