<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VCacheStatus extends Model
{
    protected $table = 'cmis.v_cache_status';
    protected $guarded = ['*'];
    public $incrementing = false;

    public $timestamps = false;
}
