<?php

namespace App\Models\Audience;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Audience extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.audiences';
    protected $primaryKey = 'audience_id';

    protected $fillable = [
        'audience_id',
        'org_id',
        'name',
        'description',
        'criteria',
        'size',
    ];

    protected $casts = [
        'criteria' => 'array',
        'size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
