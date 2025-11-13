<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VContextImpact extends Model
{
    protected $table = 'cmis_ai_analytics.v_context_impact';
    protected $guarded = ['*'];
    public $incrementing = false;

    public $timestamps = false;
}
