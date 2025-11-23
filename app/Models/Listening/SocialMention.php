<?php

namespace App\Models\Listening;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialMention extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.social_mentions';
    protected $primaryKey = 'mention_id';

    protected $fillable = [
        'org_id',
        'keyword_id',
        'platform',
        'platform_post_id',
        'post_url',
        'mention_type',
        'author_username',
        'author_display_name',
        'author_profile_url',
        'author_profile_image',
        'author_followers_count',
        'author_is_verified',
        'content',
        'media_urls',
        'hashtags',
        'mentioned_accounts',
        'language',
        'likes_count',
        'comments_count',
        'shares_count',
        'views_count',
        'engagement_rate',
        'sentiment',
        'sentiment_score',
        'sentiment_confidence',
        'detected_topics',
        'detected_entities',
        'status',
        'requires_response',
        'assigned_to',
        'responded_at',
        'internal_notes',
        'published_at',
        'captured_at',
        'last_synced_at',
        'raw_data',
    ];

    protected $casts = [
        'author_is_verified' => 'boolean',
        'media_urls' => 'array',
        'hashtags' => 'array',
        'mentioned_accounts' => 'array',
        'detected_topics' => 'array',
        'detected_entities' => 'array',
        'requires_response' => 'boolean',
        'raw_data' => 'array',
        'published_at' => 'datetime',
        'captured_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'responded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */

    public function keyword(): BelongsTo
    {
        return $this->belongsTo(MonitoringKeyword::class, 'keyword_id', 'keyword_id');

        }
    public function sentimentAnalysis(): HasOne
    {
        return $this->hasOne(SentimentAnalysis::class, 'mention_id', 'mention_id');

        }
    public function conversations(): HasMany
    {
        return $this->hasMany(SocialConversation::class, 'root_mention_id', 'mention_id');

    }
    /**
     * Status Management
     */

    public function markAsReviewed(): void
    {
        $this->update(['status' => 'reviewed']);
    }

    public function markAsResponded(): void
    {
        $this->update([
            'status' => 'responded',
            'responded_at' => now(),
            'requires_response' => false,
        ]);
    }

    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    public function flag(): void
    {
        $this->update(['status' => 'flagged']);
    }

    public function isNew(): bool
    {
        return $this->status === 'new';

        }
    public function needsResponse(): bool
    {
        return $this->requires_response && !$this->responded_at;

    }
    /**
     * Assignment
     */

    public function assignTo(string $userId): void
    {
        $this->update(['assigned_to' => $userId]);
    }

    public function unassign(): void
    {
        $this->update(['assigned_to' => null]);
    }
    /**
     * Sentiment Methods
     */

    public function updateSentiment(string $sentiment, float $score, int $confidence): void
    {
        $this->update([
            'sentiment' => $sentiment,
            'sentiment_score' => $score,
            'sentiment_confidence' => $confidence,
        ]);
    }

    public function isPositive(): bool
    {
        return $this->sentiment === 'positive';

        }
    public function isNegative(): bool
    {
        return $this->sentiment === 'negative';

        }
    public function isNeutral(): bool
    {
        return $this->sentiment === 'neutral';

        }
    public function getSentimentLabel(): string
    {
        return match($this->sentiment) {
            'positive' => '😊 Positive',
            'negative' => '😞 Negative',
            'neutral' => '😐 Neutral',
            'mixed' => '🤔 Mixed',
            default => '❓ Unknown'
        };
    }

    public function getSentimentColor(): string
    {
        return match($this->sentiment) {
            'positive' => 'green',
            'negative' => 'red',
            'neutral' => 'gray',
            'mixed' => 'yellow',
            default => 'gray'
        };
    }
    /**
     * Engagement Methods
     */

    public function updateMetrics(array $metrics): void
    {
        $data = [
            'likes_count' => $metrics['likes'] ?? $this->likes_count,
            'comments_count' => $metrics['comments'] ?? $this->comments_count,
            'shares_count' => $metrics['shares'] ?? $this->shares_count,
            'views_count' => $metrics['views'] ?? $this->views_count,
            'last_synced_at' => now(),
        ];

        // Calculate engagement rate
        $totalEngagement = $data['likes_count'] + $data['comments_count'] + $data['shares_count'];
        if ($data['views_count'] > 0) {
            $data['engagement_rate'] = ($totalEngagement / $data['views_count']) * 100;
        }

        $this->update($data);
    }

    public function getTotalEngagement(): int
    {
        return $this->likes_count + $this->comments_count + $this->shares_count;

        }
    public function hasHighEngagement(): bool
    {
        return $this->engagement_rate > 5.0; // 5% is considered high

    }
    /**
     * Author Methods
     */

    public function isInfluencer(): bool
    {
        return $this->author_followers_count > 10000 || $this->author_is_verified;

        }
    public function getAuthorDisplayName(): string
    {
        return $this->author_display_name ?? $this->author_username;

        }
    public function getAuthorBadge(): string
    {
        if ($this->author_is_verified) {
            return '✓';
        }

        if ($this->author_followers_count > 100000) {
            return '⭐';
        }

        return '';
    }
    /**
     * Content Methods
     */

    public function hasMedia(): bool
    {
        return count($this->media_urls) > 0;

        }
    public function getExcerpt(int $length = 100): string
    {
        if (strlen($this->content) <= $length) {
            return $this->content;
        }

        return substr($this->content, 0, $length) . '...';
    }
    public function getHashtagsString(): string
    {
        return implode(' ', array_map(fn($tag) => "#{$tag}", $this->hashtags));
    }

    /**
     * Analysis Methods
     */

    public function addTopic(string $topic): void
    {
        $topics = $this->detected_topics;
        if (!in_array($topic, $topics)) {
            $topics[] = $topic;
            $this->update(['detected_topics' => $topics]);
        }
    }

    public function addEntity(string $entityType, string $entityValue): void
    {
        $entities = $this->detected_entities;
        $entities[$entityType] = $entities[$entityType] ?? [];

        if (!in_array($entityValue, $entities[$entityType])) {
            $entities[$entityType][] = $entityValue;
            $this->update(['detected_entities' => $entities]);
        }
    }
    /**
     * Scopes
     */

    public function scopeForKeyword($query, string $keywordId): Builder
    {
        return $query->where('keyword_id', $keywordId);

        }
    public function scopeOnPlatform($query, string $platform): Builder
    {
        return $query->where('platform', $platform);

        }
    public function scopeWithSentiment($query, string $sentiment): Builder
    {
        return $query->where('sentiment', $sentiment);

        }
    public function scopeNeedsResponse($query): Builder
    {
        return $query->where('requires_response', true)
                     ->whereNull('responded_at');
    }

    public function scopeByStatus($query, string $status): Builder
    {
        return $query->where('status', $status);

        }
    public function scopeAssignedTo($query, string $userId): Builder
    {
        return $query->where('assigned_to', $userId);

        }
    public function scopeUnassigned($query): Builder
    {
        return $query->whereNull('assigned_to');

        }
    public function scopeRecentFirst($query): Builder
    {
        return $query->orderBy('published_at', 'desc');

        }
    public function scopeHighEngagement($query): Builder
    {
        return $query->where('engagement_rate', '>', 5.0);

        }
    public function scopeFromInfluencers($query): Builder
    {
        return $query->where(function($q) {
            $q->where('author_followers_count', '>', 10000)
              ->orWhere('author_is_verified', true);
        });
    }

    public function scopePublishedBetween($query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('published_at', [$startDate, $endDate]);
    }
}
