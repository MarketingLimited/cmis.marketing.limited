<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pgsql';

    protected $table = 'cmis.campaigns';

    protected $primaryKey = 'campaign_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'org_id',
        'name',
        'objective',
        'status',
        'start_date',
        'end_date',
        'budget',
        'currency',
        'context_id',
        'creative_id',
        'value_id',
        'created_by',
        'deleted_by',
        'provider',
    ];

    protected $casts = [
        'campaign_id' => 'string',
        'org_id' => 'string',
        'context_id' => 'string',
        'creative_id' => 'string',
        'value_id' => 'string',
        'created_by' => 'string',
        'deleted_by' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function org(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Core\Org::class, 'org_id', 'org_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function offerings(): BelongsToMany
    {
        return $this->belongsToMany(
            Offering::class,
            'cmis.campaign_offerings',
            'campaign_id',
            'offering_id'
        );
    }

    public function performanceMetrics(): HasMany
    {
        return $this->hasMany(CampaignPerformanceMetric::class, 'campaign_id', 'campaign_id');
    }
}
