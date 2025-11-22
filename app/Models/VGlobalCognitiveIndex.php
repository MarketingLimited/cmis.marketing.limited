<?php

namespace App\Models;

use App\Models\BaseModel;

class VGlobalCognitiveIndex extends BaseModel
{
    protected $table = 'cmis_knowledge.v_global_cognitive_index';
    protected $guarded = ['*'];
    public $timestamps = false;
}
