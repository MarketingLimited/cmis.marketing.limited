<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FunnelStages extends Model
{
    protected $table = 'cmis.funnel_stages';
    public $incrementing = false;

    public $timestamps = false;
}
