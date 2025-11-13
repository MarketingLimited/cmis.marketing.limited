<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VisualConcept extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'cmis_marketing.visual_concepts';
    protected $primaryKey = 'concept_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id',
        'concept_name',
        'description',
        'visual_elements',
        'color_palette',
        'mood',
        'style',
        'target_audience',
        'usage_count',
        'performance_score',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'visual_elements' => 'array',
        'color_palette' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'usage_count' => 'integer',
        'performance_score' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class, 'org_id', 'org_id');
    }

    public function scopePopular($query, $minUsage = 5)
    {
        return $query->where('usage_count', '>=', $minUsage)
            ->orderByDesc('usage_count');
    }

    public function scopeHighPerformance($query, $threshold = 0.7)
    {
        return $query->where('performance_score', '>=', $threshold)
            ->orderByDesc('performance_score');
    }

    public function incrementUsage()
    {
        $this->increment('usage_count');
    }
}
