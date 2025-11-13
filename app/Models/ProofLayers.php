<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProofLayers extends Model
{
    protected $table = 'cmis.proof_layers';

    protected $fillable = [
        'level',
    ];
    public $incrementing = false;

    public $timestamps = false;
}
