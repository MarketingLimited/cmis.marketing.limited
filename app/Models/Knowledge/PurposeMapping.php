<?php

namespace App\Models\Knowledge;

use App\Models\BaseModel;

class PurposeMapping extends BaseModel
{
    
    protected $table = 'cmis.purpose_mappings';
    protected $primaryKey = 'purpose_id';
    protected $fillable = [
        'purpose_code',
        'purpose_label',
        'description',
        'category',
        'use_cases',
        'recommended_channels',
        'content_guidelines',
        'success_metrics',
        'metadata',
        'is_active',
        'provider',
    ];

    protected $casts = [
        'purpose_id' => 'string',
        'use_cases' => 'array',
        'recommended_channels' => 'array',
        'content_guidelines' => 'array',
        'success_metrics' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope active purposes
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
     * Find by purpose code
     */
    public static function findByCode(string $code)
    {
        return self::where('purpose_code', $code)->first();
}
}
