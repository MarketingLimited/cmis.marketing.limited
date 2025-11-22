<?php

namespace App\Models\Log;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ApiLog extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.api_logs';
    protected $primaryKey = 'log_id';

    protected $fillable = [
        'log_id',
        'org_id',
        'user_id',
        'method',
        'url',
        'request_data',
        'response_data',
        'status_code',
        'duration',
        'ip_address',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'status_code' => 'integer',
        'duration' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
