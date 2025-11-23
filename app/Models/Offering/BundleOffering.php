<?php

namespace App\Models\Offering;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class BundleOffering extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.bundle_offerings';
    protected $primaryKey = 'bundle_id';
    protected $fillable = [
        'bundle_id',
        'offering_id',
        'provider',
    ];

    protected $casts = [
        'bundle_id' => 'string',
        'org_id' => 'string',
        'included_offerings' => 'array',
        'bundle_price' => 'decimal:2',
        'individual_price_sum' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'float',
        'min_commitment_period' => 'integer',
        'terms_conditions' => 'array',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    

    /**
     * Get offerings in this bundle
     */
    public function offerings()
    {
        if (empty($this->included_offerings)) {
            return collect();
        }

        return \App\Models\Offering::whereIn('offering_id', $this->included_offerings)->get();
    }
    /**
     * Scope active bundles
     */
    public function scopeActive($query): Builder
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now());
            });
    }
    /**
     * Scope by bundle type
     */
    public function scopeByType($query, string $type): Builder
    {
        return $query->where('bundle_type', $type);

    }
    /**
     * Check if bundle is valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }

        if ($this->valid_to && $this->valid_to->isPast()) {
            return false;
        }

        return true;
    }
    /**
     * Calculate savings amount
     */
    public function getSavingsAmount(): float
    {
        return $this->individual_price_sum - $this->bundle_price;

    }
    /**
     * Calculate savings percentage
     */
    public function getSavingsPercentage(): float
    {
        if ($this->individual_price_sum == 0) {
            return 0.0;
        }

        return (($this->individual_price_sum - $this->bundle_price) / $this->individual_price_sum) * 100;
    }
    /**
     * Get number of offerings
     */
    public function getOfferingCount(): int
    {
        return count($this->included_offerings ?? []);
    }
}
