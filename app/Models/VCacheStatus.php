<?php

namespace App\Models;

use App\Models\BaseModel;

class VCacheStatus extends BaseModel
{
    protected $table = 'cmis.v_cache_status';
    protected $guarded = ['*'];
    public $timestamps = false;
}
