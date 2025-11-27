<?php

namespace App\Models\FeatureManagement;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureFlagVariant extends BaseModel
{
    use HasFactory, HasOrganization;

    protected $table = 'cmis_features.feature_flag_variants';
    protected $primaryKey = 'variant_id';

    protected $fillable = [
        'variant_id',
        'flag_id',
        'org_id',
        'key',
        'name',
        'description',
        'value',
        'weight',
        'is_control',
        'is_active',
        'assignment_count',
        'conversion_count',
        'conversion_rate',
        'metadata',
    ];

    protected $casts = [
        'value' => 'array',
        'weight' => 'integer',
        'is_control' => 'boolean',
        'is_active' => 'boolean',
        'assignment_count' => 'integer',
        'conversion_count' => 'integer',
        'conversion_rate' => 'decimal:4',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function flag(): BelongsTo
    {
        return $this->belongsTo(FeatureFlag::class, 'flag_id', 'flag_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeControl($query)
    {
        return $query->where('is_control', true);
    }

    public function scopeNonControl($query)
    {
        return $query->where('is_control', false);
    }

    public function scopeOrderedByWeight($query)
    {
        return $query->orderBy('weight', 'desc');
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function isControl(): bool
    {
        return $this->is_control === true;
    }

    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    public function recordAssignment(): bool
    {
        return $this->increment('assignment_count');
    }

    public function recordConversion(): bool
    {
        $this->increment('conversion_count');

        // Update conversion rate
        $conversionRate = $this->assignment_count > 0
            ? ($this->conversion_count / $this->assignment_count)
            : 0;

        return $this->update(['conversion_rate' => $conversionRate]);
    }

    public function updateWeight(int $weight): bool
    {
        return $this->update(['weight' => max(0, min(100, $weight))]);
    }

    public function getWeightPercentage(): float
    {
        $totalWeight = static::where('flag_id', $this->flag_id)
            ->where('is_active', true)
            ->sum('weight');

        if ($totalWeight === 0) {
            return 0;
        }

        return round(($this->weight / $totalWeight) * 100, 2);
    }

    public function getPerformanceScore(): float
    {
        if ($this->assignment_count === 0) {
            return 0;
        }

        // Simple performance score based on conversion rate and sample size
        $sampleFactor = min(1, $this->assignment_count / 100);
        return $this->conversion_rate * $sampleFactor * 100;
    }

    public function getStatusColor(): string
    {
        if (!$this->is_active) {
            return 'gray';
        }

        if ($this->is_control) {
            return 'blue';
        }

        if ($this->conversion_rate > 0.1) {
            return 'green';
        }

        if ($this->conversion_rate > 0.05) {
            return 'yellow';
        }

        return 'orange';
    }

    // Static Methods
    public static function createControl(string $flagId, string $orgId, array $attributes = []): self
    {
        return static::create(array_merge([
            'flag_id' => $flagId,
            'org_id' => $orgId,
            'key' => 'control',
            'name' => 'Control',
            'description' => 'Control variant',
            'value' => ['enabled' => false],
            'weight' => 50,
            'is_control' => true,
            'is_active' => true,
        ], $attributes));
    }

    public static function selectVariant(string $flagId, string $identifier): ?self
    {
        $variants = static::where('flag_id', $flagId)
            ->where('is_active', true)
            ->get();

        if ($variants->isEmpty()) {
            return null;
        }

        $totalWeight = $variants->sum('weight');

        if ($totalWeight === 0) {
            return $variants->random();
        }

        // Consistent hashing for stable variant assignment
        $hash = crc32($flagId . $identifier);
        $bucket = $hash % $totalWeight;

        $currentWeight = 0;
        foreach ($variants as $variant) {
            $currentWeight += $variant->weight;

            if ($bucket < $currentWeight) {
                return $variant;
            }
        }

        return $variants->last();
    }

    public static function normalizeWeights(string $flagId): bool
    {
        $variants = static::where('flag_id', $flagId)
            ->where('is_active', true)
            ->get();

        if ($variants->isEmpty()) {
            return true;
        }

        $totalWeight = $variants->sum('weight');

        if ($totalWeight === 0) {
            // Distribute equally
            $equalWeight = floor(100 / $variants->count());

            foreach ($variants as $variant) {
                $variant->update(['weight' => $equalWeight]);
            }

            return true;
        }

        // Normalize to 100
        foreach ($variants as $variant) {
            $normalizedWeight = floor(($variant->weight / $totalWeight) * 100);
            $variant->update(['weight' => $normalizedWeight]);
        }

        return true;
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'flag_id' => 'required|uuid|exists:cmis_features.feature_flags,flag_id',
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'key' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'value' => 'required|array',
            'weight' => 'required|integer|min:0|max:100',
            'is_control' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'value' => 'sometimes|array',
            'weight' => 'sometimes|integer|min:0|max:100',
            'is_active' => 'sometimes|boolean',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'flag_id.required' => 'Feature flag is required',
            'org_id.required' => 'Organization is required',
            'key.required' => 'Variant key is required',
            'name.required' => 'Variant name is required',
            'value.required' => 'Variant value is required',
            'weight.required' => 'Weight is required',
            'weight.min' => 'Weight must be between 0 and 100',
            'weight.max' => 'Weight must be between 0 and 100',
        ];
    }
}
