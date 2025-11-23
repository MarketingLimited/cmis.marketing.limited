<?php

namespace App\Models\Listening;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SentimentAnalysis extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.sentiment_analysis';
    protected $primaryKey = 'analysis_id';

    protected $fillable = [
        'org_id',
        'mention_id',
        'overall_sentiment',
        'sentiment_score',
        'confidence',
        'positive_score',
        'negative_score',
        'neutral_score',
        'mixed_score',
        'emotions',
        'primary_emotion',
        'key_phrases',
        'entities',
        'topics',
        'model_used',
        'model_version',
        'model_response',
        'analyzed_at',
    ];

    protected $casts = [
        'emotions' => 'array',
        'key_phrases' => 'array',
        'entities' => 'array',
        'topics' => 'array',
        'model_response' => 'array',
        'analyzed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */

    public function mention(): BelongsTo
    {
        return $this->belongsTo(SocialMention::class, 'mention_id', 'mention_id');

    }
    /**
     * Sentiment Helpers
     */

    public function isPositive(): bool
    {
        return $this->overall_sentiment === 'positive';

        }
    public function isNegative(): bool
    {
        return $this->overall_sentiment === 'negative';

        }
    public function isNeutral(): bool
    {
        return $this->overall_sentiment === 'neutral';

        }
    public function isMixed(): bool
    {
        return $this->overall_sentiment === 'mixed';

        }
    public function isHighConfidence(): bool
    {
        return $this->confidence >= 80;

        }
    public function getConfidenceLabel(): string
    {
        if ($this->confidence >= 90) return 'Very High';
        if ($this->confidence >= 80) return 'High';
        if ($this->confidence >= 70) return 'Medium';
        if ($this->confidence >= 60) return 'Low';
        return 'Very Low';

    }
    /**
     * Emotion Analysis
     */

    public function getPrimaryEmotion(): ?string
    {
        if ($this->primary_emotion) {
            return $this->primary_emotion;
        }

        // Find emotion with highest score
        if (empty($this->emotions)) {
            return null;
        }

        return array_search(max($this->emotions), $this->emotions);
    }

    public function getEmotionScore(string $emotion): float
    {
        return $this->emotions[$emotion] ?? 0.0;
    }
    public function hasEmotion(string $emotion, float $threshold = 0.5): bool
    {
        return ($this->emotions[$emotion] ?? 0) >= $threshold;

    }
    /**
     * Entity Extraction
     */

    public function getEntitiesByType(string $type): array
    {
        $entities = [];
        foreach ($this->entities as $entity) {
            if (isset($entity['type']) && $entity['type'] === $type) {
                $entities[] = $entity;
            }
        }
        return $entities;
    }
    public function hasPerson(): bool
    {
        return !empty($this->getEntitiesByType('PERSON'));

        }
    public function hasOrganization(): bool
    {
        return !empty($this->getEntitiesByType('ORGANIZATION'));

        }
    public function hasProduct(): bool
    {
        return !empty($this->getEntitiesByType('PRODUCT'));

        }
    public function hasLocation(): bool
    {
        return !empty($this->getEntitiesByType('LOCATION'));

    }
    /**
     * Topic Analysis
     */

    public function hasTopic(string $topic): bool
    {
        return in_array($topic, $this->topics);

        }
    public function getTopicsString(): string
    {
        return implode(', ', $this->topics);

    }
    /**
     * Key Phrases
     */

    public function getTopKeyPhrases(int $limit = 5): array
    {
        return array_slice($this->key_phrases, 0, $limit);

    }
    /**
     * Sentiment Score Interpretation
     */

    public function getSentimentIntensity(): string
    {
        $absScore = abs($this->sentiment_score);

        if ($absScore >= 0.8) return 'Very Strong';
        if ($absScore >= 0.6) return 'Strong';
        if ($absScore >= 0.4) return 'Moderate';
        if ($absScore >= 0.2) return 'Slight';
        return 'Weak';

        }
    public function getSentimentDescription(): string
    {
        $intensity = $this->getSentimentIntensity();
        $sentiment = ucfirst($this->overall_sentiment);

        return "{$intensity} {$sentiment}";
    }

    /**
     * Scopes
     */

    public function scopeWithSentiment($query, string $sentiment): Builder
    {
        return $query->where('overall_sentiment', $sentiment);

        }
    public function scopeHighConfidence($query): Builder
    {
        return $query->where('confidence', '>=', 80);

        }
    public function scopeWithEmotion($query, string $emotion, float $threshold = 0.5): Builder
    {
        return $query->whereRaw("(emotions->>'$emotion')::float >= ?", [$threshold]);

        }
    public function scopeAnalyzedBy($query, string $model): Builder
    {
        return $query->where('model_used', $model);
    }
}
