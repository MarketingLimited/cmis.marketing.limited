<?php

namespace App\Models\Social;

use App\Models\Concerns\HasOrganization;

use App\Models\Core\Org;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PlatformPost extends BaseModel
{
    use HasFactory;
    use HasOrganization;

    protected $table = 'cmis.platform_posts';
    protected $primaryKey = 'platform_post_id';

    protected $fillable = [
        'platform_post_id',
        'org_id',
        'scheduled_post_id',
        'platform',
        'platform_post_id_external',
        'platform_url',
        'status',
        'platform_response',
        'published_at',
        'likes',
        'comments',
        'shares',
        'views',
        'engagement',
        'engagement_rate',
        'last_synced_at',
        'error_message',
    ];

    protected $casts = [
        'platform_response' => 'array',
        'likes' => 'integer',
        'comments' => 'integer',
        'shares' => 'integer',
        'views' => 'integer',
        'engagement' => 'integer',
        'engagement_rate' => 'decimal:2',
        'published_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===== Relationships =====

    

    public function scheduledPost(): BelongsTo
    {
        return $this->belongsTo(ScheduledPost::class, 'scheduled_post_id', 'post_id');

    // ===== Status Management =====

    public function markAsPublishing(): void
    {
        $this->update(['status' => 'publishing']);

    public function markAsPublished(string $externalId, string $url): void
    {
        $this->update([
            'status' => 'published',
            'platform_post_id_external' => $externalId,
            'platform_url' => $url,
            'published_at' => now(),
        ]);

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);

    // ===== Metrics Management =====

    public function updateMetrics(array $metrics): void
    {
        $this->update([
            'likes' => $metrics['likes'] ?? $this->likes,
            'comments' => $metrics['comments'] ?? $this->comments,
            'shares' => $metrics['shares'] ?? $this->shares,
            'views' => $metrics['views'] ?? $this->views,
            'engagement' => $metrics['engagement'] ?? $this->calculateEngagement($metrics),
            'last_synced_at' => now(),
        ]);

        // Recalculate engagement rate
        $this->updateEngagementRate();

    protected function calculateEngagement(array $metrics): int
    {
        return ($metrics['likes'] ?? 0) +
               ($metrics['comments'] ?? 0) +
               ($metrics['shares'] ?? 0);

    public function updateEngagementRate(): void
    {
        if ($this->views > 0) {
            $rate = ($this->engagement / $this->views) * 100;
            $this->update(['engagement_rate' => round($rate, 2)]);

    // ===== Platform Helpers =====

    public function getPlatformLabel(): string
    {
        return match($this->platform) {
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'twitter' => 'Twitter (X)',
            'linkedin' => 'LinkedIn',
            'tiktok' => 'TikTok',
            'youtube' => 'YouTube',
            'snapchat' => 'Snapchat',
            default => ucfirst($this->platform)
        };

    public function isPublished(): bool
    {
        return $this->status === 'published';

    // ===== Scopes =====

    public function scopePublished($query)
    {
        return $query->where('status', 'published');

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
}
