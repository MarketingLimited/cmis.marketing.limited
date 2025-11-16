<?php

namespace App\Models\Knowledge;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class CognitiveManifest extends Model
{
    use HasUuids;
    protected $table = 'cmis.cognitive_manifest';
    protected $primaryKey = 'manifest_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id',
        'manifest_version',
        'system_capabilities',
        'enabled_features',
        'model_configurations',
        'knowledge_sources',
        'processing_rules',
        'quality_thresholds',
        'personalization_settings',
        'learning_preferences',
        'output_preferences',
        'constraint_rules',
        'metadata',
        'is_active',
        'activated_at',
        'provider',
    ];

    protected $casts = [
        'manifest_id' => 'string',
        'org_id' => 'string',
        'manifest_version' => 'integer',
        'system_capabilities' => 'array',
        'enabled_features' => 'array',
        'model_configurations' => 'array',
        'knowledge_sources' => 'array',
        'processing_rules' => 'array',
        'quality_thresholds' => 'array',
        'personalization_settings' => 'array',
        'learning_preferences' => 'array',
        'output_preferences' => 'array',
        'constraint_rules' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

    /**
     * Scope active manifests
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope latest version
     */
    public function scopeLatestVersion($query)
    {
        return $query->orderBy('manifest_version', 'desc');
    }

    /**
     * Check if feature is enabled
     */
    public function isFeatureEnabled(string $feature): bool
    {
        return in_array($feature, $this->enabled_features ?? []);
    }

    /**
     * Get quality threshold for metric
     */
    public function getQualityThreshold(string $metric): ?float
    {
        return $this->quality_thresholds[$metric] ?? null;
    }

    /**
     * Get model configuration
     */
    public function getModelConfig(string $modelType): ?array
    {
        return $this->model_configurations[$modelType] ?? null;
    }
}
