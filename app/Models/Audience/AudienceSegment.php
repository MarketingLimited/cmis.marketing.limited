<?php

namespace App\Models\Audience;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class AudienceSegment extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.audience_segments';
    protected $primaryKey = 'segment_id';

    protected $fillable = [
        'segment_id',
        'org_id',
        'name',
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
