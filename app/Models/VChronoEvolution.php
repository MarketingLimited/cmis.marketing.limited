<?php

namespace App\Models;

use App\Models\BaseModel;

class VChronoEvolution extends BaseModel
{
    protected $table = 'cmis_knowledge.v_chrono_evolution';
    protected $guarded = ['*'];
    public $timestamps = false;
}
