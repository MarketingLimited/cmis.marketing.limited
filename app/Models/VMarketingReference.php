<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VMarketingReference extends Model
{
    protected $table = 'cmis.v_marketing_reference';
    protected $guarded = ['*'];
    public $incrementing = false;

    public $timestamps = false;
}
