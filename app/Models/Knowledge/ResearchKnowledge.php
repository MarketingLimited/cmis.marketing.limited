<?php

namespace App\Models\Knowledge;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class ResearchKnowledge extends BaseModel
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.research_knowledge';
    protected $primaryKey = 'research_id';
    protected $fillable = [
        'title',
        'abstract',
        'full_content',
        'authors',
        'publication_date',
        'source',
        'doi',
        'keywords',
        'methodology',
        'findings',
        'implications',
        'limitations',
        'citations_count',
        'impact_factor',
        'field_of_study',
        'research_type',
        'peer_reviewed',
        'provider',
    ];

    protected $casts = [
        'research_id' => 'string',
        'authors' => 'array',
        'publication_date' => 'date',
        'keywords' => 'array',
        'findings' => 'array',
        'implications' => 'array',
        'limitations' => 'array',
        'citations_count' => 'integer',
        'impact_factor' => 'float',
        'peer_reviewed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get related knowledge index entries
     */
    public function knowledgeEntries()
    : mixed {
        return KnowledgeIndex::where('source_type', 'research_knowledge')
            ->where('source_id', $this->research_id);

    }
    /**
     * Scope peer-reviewed only
     */
    public function scopePeerReviewed($query): Builder
    {
        return $query->where('peer_reviewed', true);

    }
    /**
     * Scope by field of study
     */
    public function scopeByField($query, string $field): Builder
    {
        return $query->where('field_of_study', $field);

    }
    /**
     * Scope by research type
     */
    public function scopeByType($query, string $type): Builder
    {
        return $query->where('research_type', $type);

    }
    /**
     * Scope high impact
     */
    public function scopeHighImpact($query, float $threshold = 5.0): Builder
    {
        return $query->where('impact_factor', '>=', $threshold)
            ->orderBy('impact_factor', 'desc');

    }
    /**
     * Scope highly cited
     */
    public function scopeHighlyCited($query, int $threshold = 100): Builder
    {
        return $query->where('citations_count', '>=', $threshold)
            ->orderBy('citations_count', 'desc');
}
}
