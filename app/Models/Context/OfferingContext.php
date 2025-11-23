<?php

namespace App\Models\Context;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class OfferingContext extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.contexts_offering';
    protected $primaryKey = 'context_id';
    protected $fillable = [
        'context_id',
        'offering_details',
        'pricing_info',
        'features',
        'provider',
    ];

    protected $casts = ['context_id' => 'string',
        'org_id' => 'string',
        'offering_id' => 'string',
        'created_by' => 'string',
        'product_features' => 'array',
        'service_details' => 'array',
        'pricing_information' => 'array',
        'target_market' => 'array',
        'use_cases' => 'array',
        'technical_specifications' => 'array',
        'warranty_support' => 'array',
        'integrations' => 'array',
        'certifications' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'pricing_info' => 'array',
        'features' => 'array',
    ];

    

    /**
     * Get the offering
     */
    public function offering()
    {
        return $this->belongsTo(\App\Models\Offering::class, 'offering_id', 'offering_id');

    }
    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');

    }
    /**
     * Scope active contexts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('deleted_at');
}
}
