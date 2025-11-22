<?php

namespace App\Models;

use App\Models\BaseModel;

class ContextsUnified extends BaseModel
{
    protected $table = 'cmis.contexts_unified';
    protected $guarded = ['*'];

    public $timestamps = false;
}
