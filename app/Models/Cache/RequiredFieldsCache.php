<?php

namespace App\Models\Cache;

use App\Models\BaseModel;

class RequiredFieldsCache extends BaseModel
{
    
    protected $table = 'cmis.required_fields_cache';
    protected $primaryKey = 'module_scope';
    public $timestamps = false;

    protected $fillable = [
        'module_scope',
        'required_fields',
        'last_updated',
        'provider',
    ];

    protected $casts = [
        'module_id' => 'string',
        'is_required' => 'boolean',
        'validation_rules' => 'array',
        'cached_at' => 'datetime',
    ];

    /**
     * Scope by module
     */
    public function scopeForModule($query, string $moduleId)
    {
        return $query->where('module_id', $moduleId);

    /**
     * Scope required fields only
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
}
