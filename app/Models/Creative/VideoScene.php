<?php

namespace App\Models\Creative;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoScene extends Model
{
    use HasFactory;

    protected $table = 'cmis.video_scenes';
    protected $primaryKey = 'scene_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'template_id',
        'asset_id',
        'scene_number',
        'scene_type',
        'duration',
        'content',
        'visual_elements',
        'audio_elements',
        'text_overlays',
        'transitions',
        'effects',
        'metadata',
        'provider',
    ];

    protected $casts = [
        'scene_id' => 'string',
        'template_id' => 'string',
        'asset_id' => 'string',
        'scene_number' => 'integer',
        'duration' => 'integer',
        'visual_elements' => 'array',
        'audio_elements' => 'array',
        'text_overlays' => 'array',
        'transitions' => 'array',
        'effects' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the video template
     */
    public function template()
    {
        return $this->belongsTo(VideoTemplate::class, 'template_id', 'template_id');
    }

    /**
     * Get the creative asset
     */
    public function asset()
    {
        return $this->belongsTo(\App\Models\CreativeAsset::class, 'asset_id', 'asset_id');
    }

    /**
     * Scope by scene type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('scene_type', $type);
    }

    /**
     * Scope ordered by scene number
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('scene_number');
    }
}
