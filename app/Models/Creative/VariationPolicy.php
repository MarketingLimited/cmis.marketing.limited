<?php

namespace App\Models\Creative;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariationPolicy extends Model
{
    use HasFactory;

    protected $table = 'cmis.variation_policies';
    protected $primaryKey = 'policy_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id',
        'policy_name',
        'description',
        'variation_rules',
        'test_percentage',
        'max_variants',
        'optimization_goal',
        'auto_promote_winner',
        'confidence_threshold',
        'metadata',
        'is_active',
        'created_by',
        'provider',
    ];

    protected $casts = [
        'policy_id' => 'string',
        'org_id' => 'string',
        'created_by' => 'string',
        'variation_rules' => 'array',
        'test_percentage' => 'float',
        'max_variants' => 'integer',
        'auto_promote_winner' => 'boolean',
        'confidence_threshold' => 'float',
        'metadata' => 'array',
        'is_active' => 'boolean',
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
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');
    }

    /**
     * Scope active policies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
