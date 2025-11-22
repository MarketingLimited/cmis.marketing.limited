<?php

namespace App\Models\Social;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Integration;
use App\Models\Campaign;
use App\Models\Analytics\Metric;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SocialPost Model
 * 
 * Unified social post model consolidating 5 previous tables.
 * Handles draft → scheduled → published workflow.
 */
class SocialPost extends BaseModel
{
    use HasOrganization, SoftDeletes;

    protected $table = 'cmis.social_posts';

    protected $fillable = [
        'org_id', 'integration_id', 'platform', 'account_id', 'account_username',
        'post_external_id', 'permalink', 'content', 'media', 'post_type',
        'targeting', 'options', 'status', 'scheduled_at', 'published_at',
        'failed_at', 'error_message', 'requires_approval', 'created_by',
        'approved_by', 'approved_at', 'approval_notes', 'campaign_id',
        'tags', 'impressions_cache', 'engagement_cache', 'metrics_updated_at',
        'metadata', 'retry_count',
    ];

    protected $casts = [
        'media' => 'array',
        'targeting' => 'array',
        'options' => 'array',
        'tags' => 'array',
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'failed_at' => 'datetime',
        'approved_at' => 'datetime',
        'metrics_updated_at' => 'datetime',
        'requires_approval' => 'boolean',
        'impressions_cache' => 'integer',
        'engagement_cache' => 'integer',
        'retry_count' => 'integer',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_PUBLISHING = 'publishing';
    const STATUS_PUBLISHED = 'published';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function integration() { return $this->belongsTo(Integration::class, 'integration_id'); }
    public function campaign() { return $this->belongsTo(Campaign::class); }
    public function metrics() { return $this->morphMany(Metric::class, 'entity'); }
    public function history() { return $this->hasMany(PostHistory::class, 'post_id'); }

    // Scopes
    public function scopePublished($q) { return $q->where('status', self::STATUS_PUBLISHED); }
    public function scopeScheduled($q) { return $q->where('status', self::STATUS_SCHEDULED); }
    public function scopeDraft($q) { return $q->where('status', self::STATUS_DRAFT); }
    public function scopePlatform($q, $platform) { return $q->where('platform', $platform); }
}
