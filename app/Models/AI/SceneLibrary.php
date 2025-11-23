<?php

namespace App\Models\AI;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SceneLibrary extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.scene_library';
    protected $primaryKey = 'scene_id';
    protected $fillable = [
        'scene_id',
        'org_id',
        'name',
        'goal',
        'duration_sec',
        'visual_spec',
        'audio_spec',
        'overlay_rules',
        'anchor',
        'quality_score',
        'tags',
        'provider',
    ];

    protected $casts = ['duration_seconds' => 'integer',
        'visual_elements' => 'array',
        'audio_elements' => 'array',
        'transitions' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'is_template' => 'boolean',
        'usage_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'visual_spec' => 'array',
        'audio_spec' => 'array',
        'overlay_rules' => 'array',
    ];

    // Relationships
    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class, 'org_id', 'org_id');
    }

    // Scopes
    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('scene_type', $type);
    }

    public function scopePopular($query, $minUsage = 10)
    {
        return $query->where('usage_count', '>=', $minUsage)
            ->orderByDesc('usage_count');
    }

    // Helpers
    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    public function getDurationFormatted()
    {
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
