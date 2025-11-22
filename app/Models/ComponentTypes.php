<?php

namespace App\Models;

use App\Models\BaseModel;

class ComponentTypes extends BaseModel
{
    protected $table = 'cmis.component_types';

    protected $fillable = [
        'type_code',
    ];
    public $timestamps = false;
}
