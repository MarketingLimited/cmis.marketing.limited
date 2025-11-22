<?php

namespace App\Models\Creative;

use App\Models\BaseModel;
use App\Models\Campaign;
use App\Models\Concerns\HasOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ContentPlan Model
 *
 * Unified content plan model for managing campaign content strategies.
 * Consolidated from Content\ContentPlan and Creative\ContentPlan.
 */
class ContentPlan extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.content_plans';
    protected $primaryKey = 'plan_id';

    protected $fillable = [
        'plan_id',
        'org_id',
        'campaign_id',
        'name',
        'description',
        'content_type',
        'timeframe_daterange',
        'strategy',
        'key_messages',
        'brief_id',
        'creative_context_id',
        'status',
        'start_date',
        'end_date',
        'created_by',
        'provider',
    ];

    protected $casts = [
        'plan_id' => 'string',
        'org_id' => 'string',
        'campaign_id' => 'string',
        'strategy' => 'array',
        'key_messages' => 'array',
        'timeframe_daterange' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the campaign this content plan belongs to
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Get the content items for this plan
     */
    public function items(): HasMany
    {
        return $this->hasMany(ContentItem::class, 'plan_id', 'plan_id');
    }

    /**
     * Get the user who created this plan
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Scope to filter active content plans
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
     * Scope to filter by campaign
     */
    public function scopeForCampaign($query, string $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter draft plans
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to filter published plans
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Check if the content plan is active
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->start_date && $this->start_date->isFuture()) {
            return false;
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get the total number of content items
     */
    public function getItemsCountAttribute(): int
    {
        return $this->items()->count();
    }

    /**
     * Get the number of completed items
     */
    public function getCompletedItemsCountAttribute(): int
    {
        return $this->items()->where('status', 'completed')->count();
    }

    /**
     * Get the completion percentage
     */
    public function getCompletionPercentageAttribute(): float
    {
        $total = $this->items_count;

        if ($total === 0) {
            return 0.0;
        }

        return ($this->completed_items_count / $total) * 100;
    }
}
