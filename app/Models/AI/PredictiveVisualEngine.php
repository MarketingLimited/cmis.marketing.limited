<?php

namespace App\Models\AI;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PredictiveVisualEngine extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.predictive_visual_engine';
    protected $primaryKey = 'prediction_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'prediction_id',
        'org_id',
        'campaign_id',
        'predicted_ctr',
        'predicted_engagement',
        'predicted_trust_index',
        'confidence_level',
        'visual_factor_weight',
        'prediction_summary',
        'provider',
    ];

    protected $casts = ['predicted_performance' => 'float',
        'confidence_score' => 'float',
        'visual_elements' => 'array',
        'color_palette' => 'array',
        'composition_score' => 'float',
        'emotion_score' => 'float',
        'attention_score' => 'float',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'visual_factor_weight' => 'array',
    ];

    // Relationships
    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class, 'org_id', 'org_id');
    }

    public function campaign()
    {
        return $this->belongsTo(\App\Models\Campaign::class, 'campaign_id', 'campaign_id');
    }

    // Scopes
    public function scopeHighConfidence($query, $threshold = 0.8)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    public function scopePositivePerformance($query, $threshold = 0.7)
    {
        return $query->where('predicted_performance', '>=', $threshold);
    }

    public function scopeByOrg($query, $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    // Helpers
    public function isHighPerformance()
    {
        return $this->predicted_performance >= 0.8 && $this->confidence_score >= 0.75;
    }

    public function getOverallScore()
    {
        return ($this->composition_score + $this->emotion_score + $this->attention_score) / 3;
    }

    public function getTopColors($limit = 5)
    {
        if (!$this->color_palette || !is_array($this->color_palette)) {
            return [];
        }

        return array_slice($this->color_palette, 0, $limit);
    }
}
