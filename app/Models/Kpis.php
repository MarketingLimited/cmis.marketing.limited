<?php

namespace App\Models;

use App\Models\BaseModel;

class Kpis extends BaseModel
{
    protected $table = 'cmis.kpis';

    protected $fillable = [
        'kpi',
        'description',
    ];
    protected $primaryKey = 'kpi';

    public $timestamps = false;
}
