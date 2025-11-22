<?php

namespace App\Models\CustomField;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class CustomField extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.custom_fields';
    protected $primaryKey = 'field_id';

    protected $fillable = [
        'field_id',
        'org_id',
        'name',
        'field_type',
        'default_value',
        'is_required',
        'options',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'options' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
