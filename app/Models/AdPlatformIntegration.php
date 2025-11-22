<?php

namespace App\Models;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class AdPlatformIntegration extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.ad_platform_integrations';
    protected $primaryKey = 'integration_id';

    protected $fillable = [
        'integration_id', 'org_id', 'platform', 'credentials'
    ];

    protected $casts = [
        'credentials' => 'encrypted',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
