<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExportBundleItem extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.export_bundle_items';
    protected $primaryKey = 'bundle_id';

    public $timestamps = false;

    protected $fillable = [
        'bundle_id',
        'asset_id',
        'provider',
    ];

    protected $casts = [
        'bundle_id' => 'string',
        'asset_id' => 'string',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the bundle
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(ExportBundle::class, 'bundle_id', 'bundle_id');

    }
    /**
     * Scope to get items for a specific bundle
     */
    public function scopeForBundle($query, string $bundleId): Builder
    {
        return $query->where('bundle_id', $bundleId);
}
}
