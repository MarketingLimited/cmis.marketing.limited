<?php

namespace App\Models\Creative;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class ContentItem extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
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
    ];

    protected $casts = ['item_id' => 'string',
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
        'brief' => 'array',
    ];

    

    /**
     * Get the content plan
     */
    public function plan()
    {
        return $this->belongsTo(ContentPlan::class, 'plan_id', 'plan_id');

    /**
     * Get the creative asset
     */
    public function asset()
    {
        return $this->belongsTo(\App\Models\CreativeAsset::class, 'asset_id', 'asset_id');

    /**
     * Get the channel
     */
    public function channel()
    {
        return $this->belongsTo(\App\Models\Channel::class, 'channel_id', 'channel_id');

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'user_id');

    /**
     * Scope scheduled items
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')->whereNotNull('scheduled_for');

    /**
     * Scope published items
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at');

    /**
     * Scope by item type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('item_type', $type);
}
