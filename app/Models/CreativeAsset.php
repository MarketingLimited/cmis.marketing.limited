<?php

namespace App\Models;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreativeAsset extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.creative_assets';

    protected $primaryKey = 'asset_id';

    public $timestamps = true;

    protected $fillable = [
        'asset_id',
        'org_id',
        'campaign_id',
        'strategy',
        'channel_id',
        'format_id',
        'variation_tag',
        'copy_block',
        'art_direction',
        'compliance_meta',
        'final_copy',
        'used_fields',
        'compliance_report',
        'status',
        'context_id',
        'example_id',
        'brief_id',
        'creative_context_id',
        'provider',
        'deleted_by',
    ];

    protected $casts = [
        'asset_id' => 'string',
        'org_id' => 'string',
        'campaign_id' => 'string',
        'context_id' => 'string',
        'example_id' => 'string',
        'brief_id' => 'string',
        'creative_context_id' => 'string',
        'deleted_by' => 'string',
        'channel_id' => 'integer',
        'format_id' => 'integer',
        'strategy' => 'array',
        'art_direction' => 'array',
        'compliance_meta' => 'array',
        'final_copy' => 'array',
        'used_fields' => 'array',
        'compliance_report' => 'array',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
}
}
