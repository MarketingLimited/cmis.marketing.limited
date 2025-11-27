<?php

namespace App\Models\Social;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\Org;
use App\Models\Core\User;
use App\Models\Creative\BrandVoice;
use App\Models\Compliance\BrandSafetyPolicy;
use App\Models\Workflow\ApprovalWorkflow;
use App\Models\Platform\AdAccount;
use App\Models\Platform\BoostRule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ProfileGroup Model
 *
 * Represents a group of social media profiles organized by client/brand.
 * Central entity for organizing social accounts, team members, brand voice,
 * safety policies, ad accounts, and boost rules.
 *
 * @property string $group_id
 * @property string $org_id
 * @property string $name
 * @property string|null $description
 * @property array|null $client_location
 * @property string|null $logo_url
 * @property string $color
 * @property string|null $default_link_shortener
 * @property string $timezone
 * @property string $language
 * @property string|null $brand_voice_id
 * @property string|null $brand_safety_policy_id
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class ProfileGroup extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis.profile_groups';
    protected $primaryKey = 'group_id';

    protected $fillable = [
        'org_id',
        'name',
        'description',
        'client_location',
        'logo_url',
        'color',
        'default_link_shortener',
        'timezone',
        'language',
        'brand_voice_id',
        'brand_safety_policy_id',
        'created_by',
    ];

    protected $casts = [
        'client_location' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user who created this profile group
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Get the brand voice associated with this profile group
     */
    public function brandVoice(): BelongsTo
    {
        return $this->belongsTo(BrandVoice::class, 'brand_voice_id', 'voice_id');
    }

    /**
     * Get the brand safety policy associated with this profile group
     */
    public function brandSafetyPolicy(): BelongsTo
    {
        return $this->belongsTo(BrandSafetyPolicy::class, 'brand_safety_policy_id', 'policy_id');
    }

    /**
     * Get all team members of this profile group
     */
    public function members(): HasMany
    {
        return $this->hasMany(ProfileGroupMember::class, 'profile_group_id', 'group_id');
    }

    /**
     * Get all social integrations (profiles) in this group
     */
    public function socialIntegrations(): HasMany
    {
        return $this->hasMany(\App\Models\Integration::class, 'profile_group_id', 'group_id');
    }

    /**
     * Alias for socialIntegrations - used by PublishingModal
     */
    public function socialProfiles(): HasMany
    {
        return $this->socialIntegrations();
    }

    /**
     * Get all approval workflows for this profile group
     */
    public function approvalWorkflows(): HasMany
    {
        return $this->hasMany(ApprovalWorkflow::class, 'profile_group_id', 'group_id');
    }

    /**
     * Get all ad accounts connected to this profile group
     */
    public function adAccounts(): HasMany
    {
        return $this->hasMany(AdAccount::class, 'profile_group_id', 'group_id');
    }

    /**
     * Get all boost rules for this profile group
     */
    public function boostRules(): HasMany
    {
        return $this->hasMany(BoostRule::class, 'profile_group_id', 'group_id');
    }

    /**
     * Scope to get only active profile groups
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope to filter by timezone
     */
    public function scopeByTimezone($query, string $timezone)
    {
        return $query->where('timezone', $timezone);
    }

    /**
     * Scope to filter by language
     */
    public function scopeByLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Get the client country from location JSONB
     */
    public function getClientCountryAttribute(): ?string
    {
        return $this->client_location['country'] ?? null;
    }

    /**
     * Get the client city from location JSONB
     */
    public function getClientCityAttribute(): ?string
    {
        return $this->client_location['city'] ?? null;
    }

    /**
     * Check if this group has a brand voice configured
     */
    public function hasBrandVoice(): bool
    {
        return !is_null($this->brand_voice_id);
    }

    /**
     * Check if this group has a brand safety policy configured
     */
    public function hasBrandSafetyPolicy(): bool
    {
        return !is_null($this->brand_safety_policy_id);
    }

    /**
     * Get the count of social profiles in this group
     */
    public function getProfilesCountAttribute(): int
    {
        return $this->socialIntegrations()->count();
    }

    /**
     * Get the count of team members in this group
     */
    public function getMembersCountAttribute(): int
    {
        return $this->members()->count();
    }
}
