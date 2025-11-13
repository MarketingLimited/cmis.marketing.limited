<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tones extends Model
{
    protected $table = 'cmis.tones';

    protected $fillable = [
        'tone',
    ];
    public $incrementing = false;

    public $timestamps = false;
}
