<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Migration extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

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
