<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContextsUnified extends Model
{
    protected $table = 'cmis.contexts_unified';
    protected $guarded = ['*'];

    public $timestamps = false;
}
