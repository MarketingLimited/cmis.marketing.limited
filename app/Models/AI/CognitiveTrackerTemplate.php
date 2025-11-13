<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CognitiveTrackerTemplate extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.cognitive_tracker_template';
    protected $primaryKey = 'tracker_id';
    public $incrementing = false;
    protected $keyType = 'string';

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
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
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
