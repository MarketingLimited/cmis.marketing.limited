<?php

namespace App\Models\Creative;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.content_plans';
    protected $primaryKey = 'plan_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'plan_id',
        'org_id',
        'campaign_id',
        'name',
        'timeframe_daterange',
        'strategy',
        'brief_id',
        'creative_context_id',
        'provider',
    ];

    protected $casts = ['plan_id' => 'string',
        'org_id' => 'string',
        'campaign_id' => 'string',
        'created_by' => 'string',
        'start_date' => 'date',
        'end_date' => 'date',
        'channels' => 'array',
        'themes' => 'array',
        'objectives' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'strategy' => 'array',
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
     * Get the content items
     */
    public function items()
    {
        return $this->hasMany(ContentItem::class, 'plan_id', 'plan_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');
    }

    /**
     * Scope active plans
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
}
