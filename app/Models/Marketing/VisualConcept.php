<?php

namespace App\Models\Marketing;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class VisualConcept extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis_marketing.visual_concepts';
    protected $primaryKey = 'concept_id';
    protected $fillable = [
        'concept_id',
        'asset_id',
        'visual_prompt',
        'style',
        'palette',
        'emotion',
        'focus_keywords',
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

    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class, 'org_id', 'org_id');

        }
    public function scopePopular($query, $minUsage = 5): Builder
    {
        return $query->where('usage_count', '>=', $minUsage)
            ->orderByDesc('usage_count');

    public function scopeHighPerformance($query, $threshold = 0.7): Builder
    {
        return $query->where('performance_score', '>=', $threshold)
            ->orderByDesc('performance_score');

    public function incrementUsage()
    {
        $this->increment('usage_count');
}
