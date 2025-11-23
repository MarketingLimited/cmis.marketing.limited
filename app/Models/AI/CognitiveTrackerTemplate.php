<?php

namespace App\Models\AI;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class CognitiveTrackerTemplate extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.cognitive_tracker_template';
    protected $primaryKey = 'tracker_id';
    protected $fillable = [
        'tracker_id',
        'org_id',
        'campaign_id',
        'record_date',
        'platform',
        'content_type',
        'visual_factor',
        'ctr',
        'engagement_rate',
        'trust_index',
        'visual_insight',
        'provider',
    ];

    protected $casts = [
        'tracking_pattern' => 'array',
        'metrics' => 'array',
        'thresholds' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Scopes
    public function scopeActive($query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category): Builder
    {
        return $query->where('category', $category);
    }

    // Helpers
    public function activate()
    {
        $this->is_active = true;
        return $this->save();
    }

    public function deactivate()
    {
        $this->is_active = false;
        return $this->save();
    }
}
