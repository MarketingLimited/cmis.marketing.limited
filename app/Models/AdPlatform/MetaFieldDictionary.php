<?php

namespace App\Models\AdPlatform;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetaFieldDictionary extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.meta_field_dictionary';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'field_name',
        'semantic_meaning',
        'usage_context',
        'unified_alias',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope to find by field name
     */
    public function scopeByFieldName($query, string $fieldName)
    {
        return $query->where('field_name', $fieldName);

    }
    /**
     * Scope to find by unified alias
     */
    public function scopeByUnifiedAlias($query, string $alias)
    {
        return $query->where('unified_alias', $alias);
}
}
