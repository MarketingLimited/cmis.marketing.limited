<?php

namespace App\Models\Marketing;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class VisualScenario extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis_marketing.visual_scenarios';
    protected $primaryKey = 'scenario_id';
    protected $fillable = [
        'scenario_id',
        'creative_id',
        'topic',
        'tone',
        'variant_index',
        'scene_order',
        'scene_type',
        'scene_text',
        'visual_hint',
        'duration_seconds',
    ];

    protected $casts = [
        'scenes' => 'array',
        'storyboard' => 'array',
        'shot_list' => 'array',
        'props_needed' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class, 'org_id', 'org_id');

        }
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Campaign::class, 'campaign_id', 'campaign_id');

        }
    public function concept(): BelongsTo
    {
        return $this->belongsTo(VisualConcept::class, 'concept_id', 'concept_id');

        }
    public function scopeByStatus($query, $status): Builder
    {
        return $query->where('status', $status);

        }
    public function getTotalScenes()
    {
        return is_array($this->scenes) ? count($this->scenes) : 0;
}
}
}
