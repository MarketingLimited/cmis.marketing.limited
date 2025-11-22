<?php

namespace App\Models;

use App\Models\BaseModel;

class Kpi extends BaseModel
{
    
    protected $table = 'cmis.kpis';

    protected $primaryKey = 'kpi';

    public $timestamps = false;

    protected $fillable = [
        'kpi',
        'description',
    ];

    protected $casts = [
        'kpi' => 'string',
    ];
}
