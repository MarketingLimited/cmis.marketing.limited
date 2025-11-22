<?php

namespace App\Models\Social;

use App\Models\Concerns\HasOrganization;
use App\Models\BaseModel;
use App\Models\Core\User;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledSocialPost extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.scheduled_social_posts_v2';
    protected $primaryKey = 'scheduled_post_id';

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_PUBLISHING = 'publishing';
    const STATUS_PUBLISHED = 'published';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'scheduled_post_id',
        'social_post_id',
        'org_id',
        'user_id',
        'campaign_id',
        'platforms',
        'content',
        'media',
        'scheduled_at',
        'published_at',
        'status',
        'error_message',
        'integration_ids',
    ];

    protected $casts = [
        'platforms' => 'array',
        'media' => 'array',
        'integration_ids' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id', 'campaign_id');
    }

    // ===== Scopes =====

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeDrafts($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    // ===== Status Methods =====

    public function markAsPublishing(): void
    {
        $this->update(['status' => self::STATUS_PUBLISHING]);
    }

    public function markAsPublished(): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }
}
