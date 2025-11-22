<?php

namespace App\Models\Knowledge;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class EmbeddingApiConfig extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.embedding_api_config';
    protected $primaryKey = 'config_id';
    protected $fillable = [
        'org_id',
        'provider_name',
        'api_key_encrypted',
        'model_name',
        'embedding_dim',
        'max_tokens',
        'rate_limit',
        'endpoint_url',
        'additional_config',
        'is_active',
        'is_default',
        'last_used',
        'total_requests',
        'failed_requests',
        'provider',
    ];

    protected $casts = [
        'config_id' => 'string',
        'org_id' => 'string',
        'embedding_dim' => 'integer',
        'max_tokens' => 'integer',
        'rate_limit' => 'integer',
        'additional_config' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'last_used' => 'datetime',
        'total_requests' => 'integer',
        'failed_requests' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key_encrypted',
    ];

    

    /**
     * Get API logs
     */
    public function logs()
    {
        return $this->hasMany(EmbeddingApiLog::class, 'config_id', 'config_id');

    /**
     * Scope active configs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);

    /**
     * Scope default config
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);

    /**
     * Scope by provider
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider_name', $provider);

    /**
     * Record usage
     */
    public function recordUsage(bool $success = true): void
    {
        $this->increment('total_requests');

        if (!$success) {
            $this->increment('failed_requests');

        $this->update(['last_used' => now()]);

    /**
     * Get success rate
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_requests === 0) {
            return 0.0;

        $successfulRequests = $this->total_requests - $this->failed_requests;
        return ($successfulRequests / $this->total_requests) * 100;
}
