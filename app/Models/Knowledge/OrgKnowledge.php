<?php

namespace App\Models\Knowledge;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgKnowledge extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.org_knowledge';
    protected $primaryKey = 'org_knowledge_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

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
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');
    }

    /**
     * Get the last modifier
     */
    public function lastModifier()
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
    public function scopeByType($query, string $type)
    {
        return $query->where('knowledge_type', $type);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope by visibility
     */
    public function scopeByVisibility($query, string $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    /**
     * Scope non-confidential
     */
    public function scopePublic($query)
    {
        return $query->where('is_confidential', false);
    }

    /**
     * Scope non-expired
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiry_date')
                ->orWhere('expiry_date', '>=', now());
        });
    }

    /**
     * Check if knowledge has expired
     */
    public function hasExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isPast();
    }
}
