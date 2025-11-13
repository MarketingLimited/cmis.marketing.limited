<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemHealth extends Model
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
