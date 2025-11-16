<?php

namespace App\Models\Context;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Campaign;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class CampaignContextLink extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.campaign_context_links';
    protected $primaryKey = 'id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'campaign_id',
        'context_id',
        'context_type',
        'link_type',
        'link_strength',
        'link_purpose',
        'link_notes',
        'effective_from',
        'effective_to',
        'is_active',
        'created_by',
        'updated_by',
        'metadata',
        'provider',
    ];

    protected $casts = [
        'link_id' => 'string',
        'campaign_id' => 'string',
        'context_id' => 'string',
        'link_strength' => 'float',
        'is_active' => 'boolean',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'metadata' => 'array',
        'created_by' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the campaign
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Get the context (polymorphic-like)
     */
    public function context()
    {
        // Return appropriate context based on context_type
        switch ($this->context_type) {
            case 'value':
                return $this->belongsTo(ValueContext::class, 'context_id', 'context_id');
            case 'creative':
                return $this->belongsTo(CreativeContext::class, 'context_id', 'context_id');
            case 'offering':
                return $this->belongsTo(OfferingContext::class, 'context_id', 'context_id');
            default:
                return $this->belongsTo(ContextBase::class, 'context_id', 'id');
        }
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');
    }

    /**
     * Scope active links
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', now());
            });
    }

    /**
     * Scope by link type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('link_type', $type);
    }

    /**
     * Scope primary links
     */
    public function scopePrimary($query)
    {
        return $query->where('link_type', 'primary');
    }
}
