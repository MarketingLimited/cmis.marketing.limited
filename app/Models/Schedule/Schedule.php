<?php

namespace App\Models\Schedule;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Schedule extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.schedules';
    protected $primaryKey = 'schedule_id';

    protected $fillable = [
        'schedule_id', 'org_id', 'entity_type', 'entity_id', 'scheduled_at', 'status'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
