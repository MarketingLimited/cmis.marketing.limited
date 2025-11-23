<?php

namespace App\Models\Team;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TeamMember extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.team_members';
    protected $primaryKey = 'team_member_id';
                    // Also generate member_id (database primary key)
            if (empty($model->member_id)) {
                $model->member_id = (string) Str::uuid();

    protected $fillable = [
        'member_id',
        'team_member_id',
        'user_id',
        'org_id',
        'role',
        'permissions',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
}
