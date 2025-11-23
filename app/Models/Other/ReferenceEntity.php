<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class ReferenceEntity extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'cmis.reference_entities';

    protected $primaryKey = 'ref_id';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'ref_id',
        'category',
        'code',
        'label',
        'description',
        'metadata',
        'provider',
    ];

    protected $casts = [
        'ref_id' => 'string',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope to filter by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);

    }
    /**
     * Scope to find by code
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
}
}
