<?php

namespace App\Models\Knowledge;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class IntentMapping extends Model
{
    use HasUuids;
    protected $table = 'cmis.intent_mappings';
    protected $primaryKey = 'intent_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'intent_code',
        'intent_label',
        'description',
        'category',
        'example_phrases',
        'confidence_threshold',
        'related_intents',
        'metadata',
        'is_active',
        'provider',
    ];

    protected $casts = [
        'intent_id' => 'string',
        'example_phrases' => 'array',
        'confidence_threshold' => 'float',
        'related_intents' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope active intents
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Find by intent code
     */
    public static function findByCode(string $code)
    {
        return self::where('intent_code', $code)->first();
    }
}
