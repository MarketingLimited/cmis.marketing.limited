<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Migration extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.migrations';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'migration',
        'batch',
    ];

    protected $casts = [
        'batch' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope to get migrations for a specific batch
     */
    public function scopeForBatch($query, int $batch)
    {
        return $query->where('batch', $batch);
}
}
