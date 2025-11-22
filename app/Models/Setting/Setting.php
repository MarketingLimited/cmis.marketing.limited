<?php

namespace App\Models\Setting;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Setting extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.settings';
    protected $primaryKey = 'setting_id';

    protected $fillable = [
        'setting_id', 'org_id', 'key', 'value', 'type'
    ];

    protected $casts = [
        'value' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
