<?php

namespace App\Models;

use App\Models\BaseModel;

class VSearchPerformance extends BaseModel
{
    protected $table = 'cmis_knowledge.v_search_performance';
    protected $guarded = ['*'];
    public $timestamps = false;
}
