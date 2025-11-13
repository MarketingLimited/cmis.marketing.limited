<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modules extends Model
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
