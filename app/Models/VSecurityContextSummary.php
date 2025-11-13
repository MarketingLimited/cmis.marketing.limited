<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VSecurityContextSummary extends Model
{
    protected $table = 'cmis.v_security_context_summary';
    protected $guarded = ['*'];
    public $incrementing = false;

    public $timestamps = false;
}
