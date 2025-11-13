<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VAiInsights extends Model
{
    protected $table = 'cmis.v_ai_insights';
    protected $guarded = ['*'];
    public $incrementing = false;

    public $timestamps = false;
}
