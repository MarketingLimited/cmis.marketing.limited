<?php

namespace App\Models\Tag;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Tag extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.tags';
    protected $primaryKey = 'tag_id';

    protected $fillable = [
        'tag_id',
        'org_id',
        'name',
        'slug',
        'color',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
