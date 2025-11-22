<?php

namespace App\Models\Lead;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Lead extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.leads';
    protected $primaryKey = 'lead_id';

    protected $fillable = [
        'lead_id',
        'org_id',
        'campaign_id',
        'name',
        'email',
        'phone',
        'source',
        'status',
        'score',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'score' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
