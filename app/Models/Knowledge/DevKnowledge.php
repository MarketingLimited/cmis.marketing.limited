<?php

namespace App\Models\Knowledge;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class DevKnowledge extends BaseModel
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.dev_knowledge';
    protected $primaryKey = 'dev_id';
    protected $fillable = [
        'topic',
        'category',
        'content',
        'code_examples',
        'references',
        'difficulty_level',
        'tags',
        'language',
        'framework',
        'version',
        'is_deprecated',
        'deprecated_reason',
        'replacement_topic',
        'provider',
    ];

    protected $casts = [
        'dev_id' => 'string',
        'code_examples' => 'array',
        'references' => 'array',
        'tags' => 'array',
        'is_deprecated' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get related knowledge index entries
     */
    public function knowledgeEntries()
    {
        return KnowledgeIndex::where('source_type', 'dev_knowledge')
            ->where('source_id', $this->dev_id);

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);

    /**
     * Scope by language
     */
    public function scopeByLanguage($query, string $language)
    {
        return $query->where('language', $language);

    /**
     * Scope by framework
     */
    public function scopeByFramework($query, string $framework)
    {
        return $query->where('framework', $framework);

    /**
     * Scope active (non-deprecated)
     */
    public function scopeActive($query)
    {
        return $query->where('is_deprecated', false);

    /**
     * Scope by difficulty level
     */
    public function scopeByDifficulty($query, string $level)
    {
        return $query->where('difficulty_level', $level);
}
