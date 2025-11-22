<?php

namespace App\Models\Subscription;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Subscription extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.subscriptions';
    protected $primaryKey = 'subscription_id';

    protected $fillable = [
        'subscription_id',
        'org_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
