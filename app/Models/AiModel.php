<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class AiModel extends Model
{
    use HasUuids;
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
