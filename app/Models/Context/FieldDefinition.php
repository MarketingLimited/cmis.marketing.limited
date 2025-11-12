<?php

namespace App\Models\Context;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldDefinition extends Model
{
    use HasFactory;

    protected $table = 'cmis.field_definitions';
    protected $primaryKey = 'field_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'module_id',
        'field_name',
        'field_type',
        'is_required',
        'default_value',
        'validation_rules',
        'description',
        'help_text',
        'placeholder',
        'options',
        'display_order',
        'is_active',
        'guidance_anchor',
        'metadata',
        'provider',
    ];

    protected $casts = [
        'field_id' => 'string',
        'module_id' => 'string',
        'guidance_anchor' => 'string',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'validation_rules' => 'array',
        'options' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the field values
     */
    public function values()
    {
        return $this->hasMany(FieldValue::class, 'field_id', 'field_id');
    }

    /**
     * Get field aliases
     */
    public function aliases()
    {
        return $this->hasMany(FieldAlias::class, 'field_id', 'field_id');
    }

    /**
     * Scope required fields
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope active fields
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by module
     */
    public function scopeForModule($query, string $moduleId)
    {
        return $query->where('module_id', $moduleId);
    }

    /**
     * Get formatted validation rules
     */
    public function getFormattedValidationRulesAttribute(): string
    {
        if (!$this->validation_rules) {
            return '';
        }

        return implode('|', $this->validation_rules);
    }
}
