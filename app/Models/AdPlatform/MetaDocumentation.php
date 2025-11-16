<?php

namespace App\Models\AdPlatform;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class MetaDocumentation extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $connection = 'pgsql';

    protected $table = 'cmis.meta_documentation';

    protected $primaryKey = 'doc_id';

    public $incrementing = false;

    protected $keyType = 'string';

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
    public function scopeByKey($query, string $key)
    {
        return $query->where('meta_key', $key);
    }

    /**
     * Scope to find by updated by
     */
    public function scopeUpdatedBy($query, string $updatedBy)
    {
        return $query->where('updated_by', $updatedBy);
    }
}
