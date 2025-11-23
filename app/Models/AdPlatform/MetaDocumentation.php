<?php

namespace App\Models\AdPlatform;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class MetaDocumentation extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'cmis.meta_documentation';

    protected $primaryKey = 'doc_id';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'meta_key',
        'meta_value',
        'updated_by',
    ];

    protected $casts = [
        'doc_id' => 'string',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope to find by meta key
     */
    public function scopeByKey($query, string $key): Builder
    {
        return $query->where('meta_key', $key);

    }
    /**
     * Scope to find by updated by
     */
    public function scopeUpdatedBy($query, string $updatedBy): Builder
    {
        return $query->where('updated_by', $updatedBy);
}
}
