<?php

namespace App\Models\Creative;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use App\Models\Social\ProfileGroup;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BrandVoice Model
 *
 * Represents an AI-powered brand voice profile for consistent content generation.
 * Stores tone, personality traits, content guidelines, and AI model configuration.
 *
 * @property string $voice_id
 * @property string $org_id
 * @property string|null $profile_group_id
 * @property string $name
 * @property string $description
 * @property string $tone
 * @property array $personality_traits
 * @property array $inspired_by
 * @property string|null $target_audience
 * @property array $keywords_to_use
 * @property array $keywords_to_avoid
 * @property string $emojis_preference
 * @property string $hashtag_strategy
 * @property array $example_posts
 * @property string $primary_language
 * @property array $secondary_languages
 * @property string|null $dialect_preference
 * @property string|null $ai_system_prompt
 * @property float $temperature
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class BrandVoice extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis.brand_voices';
    protected $primaryKey = 'voice_id';

    protected $fillable = [
        'org_id',
        'profile_group_id',
        'name',
        'description',
        'tone',
        'personality_traits',
        'inspired_by',
        'target_audience',
        'keywords_to_use',
        'keywords_to_avoid',
        'emojis_preference',
        'hashtag_strategy',
        'example_posts',
        'primary_language',
        'secondary_languages',
        'dialect_preference',
        'ai_system_prompt',
        'temperature',
        'created_by',
    ];

    protected $casts = [
        'personality_traits' => 'array',
        'inspired_by' => 'array',
        'keywords_to_use' => 'array',
        'keywords_to_avoid' => 'array',
        'example_posts' => 'array',
        'secondary_languages' => 'array',
        'temperature' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the profile group this brand voice belongs to
     */
    public function profileGroup(): BelongsTo
    {
        return $this->belongsTo(ProfileGroup::class, 'profile_group_id', 'group_id');
    }

    /**
     * Get the user who created this brand voice
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Get all profile groups using this brand voice
     */
    public function profileGroups(): HasMany
    {
        return $this->hasMany(ProfileGroup::class, 'brand_voice_id', 'voice_id');
    }

    /**
     * Scope to get org-wide brand voices (not tied to specific group)
     */
    public function scopeOrgWide($query)
    {
        return $query->whereNull('profile_group_id');
    }

    /**
     * Scope to get group-specific brand voices
     */
    public function scopeForGroup($query, string $groupId)
    {
        return $query->where('profile_group_id', $groupId);
    }

    /**
     * Scope to filter by tone
     */
    public function scopeByTone($query, string $tone)
    {
        return $query->where('tone', $tone);
    }

    /**
     * Scope to filter by primary language
     */
    public function scopeByLanguage($query, string $language)
    {
        return $query->where('primary_language', $language);
    }

    /**
     * Check if this brand voice is org-wide (not group-specific)
     */
    public function isOrgWide(): bool
    {
        return is_null($this->profile_group_id);
    }

    /**
     * Check if this brand voice has AI system prompt configured
     */
    public function hasCustomSystemPrompt(): bool
    {
        return !is_null($this->ai_system_prompt) && !empty($this->ai_system_prompt);
    }

    /**
     * Check if this brand voice has example posts for training
     */
    public function hasExamplePosts(): bool
    {
        return !empty($this->example_posts);
    }

    /**
     * Get the emoji preference level as a numeric value
     */
    public function getEmojiLevelAttribute(): int
    {
        $levels = [
            'none' => 0,
            'minimal' => 1,
            'moderate' => 2,
            'frequent' => 3,
        ];

        return $levels[$this->emojis_preference] ?? 2;
    }

    /**
     * Get the hashtag strategy level as a numeric value
     */
    public function getHashtagLevelAttribute(): int
    {
        $levels = [
            'none' => 0,
            'minimal' => 1,
            'moderate' => 2,
            'extensive' => 3,
        ];

        return $levels[$this->hashtag_strategy] ?? 2;
    }

    /**
     * Get all personality traits as a comma-separated string
     */
    public function getPersonalityTraitsStringAttribute(): string
    {
        return implode(', ', $this->personality_traits ?? []);
    }

    /**
     * Get the count of profile groups using this voice
     */
    public function getUsageCountAttribute(): int
    {
        return $this->profileGroups()->count();
    }
}
