<?php

namespace App\Models;

use App\Models\BaseModel;

class Modules extends BaseModel
{
    protected $table = 'public.modules';

    protected $fillable = [
        'module_id',
        'code',
        'name',
        'version',
    ];
    protected $primaryKey = 'module_id';

    public $timestamps = false;
}
