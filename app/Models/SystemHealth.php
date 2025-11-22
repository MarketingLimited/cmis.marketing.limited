<?php

namespace App\Models;

use App\Models\BaseModel;

class SystemHealth extends BaseModel
{
    protected $table = 'cmis.system_health';

    protected $fillable = [
        'component',
        'total_records',
        'avg_age_seconds',
        'last_activity',
    ];

    public $timestamps = false;
}
