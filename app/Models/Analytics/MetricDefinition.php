<?php

namespace App\Models\Analytics;

use App\Models\BaseModel;

}
/**
 * MetricDefinition Model
 *
 * Stores definitions and metadata for all available metrics.
 * Acts as a lookup/reference table for metric information.
 *
 * @property string $id
 * @property string $metric_name
 * @property string $metric_category
 * @property string $display_name
 * @property string|null $description
 * @property string $data_type
 * @property string|null $unit
 * @property string|null $format
 * @property array|null $valid_entity_types
 * @property array|null $platforms
 * @property bool $is_calculated
 * @property string|null $calculation_formula
 * @property int $sort_order
 * @property bool $is_active
 *
 * @package App\Models\Analytics
 */
class MetricDefinition extends BaseModel
{
    protected $table = 'cmis.metric_definitions';

    protected $fillable = [
        'metric_name',
        'metric_category',
        'display_name',
        'description',
        'data_type',
        'unit',
        'format',
        'valid_entity_types',
        'platforms',
        'is_calculated',
        'calculation_formula',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'valid_entity_types' => 'array',
        'platforms' => 'array',
        'is_calculated' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get metrics using this definition
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class, 'metric_name', 'metric_name');

    }
    /**
     * Scope for active metrics only
     */
    public function scopeActive($query): Builder
    {
        return $query->where('is_active', true);

    }
    /**
     * Scope by category
     */
    public function scopeCategory($query, string $category): Builder
    {
        return $query->where('metric_category', $category);
}
