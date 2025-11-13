<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VCreativeEfficiency extends Model
{
    protected $table = 'cmis_ai_analytics.v_creative_efficiency';
    protected $guarded = ['*'];
    public $incrementing = false;

    public $timestamps = false;
}
