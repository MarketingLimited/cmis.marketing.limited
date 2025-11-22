<?php

namespace App\Models;

use App\Models\BaseModel;

class ProofLayers extends BaseModel
{
    protected $table = 'cmis.proof_layers';

    protected $fillable = [
        'level',
    ];
    public $timestamps = false;
}
