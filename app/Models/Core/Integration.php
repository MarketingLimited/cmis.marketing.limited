<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Integration extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis_integrations.integrations';

    protected $primaryKey = 'integration_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'org_id',
        'platform',
        'account_id',
        'username',
        'access_token',
        'is_active',
        'business_id',
        'created_by',
        'updated_by',
        'provider',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected $casts = [
        'integration_id' => 'string',
        'org_id' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the organization that owns this integration.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the user who created this integration.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');
    }

    /**
     * Get ad campaigns associated with this integration
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function adCampaigns()
    {
        return $this->hasMany(\App\Models\AdPlatform\AdCampaign::class, 'integration_id', 'integration_id');
    }

    /**
     * Get ad accounts associated with this integration
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function adAccounts()
    {
        return $this->hasMany(\App\Models\AdPlatform\AdAccount::class, 'integration_id', 'integration_id');
    }

    /**
     * Get ad sets associated with this integration
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function adSets()
    {
        return $this->hasMany(\App\Models\AdPlatform\AdSet::class, 'integration_id', 'integration_id');
    }

    /**
     * Get ad entities associated with this integration
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function adEntities()
    {
        return $this->hasMany(\App\Models\AdPlatform\AdEntity::class, 'integration_id', 'integration_id');
    }
}
