<?php

namespace App\Models\Knowledge;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class MarketingKnowledge extends BaseModel
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.marketing_knowledge';
    protected $primaryKey = 'marketing_id';
    protected $fillable = [
        'topic',
        'category',
        'content',
        'best_practices',
        'case_studies',
        'metrics',
        'target_audience',
        'channels',
        'industry',
        'market_segment',
        'effectiveness_score',
        'references',
        'tags',
        'provider',
    ];

    protected $casts = [
        'marketing_id' => 'string',
        'best_practices' => 'array',
        'case_studies' => 'array',
        'metrics' => 'array',
        'target_audience' => 'array',
        'channels' => 'array',
        'effectiveness_score' => 'float',
        'references' => 'array',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get related knowledge index entries
     */
    public function knowledgeEntries()
    {
        return KnowledgeIndex::where('source_type', 'marketing_knowledge')
            ->where('source_id', $this->marketing_id);

    }
    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);

    }
    /**
     * Scope by industry
     */
    public function scopeByIndustry($query, string $industry)
    {
        return $query->where('industry', $industry);

    }
    /**
     * Scope by market segment
     */
    public function scopeBySegment($query, string $segment)
    {
        return $query->where('market_segment', $segment);

    }
    /**
     * Scope high effectiveness
     */
    public function scopeHighEffectiveness($query, float $threshold = 0.7)
    {
        return $query->where('effectiveness_score', '>=', $threshold)
            ->orderBy('effectiveness_score', 'desc');
}
}
