<?php

namespace App\Models\Offering;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfferingFullDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.offering_full_details';
    protected $primaryKey = 'detail_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'offering_id',
        'detailed_description',
        'technical_specs',
        'features',
        'benefits',
        'use_cases',
        'target_industries',
        'target_personas',
        'pricing_tiers',
        'competitive_comparison',
        'implementation_guide',
        'support_resources',
        'faq',
        'testimonials',
        'case_studies',
        'media_assets',
        'metadata',
        'provider',
    ];

    protected $casts = [
        'detail_id' => 'string',
        'offering_id' => 'string',
        'technical_specs' => 'array',
        'features' => 'array',
        'benefits' => 'array',
        'use_cases' => 'array',
        'target_industries' => 'array',
        'target_personas' => 'array',
        'pricing_tiers' => 'array',
        'competitive_comparison' => 'array',
        'implementation_guide' => 'array',
        'support_resources' => 'array',
        'faq' => 'array',
        'testimonials' => 'array',
        'case_studies' => 'array',
        'media_assets' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the offering
     */
    public function offering()
    {
        return $this->belongsTo(\App\Models\Offering::class, 'offering_id', 'offering_id');
    }

    /**
     * Get feature by key
     */
    public function getFeature(string $featureKey)
    {
        foreach ($this->features ?? [] as $feature) {
            if (isset($feature['key']) && $feature['key'] === $featureKey) {
                return $feature;
            }
        }

        return null;
    }

    /**
     * Get pricing tier
     */
    public function getPricingTier(string $tierName)
    {
        foreach ($this->pricing_tiers ?? [] as $tier) {
            if (isset($tier['name']) && $tier['name'] === $tierName) {
                return $tier;
            }
        }

        return null;
    }

    /**
     * Get use case by category
     */
    public function getUseCasesByCategory(string $category): array
    {
        return array_filter($this->use_cases ?? [], function ($useCase) use ($category) {
            return isset($useCase['category']) && $useCase['category'] === $category;
        });
    }

    /**
     * Get testimonials by rating
     */
    public function getTestimonialsByRating(int $minRating = 4): array
    {
        return array_filter($this->testimonials ?? [], function ($testimonial) use ($minRating) {
            return isset($testimonial['rating']) && $testimonial['rating'] >= $minRating;
        });
    }
}
