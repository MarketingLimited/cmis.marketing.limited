<?php

namespace App\Models;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Integration extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.integrations';
    protected $primaryKey = 'integration_id';

    protected $fillable = [
        'integration_id',
        'org_id',
        'platform',
        'account_id',
        'account_name',
        'account_username',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'expires_at',
        'is_active',
        'status',
        'scopes',
        'metadata',
    ];

    protected $casts = [
        'scopes' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];
}
