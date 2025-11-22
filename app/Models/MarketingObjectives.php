<?php

namespace App\Models;

use App\Models\BaseModel;

class MarketingObjectives extends BaseModel
{
    protected $table = 'cmis.marketing_objectives';
    protected $guarded = ['*'];

    public $timestamps = false;
}
