<?php

namespace App\Models;

use App\Models\BaseModel;

class Frameworks extends BaseModel
{
    protected $table = 'cmis.frameworks';

    protected $fillable = [
        'framework_id',
        'framework_name',
        'framework_type',
        'description',
    ];
    protected $primaryKey = 'framework_id';

    public $timestamps = false;
}
