<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreativeAsset extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'cmis.creative_assets';

    protected $primaryKey = 'asset_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
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
        'created_at',
        'context_id',
        'example_id',
        'brief_id',
        'creative_context_id',
    ];

    protected $casts = [
        'asset_id' => 'string',
        'org_id' => 'string',
        'campaign_id' => 'string',
        'strategy' => 'array',
        'art_direction' => 'array',
        'compliance_meta' => 'array',
        'final_copy' => 'array',
        'used_fields' => 'array',
        'compliance_report' => 'array',
        'created_at' => 'datetime',
    ];

    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }
}
