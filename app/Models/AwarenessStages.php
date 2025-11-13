<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AwarenessStages extends Model
{
    protected $table = 'cmis.awareness_stages';

    protected $fillable = [
        'stage',
    ];
    public $incrementing = false;

    public $timestamps = false;
}
