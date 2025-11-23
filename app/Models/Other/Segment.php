<?php

namespace App\Models\Other;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Segment extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.segments';

    protected $primaryKey = 'segment_id';

    public $timestamps = false;

    protected $fillable = [
        'segment_id',
        'org_id',
        'name',
        'persona',
        'notes',
        'provider',
    ];

    protected $casts = [
        'segment_id' => 'string',
        'org_id' => 'string',
        'persona' => 'array',
        'deleted_at' => 'datetime',
    ];
}
