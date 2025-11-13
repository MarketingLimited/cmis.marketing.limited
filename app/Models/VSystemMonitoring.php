<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VSystemMonitoring extends Model
{
    protected $table = 'cmis.v_system_monitoring';
    protected $guarded = ['*'];
    public $incrementing = false;

    public $timestamps = false;
}
