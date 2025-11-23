<?php

namespace App\Models\Context;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class FieldValue extends BaseModel
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.field_values';
    protected $primaryKey = 'value_id';
    protected $fillable = [
        'value_id',
        'field_id',
        'context_id',
        'value',
        'source',
        'provider_ref',
        'justification',
        'confidence',
        'provider',
    ];

    protected $casts = [
        'value_id' => 'string',
        'context_id' => 'string',
        'field_id' => 'string',
        'created_by' => 'string',
        'value' => 'array', // JSONB field
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the field definition
     */
    public function field()
    {
        return $this->belongsTo(FieldDefinition::class, 'field_id', 'field_id');

    }
    /**
     * Get the context (value context)
     */
    public function context()
    {
        return $this->belongsTo(ValueContext::class, 'context_id', 'context_id');

    }
    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');

    }
    /**
     * Scope by context
     */
    public function scopeForContext($query, string $contextId)
    {
        return $query->where('context_id', $contextId);

    }
    /**
     * Scope by field
     */
    public function scopeForField($query, string $fieldId)
    {
        return $query->where('field_id', $fieldId);

    }
    /**
     * Get the scalar value if it's a simple type
     */
    public function getScalarValueAttribute()
    {
        if (is_array($this->value) && count($this->value) === 1) {
            return reset($this->value);

        return $this->value;
}
}
}
