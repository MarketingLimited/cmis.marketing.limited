<?php

namespace App\Models\Context;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class FieldAlias extends BaseModel
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.field_aliases';
    protected $primaryKey = 'field_id';
    public $timestamps = false;

    protected $fillable = [
        'alias_slug',
        'field_id',
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
