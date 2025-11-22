<?php

namespace App\Models;

use App\Models\BaseModel;

class VCognitiveVitality extends BaseModel
{
    protected $table = 'cmis_knowledge.v_cognitive_vitality';
    protected $guarded = ['*'];
    public $timestamps = false;
}
