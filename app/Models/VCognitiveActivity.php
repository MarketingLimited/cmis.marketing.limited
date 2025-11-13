<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VCognitiveActivity extends Model
{
    protected $table = 'cmis_knowledge.v_cognitive_activity';
    protected $guarded = ['*'];
    public $incrementing = false;

    public $timestamps = false;
}
