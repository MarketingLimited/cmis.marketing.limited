<?php

namespace App\Models\Activity;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ActivityLog extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.activity_logs';
    protected $primaryKey = 'log_id';

    protected $fillable = [
        'log_id', 'user_id', 'org_id', 'activity_type', 'description', 'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
