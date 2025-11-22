<?php

namespace App\Models\Webhook;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Webhook extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.webhooks';
    protected $primaryKey = 'webhook_id';

    protected $fillable = [
        'webhook_id',
        'org_id',
        'url',
        'events',
        'secret',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'events' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'secret',
    ];
}
