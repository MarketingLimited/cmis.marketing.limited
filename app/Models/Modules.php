<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modules extends Model
{
    protected $table = 'public.modules';
    protected $primaryKey = 'module_id';

    public $timestamps = false;
}
