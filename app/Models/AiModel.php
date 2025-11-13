<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'cmis_refactored.ai_models';

    protected $primaryKey = 'model_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'model_name',
        'model_family',
        'version',
        'status',
        'trained_at',
        'metadata',
    ];

    protected $casts = [
        'model_id' => 'string',
        'org_id' => 'string',
        'trained_at' => 'datetime',
        'metadata' => 'array',
    ];
}
