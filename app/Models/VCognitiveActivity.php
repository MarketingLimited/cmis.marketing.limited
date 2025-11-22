<?php

namespace App\Models;

use App\Models\BaseModel;

class VCognitiveActivity extends BaseModel
{
    protected $table = 'cmis_knowledge.v_cognitive_activity';
    protected $guarded = ['*'];
    public $timestamps = false;
}
