<?php

namespace App\Models\AdPlatform;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class AdAccount extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.ad_accounts';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'org_id',
        'integration_id',
        'account_external_id',
        'name',
        'currency',
        'timezone',
        'spend_cap',
        'status',
        'provider',
    ];

    protected $casts = [
        'id' => 'string',
        'org_id' => 'string',
        'integration_id' => 'string',
        'spend_cap' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the integration
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Core\Integration::class, 'integration_id', 'integration_id');
    }

    /**
     * Scope active accounts
     */
    public function scopeActive($query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope by provider
     */
    public function scopeByProvider($query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }
}
