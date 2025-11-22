<?php

namespace App\Models\Permission;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Permission extends BaseModel
{
    use HasFactory;

    protected $table = 'cmis.permissions';
    protected $primaryKey = 'permission_id';

    protected $fillable = [
        'permission_id',
        'name',
        'slug',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
