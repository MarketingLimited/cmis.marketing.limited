<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VChronoEvolution extends Model
{
    protected $table = 'cmis_knowledge.v_chrono_evolution';
    protected $guarded = ['*'];
    public $incrementing = false;

    public $timestamps = false;
}
