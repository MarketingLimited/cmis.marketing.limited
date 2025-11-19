<?php

namespace App\Models\Team;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TeamMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';
    protected $table = 'cmis.team_members';
    protected $primaryKey = 'team_member_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
            // Also generate member_id (database primary key)
            if (empty($model->member_id)) {
                $model->member_id = (string) Str::uuid();
            }
        });
    }

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
