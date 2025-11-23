<?php

namespace App\Models\Creative;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class VariationPolicy extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.variation_policies';
    protected $primaryKey = 'policy_id';
    protected $fillable = [
        'policy_id',
        'org_id',
        'max_variations',
        'dco_enabled',
        'naming_ref',
        'provider',
    ];

    protected $casts = ['policy_id' => 'string',
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
        'dco_enabled' => 'boolean',
    ];

    

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
