<?php

namespace App\Models;

use App\Models\BaseModel;

class VSecurityContextSummary extends BaseModel
{
    protected $table = 'cmis.v_security_context_summary';
    protected $guarded = ['*'];
    public $timestamps = false;
}
