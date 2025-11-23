<?php

namespace App\Models\Listening;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;

class CompetitorProfile extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.competitor_profiles';
    protected $primaryKey = 'competitor_id';

    protected $fillable = [
        'org_id',
        'created_by',
        'competitor_name',
        'industry',
        'description',
        'website',
        'logo_url',
        'social_accounts',
        'monitoring_settings',
        'follower_counts',
        'posting_frequency',
        'engagement_stats',
        'content_themes',
        'status',
        'enable_alerts',
        'last_analyzed_at',
    ];

    protected $casts = [
        'social_accounts' => 'array',
        'monitoring_settings' => 'array',
        'follower_counts' => 'array',
        'posting_frequency' => 'array',
        'engagement_stats' => 'array',
        'content_themes' => 'array',
        'enable_alerts' => 'boolean',
        'last_analyzed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status Management
     */

    public function activate(): void
    {
        $this->update(['status' => 'active']);

        }
    public function pause(): void
    {
        $this->update(['status' => 'paused']);

        }
    public function archive(): void
    {
        $this->update(['status' => 'archived']);

        }
    public function isActive(): bool
    {
        return $this->status === 'active';

    }
    /**
     * Social Account Management
     */

    public function addSocialAccount(string $platform, array $accountData): void
    {
        $accounts = $this->social_accounts;
        $accounts[$platform] = $accountData;
        $this->update(['social_accounts' => $accounts]);

        }
    public function removeSocialAccount(string $platform): void
    {
        $accounts = $this->social_accounts;
        unset($accounts[$platform]);
        $this->update(['social_accounts' => $accounts]);

        }
    public function hasPlatform(string $platform): bool
    {
        return isset($this->social_accounts[$platform]);

        }
    public function getPlatformAccount(string $platform): ?array
    {
        return $this->social_accounts[$platform] ?? null;

        }
    public function getMonitoredPlatforms(): array
    {
        return array_keys($this->social_accounts);

    }
    /**
     * Metrics Management
     */

    public function updateFollowerCount(string $platform, int $count): void
    {
        $followers = $this->follower_counts;
        $followers[$platform] = $count;
        $this->update(['follower_counts' => $followers]);

        }
    public function getTotalFollowers(): int
    {
        return array_sum($this->follower_counts);

        }
    public function getFollowerGrowth(string $platform): ?float
    {
        // Calculate based on historical data if available
        $current = $this->follower_counts[$platform] ?? 0;
        $previous = $this->engagement_stats[$platform]['previous_followers'] ?? $current;

        if ($previous == 0) {
            return null;
        }

        return (($current - $previous) / $previous) * 100;
    }
    public function updatePostingFrequency(string $platform, float $postsPerDay): void
    {
        $frequency = $this->posting_frequency;
        $frequency[$platform] = $postsPerDay;
        $this->update(['posting_frequency' => $frequency]);

        }
    public function getAveragePostingFrequency(): float
    {
        if (empty($this->posting_frequency)) {
            return 0;
        }

        return array_sum($this->posting_frequency) / count($this->posting_frequency);
    }
    public function updateEngagementStats(string $platform, array $stats): void
    {
        $engagement = $this->engagement_stats;
        $engagement[$platform] = array_merge($engagement[$platform] ?? [], $stats);
        $this->update(['engagement_stats' => $engagement]);

        }
    public function getEngagementRate(string $platform): ?float
    {
        return $this->engagement_stats[$platform]['engagement_rate'] ?? null;

    }
    /**
     * Content Theme Analysis
     */

    public function addContentTheme(string $theme): void
    {
        $themes = $this->content_themes;
        if (!in_array($theme, $themes)) {
            $themes[] = $theme;
            $this->update(['content_themes' => $themes]);
        }
    }
    public function getTopThemes(int $limit = 5): array
    {
        return array_slice($this->content_themes, 0, $limit);

    }
    /**
     * Analysis Tracking
     */

    public function markAsAnalyzed(): void
    {
        $this->update(['last_analyzed_at' => now()]);

        }
    public function needsAnalysis(int $hoursThreshold = 24): bool
    {
        if (!$this->last_analyzed_at) {
            return true;
        }

        return $this->last_analyzed_at->lt(now()->subHours($hoursThreshold));
    }
    /**
     * Scopes
     */

    public function scopeActive($query): Builder
    {
        return $query->where('status', 'active');

        }
    public function scopeWithAlerts($query): Builder
    {
        return $query->where('enable_alerts', true);

        }
    public function scopeInIndustry($query, string $industry): Builder
    {
        return $query->where('industry', $industry);

        }
    public function scopeNeedsAnalysis($query, int $hoursThreshold = 24): Builder
    {
        return $query->where(function($q) use ($hoursThreshold) {
            $q->whereNull('last_analyzed_at')
              ->orWhere('last_analyzed_at', '<', now()->subHours($hoursThreshold));
        });
    }
}
