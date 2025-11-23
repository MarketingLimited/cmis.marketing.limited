<?php

namespace App\Models\Creative;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class VideoTemplate extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.video_templates';
    protected $primaryKey = 'vtpl_id';
    protected $fillable = [
        'vtpl_id',
        'org_id',
        'channel_id',
        'format_id',
        'name',
        'steps',
        'version',
        'provider',
    ];

    protected $casts = ['template_id' => 'string',
        'org_id' => 'string',
        'created_by' => 'string',
        'duration' => 'integer',
        'structure' => 'array',
        'transitions' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'steps' => 'array',
    ];

    

    /**
     * Get the video scenes
     */
    public function scenes()
    {
        return $this->hasMany(VideoScene::class, 'template_id', 'template_id');

    }
    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');

    }
    /**
     * Scope active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);

    }
    /**
     * Scope by style
     */
    public function scopeWithStyle($query, string $style)
    {
        return $query->where('style', $style);
}
}
