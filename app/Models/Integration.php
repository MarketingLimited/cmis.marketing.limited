<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    protected $table = 'cmis_refactored.integrations';

    protected $fillable = [
        'integration_id',
        'org_id',
        'platform',
        'account_id',
        'username',
        'access_token',
        'is_active',
        'created_at',
        'business_id'
    ];
}
