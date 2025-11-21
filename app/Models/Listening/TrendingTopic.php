<?php

namespace App\Models\Listening;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrendingTopic extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.trending_topics';
    protected $primaryKey = 'trend_id';

    protected $fillable = [
        'org_id',
        'topic',
        'topic_type',
        'description',
        'related_keywords',
        'mention_count',
        'mention_count_24h',
        'mention_count_7d',
        'growth_rate',
        'trend_velocity',
        'platform_distribution',
        'geographic_distribution',
        'overall_sentiment',
        'avg_sentiment_score',
        'first_seen_at',
        'peak_at',
        'last_seen_at',
        'status',
        'relevance_score',
        'is_opportunity',
    ];

    protected $casts = [
        'related_keywords' => 'array',
        'platform_distribution' => 'array',
        'geographic_distribution' => 'array',
        'is_opportunity' => 'boolean',
        'first_seen_at' => 'datetime',
        'peak_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Mention Tracking
     */

    public function incrementMentions(int $count = 1): void
    {
        $this->increment('mention_count', $count);
        $this->update(['last_seen_at' => now()]);
    }

    public function updateDailyMentions(int $count): void
    {
        $this->update(['mention_count_24h' => $count]);
    }

    public function updateWeeklyMentions(int $count): void
    {
        $this->update(['mention_count_7d' => $count]);
    }

    /**
     * Growth Analysis
     */

    public function calculateGrowthRate(): void
    {
        if ($this->mention_count_7d == 0) {
            $this->update(['growth_rate' => 0]);
            return;
        }

        $previousWeek = $this->mention_count - $this->mention_count_7d;
        if ($previousWeek == 0) {
            $this->update(['growth_rate' => 100]);
            return;
        }

        $growthRate = (($this->mention_count_7d - $previousWeek) / $previousWeek) * 100;
        $this->update(['growth_rate' => $growthRate]);
    }

    public function updateTrendVelocity(): void
    {
        $velocity = 'normal';

        if ($this->growth_rate > 500) {
            $velocity = 'viral';
        } elseif ($this->growth_rate > 100) {
            $velocity = 'rising';
        } elseif ($this->growth_rate < -50) {
            $velocity = 'declining';
        }

        $this->update(['trend_velocity' => $velocity]);
    }

    public function isViral(): bool
    {
        return $this->trend_velocity === 'viral';
    }

    public function isRising(): bool
    {
        return $this->trend_velocity === 'rising';
    }

    public function isDeclining(): bool
    {
        return $this->trend_velocity === 'declining';
    }

    /**
     * Platform Analysis
     */

    public function updatePlatformDistribution(array $distribution): void
    {
        $this->update(['platform_distribution' => $distribution]);
    }

    public function getTopPlatform(): ?string
    {
        if (empty($this->platform_distribution)) {
            return null;
        }

        return array_key_first(
            array_slice(
                arsort($this->platform_distribution),
                0,
                1,
                true
            )
        ) ?: null;
    }

    public function getPlatformPercentage(string $platform): float
    {
        if ($this->mention_count == 0) {
            return 0;
        }

        $platformCount = $this->platform_distribution[$platform] ?? 0;
        return ($platformCount / $this->mention_count) * 100;
    }

    /**
     * Sentiment Analysis
     */

    public function updateSentiment(string $sentiment, float $avgScore): void
    {
        $this->update([
            'overall_sentiment' => $sentiment,
            'avg_sentiment_score' => $avgScore,
        ]);
    }

    public function isPositiveTrend(): bool
    {
        return $this->overall_sentiment === 'positive';
    }

    public function isNegativeTrend(): bool
    {
        return $this->overall_sentiment === 'negative';
    }

    /**
     * Relevance & Opportunity
     */

    public function calculateRelevanceScore(array $factors = []): void
    {
        $score = 0;

        // Base score from mention count
        $score += min(($this->mention_count_24h / 100) * 20, 20);

        // Growth rate bonus
        if ($this->growth_rate > 100) {
            $score += 25;
        } elseif ($this->growth_rate > 50) {
            $score += 15;
        }

        // Positive sentiment bonus
        if ($this->overall_sentiment === 'positive') {
            $score += 15;
        }

        // Custom factors
        foreach ($factors as $factor => $value) {
            $score += $value;
        }

        $this->update(['relevance_score' => min($score, 100)]);
    }

    public function markAsOpportunity(): void
    {
        $this->update(['is_opportunity' => true]);
    }

    public function unmarkAsOpportunity(): void
    {
        $this->update(['is_opportunity' => false]);
    }

    public function isHighRelevance(): bool
    {
        return $this->relevance_score >= 70;
    }

    /**
     * Status Management
     */

    public function markAsActive(): void
    {
        $this->update(['status' => 'active']);
    }

    public function markAsDeclining(): void
    {
        $this->update(['status' => 'declining']);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function updatePeak(): void
    {
        if (!$this->peak_at || $this->mention_count_24h > $this->mention_count) {
            $this->update(['peak_at' => now()]);
        }
    }

    /**
     * Time Analysis
     */

    public function getAge(): int
    {
        return $this->first_seen_at->diffInHours(now());
    }

    public function getTimeSincePeak(): ?int
    {
        if (!$this->peak_at) {
            return null;
        }

        return $this->peak_at->diffInHours(now());
    }

    /**
     * Scopes
     */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeViral($query)
    {
        return $query->where('trend_velocity', 'viral');
    }

    public function scopeRising($query)
    {
        return $query->where('trend_velocity', 'rising');
    }

    public function scopeHighRelevance($query)
    {
        return $query->where('relevance_score', '>=', 70);
    }

    public function scopeOpportunities($query)
    {
        return $query->where('is_opportunity', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('topic_type', $type);
    }

    public function scopePositive($query)
    {
        return $query->where('overall_sentiment', 'positive');
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderBy('first_seen_at', 'desc');
    }

    public function scopeByRelevance($query)
    {
        return $query->orderBy('relevance_score', 'desc');
    }
}
