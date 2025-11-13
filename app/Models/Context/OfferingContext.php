<?php

namespace App\Models\Context;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfferingContext extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.contexts_offering';
    protected $primaryKey = 'context_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id',
        'offering_id',
        'name',
        'description',
        'product_features',
        'service_details',
        'pricing_information',
        'delivery_method',
        'target_market',
        'use_cases',
        'technical_specifications',
        'warranty_support',
        'integrations',
        'certifications',
        'metadata',
        'tags',
        'is_active',
        'created_by',
        'provider',
    ];

    protected $casts = [
        'context_id' => 'string',
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
    ];

    /**
     * Get the base context
     */
    public function contextBase()
    {
        return $this->belongsTo(ContextBase::class, 'context_id', 'id');
    }

    /**
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

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
