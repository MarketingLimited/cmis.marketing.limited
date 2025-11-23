<?php

namespace App\Models\Marketing;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class VideoScenario extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis_marketing.video_scenarios';
    protected $primaryKey = 'scenario_id';
    protected $fillable = [
        'scenario_id',
        'task_id',
        'asset_id',
        'title',
        'duration_seconds',
        'scenes',
        'tone',
        'goal',
        'confidence',
    ];

    protected $casts = [
        'scenes' => 'array',
        'transitions' => 'array',
        'narration_script' => 'array',
        'duration_seconds' => 'integer',
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
    public function scopeByStatus($query, $status): Builder
    {
        return $query->where('status', $status);

        }
    public function getDurationFormatted()
    {
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
}
}
}
