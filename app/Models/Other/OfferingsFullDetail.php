<?php

namespace App\Models\Other;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class OfferingsFullDetail extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $connection = 'pgsql';

    protected $table = 'cmis.offerings_full_details';

    protected $primaryKey = 'detail_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'offering_id',
        'full_description',
        'pricing_notes',
        'target_segment',
    ];

    protected $casts = [
        'detail_id' => 'string',
        'offering_id' => 'string',
        'created_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope to get details for a specific offering
     */
    public function scopeForOffering($query, string $offeringId)
    {
        return $query->where('offering_id', $offeringId);
    }
}
