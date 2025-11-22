<?php

namespace App\Models;

use App\Models\BaseModel;

class NamingTemplates extends BaseModel
{
    protected $table = 'public.naming_templates';

    protected $fillable = [
        'naming_id',
        'scope',
        'template',
    ];
    protected $primaryKey = 'naming_id';

    public $timestamps = false;
}
