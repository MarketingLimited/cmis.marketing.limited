<?php

namespace App\Models\Knowledge;

use App\Models\Concerns\HasOrganization;

use App\Casts\VectorCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class KnowledgeIndex extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.knowledge_index';
    protected $primaryKey = 'index_id';
    protected $fillable = [
        'org_id',
        'source_type',
        'source_id',
        'title',
        'content',
        'content_summary',
        'embedding',
        'metadata',
        'tags',
        'category',
        'language',
        'indexed_at',
        'last_accessed',
        'access_count',
        'relevance_score',
        'is_verified',
        'verified_by',
        'verified_at',
        'provider',
    ];

    protected $casts = [
        'index_id' => 'string',
        'knowledge_id' => 'string',
        'org_id' => 'string',
        'source_id' => 'string',
        'verified_by' => 'string',
        'embedding' => VectorCast::class,
        'metadata' => 'array',
        'tags' => 'array',
        'indexed_at' => 'datetime',
        'last_accessed' => 'datetime',
        'access_count' => 'integer',
        'relevance_score' => 'float',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    

    /**
     * Get the verifier
     */
    public function verifier()
    {
        return $this->belongsTo(\App\Models\User::class, 'verified_by', 'user_id');

    /**
     * Increment access count
     */
    public function recordAccess(): void
    {
        $this->increment('access_count');
        $this->update(['last_accessed' => now()]);

    /**
     * Scope verified knowledge only
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);

    /**
     * Scope by source type
     */
    public function scopeBySourceType($query, string $sourceType)
    {
        return $query->where('source_type', $sourceType);

    /**
     * Scope high relevance
     */
    public function scopeHighRelevance($query, float $threshold = 0.7)
    {
        return $query->where('relevance_score', '>=', $threshold);

    /**
     * Perform semantic search using vector similarity
     */
    public static function semanticSearch(array $queryEmbedding, int $limit = 10, ?string $orgId = null)
    {
        $vectorStr = '[' . implode(',', $queryEmbedding) . ']';

        $query = self::query()
            ->selectRaw('*, embedding <=> ?::vector as distance', [$vectorStr])
            ->orderBy('distance');

        if ($orgId) {
            $query->where('org_id', $orgId);

        return $query->limit($limit)->get();
}
