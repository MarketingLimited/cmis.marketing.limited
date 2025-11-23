<?php

namespace App\Models\Knowledge;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class OrgKnowledge extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.org_knowledge';
    protected $primaryKey = 'org_knowledge_id';
    protected $fillable = [
        'org_id',
        'title',
        'content',
        'knowledge_type',
        'category',
        'tags',
        'visibility',
        'is_confidential',
        'access_level',
        'created_by',
        'last_modified_by',
        'version',
        'related_documents',
        'expiry_date',
        'provider',
    ];

    protected $casts = [
        'org_knowledge_id' => 'string',
        'org_id' => 'string',
        'created_by' => 'string',
        'last_modified_by' => 'string',
        'tags' => 'array',
        'related_documents' => 'array',
        'is_confidential' => 'boolean',
        'version' => 'integer',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    

    /**
     * Get the creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');

    }
    /**
     * Get the last modifier
     */
    public function lastModifier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'last_modified_by', 'user_id');

    }
    /**
     * Get related knowledge index entries
     */
    public function knowledgeEntries()
    {
        return KnowledgeIndex::where('source_type', 'org_knowledge')
            ->where('source_id', $this->org_knowledge_id);

    }
    /**
     * Scope by knowledge type
     */
    public function scopeByType($query, string $type): Builder
    {
        return $query->where('knowledge_type', $type);

    }
    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category): Builder
    {
        return $query->where('category', $category);

    }
    /**
     * Scope by visibility
     */
    public function scopeByVisibility($query, string $visibility): Builder
    {
        return $query->where('visibility', $visibility);

    }
    /**
     * Scope non-confidential
     */
    public function scopePublic($query): Builder
    {
        return $query->where('is_confidential', false);

    }
    /**
     * Scope non-expired
     */
    public function scopeActive($query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expiry_date')
                ->orWhere('expiry_date', '>=', now());

    }
    /**
     * Check if knowledge has expired
     */
    public function hasExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;

        return $this->expiry_date->isPast();
}
}
}
}
