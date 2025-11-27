<?php

namespace App\Models;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;

class AiModel extends BaseModel
{
    use HasOrganization;
protected $table = 'cmis.ai_models';

    protected $primaryKey = 'model_id';

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
