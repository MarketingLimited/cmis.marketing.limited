<?php

namespace App\Models\Content;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class ContentPlanItem extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.content_items';
    protected $primaryKey = 'item_id';

    protected $fillable = [
        'item_id',
        'plan_id',
        'channel_id',
        'format_id',
        'scheduled_at',
        'title',
        'brief',
        'asset_id',
        'status',
        'context_id',
        'example_id',
        'creative_context_id',
        'provider',
        'org_id',
    ];

    protected $casts = [
        'brief' => 'array',
        'scheduled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
