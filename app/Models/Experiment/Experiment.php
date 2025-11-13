<?php

namespace App\Models\Experiment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Experiment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.experiments';
    protected $primaryKey = 'exp_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'exp_id',
        'org_id',
        'channel_id',
        'framework',
        'hypothesis',
        'status',
        'campaign_id',
        'provider',
    ];

    protected $casts = [
        'exp_id' => 'string',
        'org_id' => 'string',
        'campaign_id' => 'string',
        'control_variant' => 'string',
        'winning_variant' => 'string',
        'created_by' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'confidence_level' => 'float',
        'sample_size' => 'integer',
        'metadata' => 'array',
        'results' => 'array',
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
     * Get the campaign
     */
    public function campaign()
    {
        return $this->belongsTo(\App\Models\Campaign::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Get the experiment variants
     */
    public function variants()
    {
        return $this->hasMany(ExperimentVariant::class, 'exp_id', 'exp_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');
    }

    /**
     * Scope active experiments
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope completed experiments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
