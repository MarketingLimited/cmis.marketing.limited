<?php

namespace App\Models\Creative;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cmis.content_items';
    protected $primaryKey = 'item_id';
    protected $connection = 'pgsql';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'org_id',
        'context_id',
        'plan_id',
        'asset_id',
        'item_type',
        'title',
        'body',
        'channel_id',
        'format_id',
        'scheduled_for',
        'published_at',
        'status',
        'metadata',
        'tags',
        'example_id',
        'created_by',
        'provider',
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
        'scheduled_for' => 'datetime',
        'published_at' => 'datetime',
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
     * Get the content plan
     */
    public function plan()
    {
        return $this->belongsTo(ContentPlan::class, 'plan_id', 'plan_id');
    }

    /**
     * Get the creative asset
     */
    public function asset()
    {
        return $this->belongsTo(\App\Models\CreativeAsset::class, 'asset_id', 'asset_id');
    }

    /**
     * Get the channel
     */
    public function channel()
    {
        return $this->belongsTo(\App\Models\Channel::class, 'channel_id', 'channel_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');
    }

    /**
     * Scope scheduled items
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')->whereNotNull('scheduled_for');
    }

    /**
     * Scope published items
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    /**
     * Scope by item type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('item_type', $type);
    }
}
