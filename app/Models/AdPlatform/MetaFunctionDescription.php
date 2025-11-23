<?php

namespace App\Models\AdPlatform;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetaFunctionDescription extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.meta_function_descriptions';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'routine_schema',
        'routine_name',
        'description',
        'cognitive_category',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope to find by routine name
     */
    public function scopeByRoutineName($query, string $routineName): Builder
    {
        return $query->where('routine_name', $routineName);

    }
    /**
     * Scope to find by schema
     */
    public function scopeBySchema($query, string $schema): Builder
    {
        return $query->where('routine_schema', $schema);

    }
    /**
     * Scope to filter by category
     */
    public function scopeByCategory($query, string $category): Builder
    {
        return $query->where('cognitive_category', $category);
}
}
