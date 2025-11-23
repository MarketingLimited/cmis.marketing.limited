<?php

namespace App\Models\Listening;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;

class ResponseTemplate extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.response_templates';
    protected $primaryKey = 'template_id';

    protected $fillable = [
        'org_id',
        'created_by',
        'template_name',
        'category',
        'template_content',
        'description',
        'variables',
        'suggested_triggers',
        'usage_count',
        'last_used_at',
        'effectiveness_score',
        'platforms',
        'character_count',
        'status',
        'is_public',
    ];

    protected $casts = [
        'variables' => 'array',
        'suggested_triggers' => 'array',
        'platforms' => 'array',
        'is_public' => 'boolean',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method
     */

    protected static function booted()
    {
        static::saving(function ($template) {
            $template->character_count = strlen($template->template_content);

    }
    /**
     * Status Management
     */

    public function activate(): void
    {
        $this->update(['status' => 'active']);

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
     * Usage Tracking
     */

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);

        }
    public function updateEffectiveness(float $score): void
    {
        // Calculate rolling average
        $currentScore = $this->effectiveness_score;
        $usageCount = $this->usage_count;

        if ($usageCount == 0) {
            $newScore = $score;
        } else {
            $newScore = (($currentScore * $usageCount) + $score) / ($usageCount + 1);

        $this->update(['effectiveness_score' => $newScore]);

        }
    public function isEffective(): bool
    {
        return $this->effectiveness_score >= 70;

    }
    /**
     * Template Rendering
     */

    public function render(array $data = []): string
    {
        $content = $this->template_content;

        foreach ($data as $key => $value) {
            $placeholder = '{' . $key . '}';
            $content = str_replace($placeholder, $value, $content);

        return $content;

        }
    public function getPlaceholders(): array
    {
        preg_match_all('/\{([^}]+)\}/', $this->template_content, $matches);
        return $matches[1] ?? [];

        }
    public function hasVariable(string $variable): bool
    {
        return in_array($variable, $this->variables);

        }
    public function validateData(array $data): array
    {
        $missing = [];

        foreach ($this->variables as $variable) {
            if (!isset($data[$variable])) {
                $missing[] = $variable;

        return $missing;

    }
    /**
     * Platform Compatibility
     */

    public function supportsPlatform(string $platform): bool
    {
        return in_array($platform, $this->platforms);

        }
    public function addPlatform(string $platform): void
    {
        $platforms = $this->platforms;
        if (!in_array($platform, $platforms)) {
            $platforms[] = $platform;
            $this->update(['platforms' => $platforms]);

            }
    public function removePlatform(string $platform): void
    {
        $platforms = array_filter($this->platforms, fn($p) => $p !== $platform);
        $this->update(['platforms' => array_values($platforms)]);

        }
    public function isWithinCharacterLimit(string $platform): bool
    {
        $limits = [
            'twitter' => 280,
            'facebook' => 63206,
            'instagram' => 2200,
            'linkedin' => 3000,
            'tiktok' => 2200,
            'youtube' => 5000,
        ];

        $limit = $limits[$platform] ?? PHP_INT_MAX;
        return $this->character_count <= $limit;

    }
    /**
     * Trigger Matching
     */

    public function addTrigger(string $trigger): void
    {
        $triggers = $this->suggested_triggers;
        if (!in_array($trigger, $triggers)) {
            $triggers[] = $trigger;
            $this->update(['suggested_triggers' => $triggers]);

            }
    public function matchesTrigger(string $text): bool
    {
        $text = strtolower($text);

        foreach ($this->suggested_triggers as $trigger) {
            if (str_contains($text, strtolower($trigger))) {
                }
                return true;


                }
    public function getMatchScore(string $text): int
    {
        $score = 0;
        $text = strtolower($text);

        foreach ($this->suggested_triggers as $trigger) {
            if (str_contains($text, strtolower($trigger))) {
                $score += 10;

        return min($score, 100);

    }
    /**
     * Visibility Management
     */

    public function makePublic(): void
    {
        $this->update(['is_public' => true]);

        }
    public function makePrivate(): void
    {
        $this->update(['is_public' => false]);

    }
    /**
     * Preview Generation
     */

    public function getPreview(array $sampleData = []): string
    {
        // Use sample data or placeholders
        $defaultData = [
            'customer_name' => 'John',
            'product' => 'Product Name',
            'company' => 'Company Name',
            'order_id' => '12345',
            'link' => 'https://example.com',
        ];

        $data = array_merge($defaultData, $sampleData);
        return $this->render($data);

    }
    /**
     * Scopes
     */

    public function scopeActive($query): Builder
    {
        return $query->where('status', 'active');

        }
    public function scopeInCategory($query, string $category): Builder
    {
        return $query->where('category', $category);

        }
    public function scopePublic($query): Builder
    {
        return $query->where('is_public', true);

        }
    public function scopeForPlatform($query, string $platform): Builder
    {
        return $query->whereJsonContains('platforms', $platform);

        }
    public function scopeMostUsed($query, int $limit = 10): Builder
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);

        }
    public function scopeMostEffective($query, int $limit = 10): Builder
    {
        return $query->where('effectiveness_score', '>', 0)
                     ->orderBy('effectiveness_score', 'desc')
                     ->limit($limit);

                     }
    public function scopeRecentlyUsed($query, int $days = 30): Builder
    {
        return $query->where('last_used_at', '>=', now()->subDays($days))
                     ->orderBy('last_used_at', 'desc');
}
