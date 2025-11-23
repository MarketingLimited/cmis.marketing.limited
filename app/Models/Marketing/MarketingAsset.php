<?php

namespace App\Models\Marketing;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class MarketingAsset extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis_marketing.assets';
    protected $primaryKey = 'asset_id';
    protected $fillable = [
        'asset_id',
        'task_id',
        'platform',
        'asset_type',
        'content',
        'generated_by',
        'confidence',
    ];

    protected $casts = ['file_size_bytes' => 'integer',
        'dimensions' => 'array',
        'duration_seconds' => 'integer',
        'tags' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'content' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class, 'org_id', 'org_id');

        }
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Campaign::class, 'campaign_id', 'campaign_id');

        }
    public function scopeByType($query, $type): Builder
    {
        return $query->where('asset_type', $type);

        }
    public function scopeActive($query): Builder
    {
        return $query->where('status', 'active');
}
}
}
