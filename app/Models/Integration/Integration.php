<?php

namespace App\Models\Integration;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Integration as CoreIntegration;

class Integration extends CoreIntegration
{
    protected $table = 'cmis.integrations';

    protected $primaryKey = 'integration_id';

    protected $fillable = [
        'integration_id',
        'org_id',
        'platform',
        'name',
        'account_id',
        'credentials',
        'access_token',
        'is_active',
        'business_id',
        'username',
        'last_synced_at',
        'sync_status',
        'sync_metadata',
    ];

    protected $casts = [
        'integration_id' => 'string',
        'org_id' => 'string',
        'credentials' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'sync_metadata' => 'array',
    ];
}
