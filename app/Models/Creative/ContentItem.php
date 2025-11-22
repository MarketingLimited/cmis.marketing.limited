<?php

namespace App\Models\Creative;

use App\Models\BaseModel;
use App\Models\Channel;
use App\Models\Concerns\HasOrganization;
use App\Models\CreativeAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentItem extends BaseModel
{
    use HasFactory, SoftDeletes;
    use HasOrganization;

    protected $table = 'cmis.content_items';
    protected $primaryKey = 'item_id';

    protected $fillable = [
        'item_id',
        'plan_id',
        'channel_id',
        'format_id',
        'scheduled_at',
        'title',
        'brief',
        'asset_id',
        'status',
        'context_id',
        'example_id',
        'creative_context_id',
        'provider',
        'org_id',
        'deleted_by',
        'metadata',
        'tags',
        'item_type',
        'scheduled_for',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'item_id' => 'string',
        'org_id' => 'string',
        'context_id' => 'string',
        'plan_id' => 'string',
        'asset_id' => 'string',
        'example_id' => 'string',
        'created_by' => 'string',
        'channel_id' => 'integer',
        'format_id' => 'integer',
        'metadata' => 'array',
        'tags' => 'array',
        'brief' => 'array',
        'scheduled_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the content plan this item belongs to
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(ContentPlan::class, 'plan_id', 'plan_id');
    }

    /**
     * Get the creative asset
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(CreativeAsset::class, 'asset_id', 'asset_id');
    }

    /**
     * Get the channel
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id', 'channel_id');
    }

    /**
     * Get the user who created this item
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Scope to filter scheduled items
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
            ->whereNotNull('scheduled_for');
    }

    /**
     * Scope to filter published items
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at');
    }

    /**
     * Scope to filter draft items
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to filter by item type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('item_type', $type);
    }

    /**
     * Scope to filter by content plan
     */
    public function scopeForPlan($query, string $planId)
    {
        return $query->where('plan_id', $planId);
    }

    /**
     * Check if the item is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_for !== null;
    }

    /**
     * Check if the item is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    /**
     * Check if the item is overdue
     */
    public function isOverdue(): bool
    {
        if (!$this->isScheduled()) {
            return false;
        }

        return $this->scheduled_for->isPast();
    }
}
