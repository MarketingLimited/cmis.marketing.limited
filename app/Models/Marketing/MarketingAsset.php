<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketingAsset extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'cmis_marketing.assets';
    protected $primaryKey = 'asset_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id',
        'campaign_id',
        'asset_name',
        'asset_type',
        'file_path',
        'file_url',
        'file_size_bytes',
        'mime_type',
        'dimensions',
        'duration_seconds',
        'thumbnail_url',
        'status',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'dimensions' => 'array',
        'duration_seconds' => 'integer',
        'tags' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class, 'org_id', 'org_id');
    }

    public function campaign()
    {
        return $this->belongsTo(\App\Models\Campaign::class, 'campaign_id', 'campaign_id');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('asset_type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
