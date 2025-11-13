<?php

namespace App\Models\CMIS;

use Illuminate\Database\Eloquent\Model;

class KnowledgeItem extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'cmis_knowledge.index';
    protected $primaryKey = 'knowledge_id';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'knowledge_id',
        'domain',
        'category',
        'topic',
        'keywords',
        'tier',
        'token_budget',
        'supersedes_knowledge_id',
        'is_deprecated',
        'last_verified_at',
        'total_chunks',
        'has_children',
        'last_audit_status',
        'report_phase',
        'topic_embedding',
        'intent_vector',
        'direction_vector',
        'purpose_vector',
        'verification_confidence',
        'verification_source',
        'is_verified_by_ai',
        'keywords_embedding',
        'embedding_model',
        'embedding_updated_at',
        'embedding_version',
    ];
    
    protected $casts = ['keywords' => 'array',
        'topic_embedding' => 'array',
        'keywords_embedding' => 'array',
        'semantic_fingerprint' => 'array',
        'is_deprecated' => 'boolean',
        'embedding_updated_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'has_children' => 'boolean',
        'is_verified_by_ai' => 'boolean',
    ];
    
    /**
     * Get content from appropriate table
     */
    public function getContent(): ?string
    {
        $category = $this->category;
        $tables = [
            'dev' => 'cmis_knowledge.dev',
            'marketing' => 'cmis_knowledge.marketing',
            'org' => 'cmis_knowledge.org',
            'research' => 'cmis_knowledge.research'
        ];
        
        if (!isset($tables[$category])) {
            return null;
        }
        
        $result = \DB::connection($this->connection)
            ->table($tables[$category])
            ->where('knowledge_id', $this->knowledge_id)
            ->first();
            
        return $result ? $result->content : null;
    }
    
    /**
     * Scope for pending embeddings
     */
    public function scopePendingEmbeddings($query)
    {
        return $query->whereNull('topic_embedding')
                    ->where('is_deprecated', false)
                    ->orderBy('tier', 'asc')
                    ->orderBy('last_verified_at', 'desc');
    }
}