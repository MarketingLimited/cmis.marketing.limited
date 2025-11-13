<?php

namespace App\Models\AdPlatform;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdAudience extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.ad_audiences';
    protected $primaryKey = 'id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'org_id',
        'integration_id',
        'entity_level',
        'entity_external_id',
        'audience_type',
        'platform',
        'demographics',
        'interests',
        'behaviors',
        'location',
        'keywords',
        'custom_audience',
        'lookalike_audience',
        'advantage_plus_settings',
        'provider',
    ];

    protected $casts = [
        'id' => 'string',
        'org_id' => 'string',
        'integration_id' => 'string',
        'demographics' => 'array',
        'interests' => 'array',
        'behaviors' => 'array',
        'location' => 'array',
        'keywords' => 'array',
        'custom_audience' => 'array',
        'lookalike_audience' => 'array',
        'advantage_plus_settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the organization
     */
    public function org()
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the integration
     */
    public function integration()
    {
        return $this->belongsTo(\App\Models\Core\Integration::class, 'integration_id', 'integration_id');
    }

    /**
     * Scope by platform
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope by audience type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('audience_type', $type);
    }

    /**
     * Scope custom audiences
     */
    public function scopeCustom($query)
    {
        return $query->where('audience_type', 'custom');
    }

    /**
     * Scope lookalike audiences
     */
    public function scopeLookalike($query)
    {
        return $query->where('audience_type', 'lookalike');
    }

    /**
     * Scope by entity level
     */
    public function scopeByEntityLevel($query, string $level)
    {
        return $query->where('entity_level', $level);
    }
}
