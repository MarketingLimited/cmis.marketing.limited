<?php

namespace App\Models\Social;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\Core\User;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ScheduledPost extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.scheduled_posts';
    protected $primaryKey = 'post_id';

    protected $fillable = [
        'post_id',
        'org_id',
        'created_by',
        'content_library_id',
        'title',
        'content',
        'media_urls',
        'media_metadata',
        'post_type',
        'platforms',
        'platform_specific_content',
        'status',
        'scheduled_at',
        'published_at',
        'targeting',
        'hashtags',
        'first_comment',
        'priority',
        'approval_workflow',
        'approval_status',
        'approved_by',
        'approved_at',
        'retry_count',
        'error_message',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'media_metadata' => 'array',
        'platforms' => 'array',
        'platform_specific_content' => 'array',
        'targeting' => 'array',
        'hashtags' => 'array',
        'approval_workflow' => 'array',
        'first_comment' => 'boolean',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'approved_at' => 'datetime',
        'retry_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');

    public function contentLibrary(): BelongsTo
    {
        return $this->belongsTo(ContentLibrary::class, 'content_library_id', 'library_id');

    public function platformPosts(): HasMany
    {
        return $this->hasMany(PlatformPost::class, 'scheduled_post_id', 'post_id');

    public function queueItems(): HasMany
    {
        return $this->hasMany(PublishingQueue::class, 'scheduled_post_id', 'post_id');

    // ===== Status Management =====

    public function schedule(string $scheduledAt): void
    {
        $this->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
        ]);

    public function markAsPublishing(): void
    {
        $this->update(['status' => 'publishing']);

    public function markAsPublished(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
        $this->increment('retry_count');

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);

    // ===== Approval Workflow =====

    public function requestApproval(): void
    {
        $this->update([
            'approval_status' => 'pending',
        ]);

    public function approve(string $userId): void
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

    public function reject(): void
    {
        $this->update([
            'approval_status' => 'rejected',
        ]);

    public function needsApproval(): bool
    {
        return !empty($this->approval_workflow) && $this->approval_status === null;

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';

    // ===== Content Helpers =====

    public function getContentForPlatform(string $platform): string
    {
        if (isset($this->platform_specific_content[$platform])) {
            return $this->platform_specific_content[$platform];
        return $this->content;

    public function hasMedia(): bool
    {
        return !empty($this->media_urls);

    public function getMediaCount(): int
    {
        return count($this->media_urls ?? []);

    public function getPlatformCount(): int
    {
        return count($this->platforms ?? []);

    public function getHashtagString(): string
    {
        if (empty($this->hashtags)) {
            return '';
        return implode(' ', array_map(fn($tag) => '#' . ltrim($tag, '#'), $this->hashtags));

    // ===== Scheduling Helpers =====

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';

    public function isPublished(): bool
    {
        return $this->status === 'published';

    public function isDraft(): bool
    {
        return $this->status === 'draft';

    public function isPastScheduledTime(): bool
    {
        return $this->scheduled_at && now()->isAfter($this->scheduled_at);

    public function canBePublished(): bool
    {
        if (!$this->isScheduled()) {
            return false;

        if ($this->needsApproval() && !$this->isApproved()) {
            return false;

        return $this->isPastScheduledTime();

    // ===== Performance Tracking =====

    public function getTotalEngagement(): int
    {
        return $this->platformPosts()->sum('engagement');

    public function getAverageEngagementRate(): float
    {
        return $this->platformPosts()->avg('engagement_rate') ?? 0;

    public function getTotalReach(): int
    {
        return $this->platformPosts()->sum('views');

    // ===== Scopes =====

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');

    public function scopePublished($query)
    {
        return $query->where('status', 'published');

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');

    public function scopeDueForPublishing($query)
    {
        return $query->where('status', 'scheduled')
                     ->where('scheduled_at', '<=', now());

    public function scopeForPlatform($query, string $platform)
    {
        return $query->whereJsonContains('platforms', $platform);

    public function scopePendingApproval($query)
    {
        return $query->where('approval_status', 'pending');
}
