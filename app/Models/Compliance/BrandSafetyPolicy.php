<?php

namespace App\Models\Compliance;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use App\Models\Social\ProfileGroup;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BrandSafetyPolicy Model
 *
 * Represents brand safety and compliance policies for automated content validation.
 * Defines prohibited content, custom rules, content requirements, and enforcement levels.
 *
 * @property string $policy_id
 * @property string $org_id
 * @property string|null $profile_group_id
 * @property string $name
 * @property string $description
 * @property bool $is_active
 * @property bool $prohibit_derogatory_language
 * @property bool $prohibit_profanity
 * @property bool $prohibit_offensive_content
 * @property array $custom_banned_words
 * @property array $custom_banned_phrases
 * @property string|null $custom_requirements
 * @property bool $require_disclosure
 * @property string|null $disclosure_text
 * @property bool $require_fact_checking
 * @property bool $require_source_citation
 * @property array $industry_regulations
 * @property array $compliance_regions
 * @property string $enforcement_level
 * @property bool $auto_reject_violations
 * @property bool $use_default_template
 * @property string|null $template_name
 * @property string $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class BrandSafetyPolicy extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis.brand_safety_policies';
    protected $primaryKey = 'policy_id';

    protected $fillable = [
        'org_id',
        'profile_group_id',
        'name',
        'description',
        'is_active',
        'prohibit_derogatory_language',
        'prohibit_profanity',
        'prohibit_offensive_content',
        'custom_banned_words',
        'custom_banned_phrases',
        'custom_requirements',
        'require_disclosure',
        'disclosure_text',
        'require_fact_checking',
        'require_source_citation',
        'industry_regulations',
        'compliance_regions',
        'enforcement_level',
        'auto_reject_violations',
        'use_default_template',
        'template_name',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'prohibit_derogatory_language' => 'boolean',
        'prohibit_profanity' => 'boolean',
        'prohibit_offensive_content' => 'boolean',
        'custom_banned_words' => 'array',
        'custom_banned_phrases' => 'array',
        'require_disclosure' => 'boolean',
        'require_fact_checking' => 'boolean',
        'require_source_citation' => 'boolean',
        'industry_regulations' => 'array',
        'compliance_regions' => 'array',
        'auto_reject_violations' => 'boolean',
        'use_default_template' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the profile group this policy belongs to
     */
    public function profileGroup(): BelongsTo
    {
        return $this->belongsTo(ProfileGroup::class, 'profile_group_id', 'group_id');
    }

    /**
     * Get the user who created this policy
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Get all profile groups using this policy
     */
    public function profileGroups(): HasMany
    {
        return $this->hasMany(ProfileGroup::class, 'brand_safety_policy_id', 'policy_id');
    }

    /**
     * Scope to get only active policies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get org-wide policies (not tied to specific group)
     */
    public function scopeOrgWide($query)
    {
        return $query->whereNull('profile_group_id');
    }

    /**
     * Scope to get group-specific policies
     */
    public function scopeForGroup($query, string $groupId)
    {
        return $query->where('profile_group_id', $groupId);
    }

    /**
     * Scope to filter by enforcement level
     */
    public function scopeByEnforcementLevel($query, string $level)
    {
        return $query->where('enforcement_level', $level);
    }

    /**
     * Check if this policy is org-wide (not group-specific)
     */
    public function isOrgWide(): bool
    {
        return is_null($this->profile_group_id);
    }

    /**
     * Check if any automated rules are enabled
     */
    public function hasAutomatedRules(): bool
    {
        return $this->prohibit_derogatory_language
            || $this->prohibit_profanity
            || $this->prohibit_offensive_content;
    }

    /**
     * Check if custom banned content is defined
     */
    public function hasCustomBannedContent(): bool
    {
        return !empty($this->custom_banned_words) || !empty($this->custom_banned_phrases);
    }

    /**
     * Check if content requirements are enabled
     */
    public function hasContentRequirements(): bool
    {
        return $this->require_disclosure
            || $this->require_fact_checking
            || $this->require_source_citation;
    }

    /**
     * Check if industry regulations are configured
     */
    public function hasIndustryRegulations(): bool
    {
        return !empty($this->industry_regulations);
    }

    /**
     * Check if compliance regions are configured
     */
    public function hasComplianceRegions(): bool
    {
        return !empty($this->compliance_regions);
    }

    /**
     * Get all banned content (words and phrases combined)
     */
    public function getAllBannedContent(): array
    {
        return array_merge(
            $this->custom_banned_words ?? [],
            $this->custom_banned_phrases ?? []
        );
    }

    /**
     * Validate content against this policy
     *
     * @param string $content
     * @return array ['valid' => bool, 'violations' => array]
     */
    public function validateContent(string $content): array
    {
        $violations = [];

        // Check banned words
        foreach ($this->custom_banned_words ?? [] as $word) {
            if (stripos($content, $word) !== false) {
                $violations[] = "Contains banned word: {$word}";
            }
        }

        // Check banned phrases
        foreach ($this->custom_banned_phrases ?? [] as $phrase) {
            if (stripos($content, $phrase) !== false) {
                $violations[] = "Contains banned phrase: {$phrase}";
            }
        }

        // Check disclosure requirement
        if ($this->require_disclosure && $this->disclosure_text) {
            if (stripos($content, $this->disclosure_text) === false) {
                $violations[] = "Missing required disclosure: {$this->disclosure_text}";
            }
        }

        return [
            'valid' => empty($violations),
            'violations' => $violations,
        ];
    }

    /**
     * Get the count of profile groups using this policy
     */
    public function getUsageCountAttribute(): int
    {
        return $this->profileGroups()->count();
    }
}
