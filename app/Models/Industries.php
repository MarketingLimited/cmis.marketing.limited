<?php

namespace App\Models;

use App\Models\BaseModel;

class Industries extends BaseModel
{
    protected $table = 'cmis.industries';

    protected $fillable = [
        'industry_id',
        'name',
    ];
    protected $primaryKey = 'industry_id';

    public $timestamps = false;
}
