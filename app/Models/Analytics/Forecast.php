<?php

namespace App\Models\Analytics;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Forecast extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.forecasts';
    protected $primaryKey = 'forecast_id';
    protected $fillable = [
        'org_id', 'entity_type', 'entity_id', 'metric', 'forecast_type',
        'forecast_date', 'predicted_value', 'confidence_lower', 'confidence_upper',
        'confidence_level', 'actual_value', 'error', 'model_params', 'generated_at'
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'predicted_value' => 'decimal:2',
        'confidence_lower' => 'decimal:2',
        'confidence_upper' => 'decimal:2',
        'confidence_level' => 'decimal:2',
        'actual_value' => 'decimal:2',
        'error' => 'decimal:2',
        'model_params' => 'array',
        'generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    

    /**
     * Update with actual value and calculate error
     */
    public function updateActual(float $actualValue): void
    {
        $error = abs($actualValue - $this->predicted_value);
        $this->update([
            'actual_value' => $actualValue,
            'error' => $error
        ]);

    /**
     * Check if forecast is accurate (within confidence interval)
     */
    public function isAccurate(): bool
    {
        if (!$this->actual_value || !$this->confidence_lower || !$this->confidence_upper) {
            return false;

        return $this->actual_value >= $this->confidence_lower
            && $this->actual_value <= $this->confidence_upper;

    /**
     * Get accuracy percentage
     */
    public function getAccuracyPercentage(): ?float
    {
        if (!$this->actual_value || $this->predicted_value == 0) {
            return null;

        $accuracy = 100 - (abs($this->actual_value - $this->predicted_value) / $this->predicted_value * 100);
        return max(0, $accuracy);

    /**
     * Scope: Future forecasts
     */
    public function scopeFuture($query)
    {
        return $query->where('forecast_date', '>', now());

    /**
     * Scope: Past forecasts
     */
    public function scopePast($query)
    {
        return $query->where('forecast_date', '<=', now());

    /**
     * Scope: By entity
     */
    public function scopeForEntity($query, string $entityType, string $entityId)
    {
        return $query->where('entity_type', $entityType)
                     ->where('entity_id', $entityId);

    /**
     * Scope: By metric
     */
    public function scopeForMetric($query, string $metric)
    {
        return $query->where('metric', $metric);
}
