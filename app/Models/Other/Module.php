<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.modules';

    protected $primaryKey = 'module_id';

    public $timestamps = false;

    protected $fillable = [
        'module_id',
        'code',
        'name',
        'version',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the anchors
     */
    public function anchors(): HasMany
    {
        return $this->hasMany(Anchor::class, 'module_id', 'module_id');

    }
    /**
     * Scope to find by code
     */
    public function scopeByCode($query, string $code): Builder
    {
        return $query->where('code', $code);
}
}
