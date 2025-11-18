<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledSocialPost extends Model
{
    use HasUuids;

    protected $connection = 'pgsql';

    protected $table = 'cmis.scheduled_social_posts';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'org_id',
        'user_id',
        'campaign_id',
        'platforms',
        'content',
        'media',
        'scheduled_at',
        'status',
        'published_at',
        'published_ids',
        'error_message',
        // NEW: Social Publishing Fix
        'integration_ids',
        'media_urls',
        'publish_results',
        'post_id',
        'created_by',
    ];

    protected $casts = [
        'id' => 'string',
        'org_id' => 'string',
        'user_id' => 'string',
        'campaign_id' => 'string',
        'platforms' => 'array',
        'media' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'published_ids' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // NEW: Social Publishing Fix
        'integration_ids' => 'array',
        'media_urls' => 'array',
        'publish_results' => 'array',
    ];

    /**
     * Valid status values
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_PUBLISHING = 'publishing';
    const STATUS_PUBLISHED = 'published';
    const STATUS_PARTIALLY_PUBLISHED = 'partially_published'; // NEW: Social Publishing Fix
    const STATUS_FAILED = 'failed';

    /**
     * Get the organization that owns the scheduled post
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Core\Org::class, 'org_id', 'org_id');
    }

    /**
     * Get the user who created the scheduled post
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the campaign associated with the scheduled post
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }

    /**
     * Scope to get only scheduled posts
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope to get only draft posts
     */
    public function scopeDrafts($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope to get only published posts
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope to filter by organization
     */
    public function scopeForOrg($query, string $orgId)
    {
        return $query->where('org_id', $orgId);
    }

    /**
     * Check if post is ready to publish
     */
    public function isReadyToPublish(): bool
    {
        return $this->status === self::STATUS_SCHEDULED
            && $this->scheduled_at
            && $this->scheduled_at->isPast();
    }

    /**
     * Mark post as publishing
     */
    public function markAsPublishing(): void
    {
        $this->update(['status' => self::STATUS_PUBLISHING]);
    }

    /**
     * Mark post as published
     */
    public function markAsPublished(array $publishedIds = []): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => now(),
            'published_ids' => $publishedIds,
        ]);
    }

    /**
     * Mark post as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Get integrations for this post (NEW: Social Publishing Fix)
     */
    public function integrations()
    {
        if (empty($this->integration_ids)) {
            return collect([]);
        }

        return \Illuminate\Support\Facades\DB::table('cmis_integrations.integrations')
            ->whereIn('integration_id', $this->integration_ids)
            ->where('org_id', $this->org_id)
            ->where('is_active', true)
            ->get();
    }
}
