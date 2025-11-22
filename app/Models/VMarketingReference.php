<?php

namespace App\Models;

use App\Models\BaseModel;

class VMarketingReference extends BaseModel
{
    protected $table = 'cmis.v_marketing_reference';
    protected $guarded = ['*'];
    public $timestamps = false;
}
