<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComponentTypes extends Model
{
    protected $table = 'cmis.component_types';

    protected $fillable = [
        'type_code',
    ];
    public $incrementing = false;

    public $timestamps = false;
}
