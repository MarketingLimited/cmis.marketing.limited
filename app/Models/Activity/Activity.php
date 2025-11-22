<?php

namespace App\Models\Activity;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Activity extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.activities';
    protected $primaryKey = 'activity_id';

    protected $fillable = [
        'activity_id',
        'org_id',
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
