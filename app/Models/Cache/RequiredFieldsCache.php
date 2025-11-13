<?php

namespace App\Models\Cache;

use Illuminate\Database\Eloquent\Model;

class RequiredFieldsCache extends Model
{
    protected $table = 'cmis.required_fields_cache';
    protected $connection = 'pgsql';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'module_id',
        'field_name',
        'is_required',
        'field_type',
        'validation_rules',
        'cached_at',
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
    }

    /**
     * Scope required fields only
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}
