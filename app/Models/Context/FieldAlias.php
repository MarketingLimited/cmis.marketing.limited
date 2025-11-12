<?php

namespace App\Models\Context;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldAlias extends Model
{
    use HasFactory;

    protected $table = 'cmis.field_aliases';
    protected $primaryKey = 'alias_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'field_id',
        'alias_name',
        'alias_type',
        'is_active',
        'provider',
    ];

    protected $casts = [
        'alias_id' => 'string',
        'field_id' => 'string',
        'is_active' => 'boolean',
    ];

    /**
     * Get the field definition
     */
    public function field()
    {
        return $this->belongsTo(FieldDefinition::class, 'field_id', 'field_id');
    }

    /**
     * Scope active aliases
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by alias type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('alias_type', $type);
    }
}
