<?php

namespace App\Models\Workflow;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Workflow extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.workflows';
    protected $primaryKey = 'workflow_id';

    protected $fillable = [
        'workflow_id',
        'org_id',
        'name',
        'trigger',
        'actions',
        'is_active',
    ];

    protected $casts = [
        'actions' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
