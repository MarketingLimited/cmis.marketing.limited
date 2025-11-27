<?php

namespace App\Models\FeatureManagement;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureFlag extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis_features.feature_flags';
    protected $primaryKey = 'flag_id';

    protected $fillable = [
        'flag_id',
        'org_id',
        'key',
        'name',
        'description',
        'flag_type',
        'status',
        'default_value',
        'targeting_rules',
        'rollout_percentage',
        'user_whitelist',
        'user_blacklist',
        'org_whitelist',
        'org_blacklist',
        'start_date',
        'end_date',
        'tags',
        'category',
        'priority',
        'evaluation_count',
        'last_evaluated_at',
        'enabled_count',
        'disabled_count',
        'dependencies',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'default_value' => 'boolean',
        'targeting_rules' => 'array',
        'rollout_percentage' => 'integer',
        'user_whitelist' => 'array',
        'user_blacklist' => 'array',
        'org_whitelist' => 'array',
        'org_blacklist' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'tags' => 'array',
        'priority' => 'integer',
        'evaluation_count' => 'integer',
        'last_evaluated_at' => 'datetime',
        'enabled_count' => 'integer',
        'disabled_count' => 'integer',
        'dependencies' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Flag type constants
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_VARIANT = 'variant';
    public const TYPE_EXPERIMENT = 'experiment';
    public const TYPE_ROLLOUT = 'rollout';
    public const TYPE_KILLSWITCH = 'killswitch';

    // Status constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_DEPRECATED = 'deprecated';

    // Category constants
    public const CATEGORY_FEATURE = 'feature';
    public const CATEGORY_EXPERIMENT = 'experiment';
    public const CATEGORY_OPERATIONAL = 'operational';
    public const CATEGORY_PERMISSION = 'permission';
    public const CATEGORY_RELEASE = 'release';

    // Relationships
    public function variants(): HasMany
    {
        return $this->hasMany(FeatureFlagVariant::class, 'flag_id', 'flag_id');
    }

    public function overrides(): HasMany
    {
        return $this->hasMany(FeatureFlagOverride::class, 'flag_id', 'flag_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('flag_type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('start_date')
                     ->orWhereNotNull('end_date');
    }

    public function scopeCurrentlyActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                     ->where(function ($q) {
                         $q->whereNull('start_date')
                           ->orWhere('start_date', '<=', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', now());
                     });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('end_date')
                     ->where('end_date', '<', now());
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->isWithinSchedule();
    }

    public function isWithinSchedule(): bool
    {
        $now = now();

        if ($this->start_date && $this->start_date->isFuture()) {
            return false;
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->end_date !== null && $this->end_date->isPast();
    }

    public function hasVariants(): bool
    {
        return $this->flag_type === self::TYPE_VARIANT
            || $this->flag_type === self::TYPE_EXPERIMENT;
    }

    public function activate(): bool
    {
        return $this->update(['status' => self::STATUS_ACTIVE]);
    }

    public function deactivate(): bool
    {
        return $this->update(['status' => self::STATUS_INACTIVE]);
    }

    public function archive(): bool
    {
        return $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    public function evaluate(?string $userId = null, ?string $orgId = null, array $context = []): bool
    {
        // Record evaluation
        $this->increment('evaluation_count', 1, [
            'last_evaluated_at' => now()
        ]);

        // Check if flag is active
        if (!$this->isActive()) {
            return $this->default_value ?? false;
        }

        // Check for override
        if ($override = $this->getOverride($userId, $orgId)) {
            return $override->value;
        }

        // Check blacklists first
        if ($this->isBlacklisted($userId, $orgId)) {
            $this->increment('disabled_count');
            return false;
        }

        // Check whitelists
        if ($this->isWhitelisted($userId, $orgId)) {
            $this->increment('enabled_count');
            return true;
        }

        // Evaluate targeting rules
        if ($this->evaluateTargetingRules($userId, $orgId, $context)) {
            $this->increment('enabled_count');
            return true;
        }

        // Check rollout percentage
        if ($this->checkRollout($userId ?? $orgId)) {
            $this->increment('enabled_count');
            return true;
        }

        // Default value
        $result = $this->default_value ?? false;
        $result ? $this->increment('enabled_count') : $this->increment('disabled_count');

        return $result;
    }

    protected function getOverride(?string $userId, ?string $orgId): ?FeatureFlagOverride
    {
        if ($userId) {
            $override = $this->overrides()
                ->where('override_type', 'user')
                ->where('override_id', $userId)
                ->where('is_active', true)
                ->first();

            if ($override) {
                return $override;
            }
        }

        if ($orgId) {
            return $this->overrides()
                ->where('override_type', 'organization')
                ->where('override_id', $orgId)
                ->where('is_active', true)
                ->first();
        }

        return null;
    }

    protected function isBlacklisted(?string $userId, ?string $orgId): bool
    {
        if ($userId && in_array($userId, $this->user_blacklist ?? [])) {
            return true;
        }

        if ($orgId && in_array($orgId, $this->org_blacklist ?? [])) {
            return true;
        }

        return false;
    }

    protected function isWhitelisted(?string $userId, ?string $orgId): bool
    {
        if ($userId && in_array($userId, $this->user_whitelist ?? [])) {
            return true;
        }

        if ($orgId && in_array($orgId, $this->org_whitelist ?? [])) {
            return true;
        }

        return false;
    }

    protected function evaluateTargetingRules(?string $userId, ?string $orgId, array $context): bool
    {
        if (empty($this->targeting_rules)) {
            return false;
        }

        foreach ($this->targeting_rules as $rule) {
            if ($this->evaluateRule($rule, $userId, $orgId, $context)) {
                return true;
            }
        }

        return false;
    }

    protected function evaluateRule(array $rule, ?string $userId, ?string $orgId, array $context): bool
    {
        $attribute = $rule['attribute'] ?? null;
        $operator = $rule['operator'] ?? 'equals';
        $value = $rule['value'] ?? null;

        if (!$attribute || $value === null) {
            return false;
        }

        $contextValue = $context[$attribute] ?? null;

        return match($operator) {
            'equals' => $contextValue == $value,
            'not_equals' => $contextValue != $value,
            'contains' => is_string($contextValue) && str_contains($contextValue, $value),
            'starts_with' => is_string($contextValue) && str_starts_with($contextValue, $value),
            'ends_with' => is_string($contextValue) && str_ends_with($contextValue, $value),
            'in' => is_array($value) && in_array($contextValue, $value),
            'not_in' => is_array($value) && !in_array($contextValue, $value),
            'greater_than' => is_numeric($contextValue) && $contextValue > $value,
            'less_than' => is_numeric($contextValue) && $contextValue < $value,
            default => false,
        };
    }

    protected function checkRollout(?string $identifier): bool
    {
        if (!$this->rollout_percentage || $this->rollout_percentage >= 100) {
            return $this->rollout_percentage >= 100;
        }

        if ($this->rollout_percentage <= 0) {
            return false;
        }

        // Consistent hashing for stable rollout
        $hash = crc32($this->key . ($identifier ?? ''));
        $bucket = $hash % 100;

        return $bucket < $this->rollout_percentage;
    }

    public function setRolloutPercentage(int $percentage): bool
    {
        return $this->update(['rollout_percentage' => max(0, min(100, $percentage))]);
    }

    public function addToWhitelist(string $type, string $id): bool
    {
        $field = $type === 'user' ? 'user_whitelist' : 'org_whitelist';
        $whitelist = $this->$field ?? [];

        if (!in_array($id, $whitelist)) {
            $whitelist[] = $id;
            return $this->update([$field => $whitelist]);
        }

        return true;
    }

    public function removeFromWhitelist(string $type, string $id): bool
    {
        $field = $type === 'user' ? 'user_whitelist' : 'org_whitelist';
        $whitelist = $this->$field ?? [];
        $whitelist = array_values(array_diff($whitelist, [$id]));

        return $this->update([$field => $whitelist]);
    }

    public function addToBlacklist(string $type, string $id): bool
    {
        $field = $type === 'user' ? 'user_blacklist' : 'org_blacklist';
        $blacklist = $this->$field ?? [];

        if (!in_array($id, $blacklist)) {
            $blacklist[] = $id;
            return $this->update([$field => $blacklist]);
        }

        return true;
    }

    public function removeFromBlacklist(string $type, string $id): bool
    {
        $field = $type === 'user' ? 'user_blacklist' : 'org_blacklist';
        $blacklist = $this->$field ?? [];
        $blacklist = array_values(array_diff($blacklist, [$id]));

        return $this->update([$field => $blacklist]);
    }

    public function getEnabledPercentage(): float
    {
        $total = $this->enabled_count + $this->disabled_count;

        if ($total === 0) {
            return 0;
        }

        return round(($this->enabled_count / $total) * 100, 2);
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'green',
            self::STATUS_INACTIVE => 'gray',
            self::STATUS_ARCHIVED => 'yellow',
            self::STATUS_DEPRECATED => 'red',
            default => 'gray',
        };
    }

    public function getCategoryColor(): string
    {
        return match($this->category) {
            self::CATEGORY_FEATURE => 'blue',
            self::CATEGORY_EXPERIMENT => 'purple',
            self::CATEGORY_OPERATIONAL => 'orange',
            self::CATEGORY_PERMISSION => 'red',
            self::CATEGORY_RELEASE => 'green',
            default => 'gray',
        };
    }

    // Static Methods
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_BOOLEAN => 'Boolean',
            self::TYPE_VARIANT => 'Variant',
            self::TYPE_EXPERIMENT => 'Experiment',
            self::TYPE_ROLLOUT => 'Rollout',
            self::TYPE_KILLSWITCH => 'Kill Switch',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_ARCHIVED => 'Archived',
            self::STATUS_DEPRECATED => 'Deprecated',
        ];
    }

    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_FEATURE => 'Feature',
            self::CATEGORY_EXPERIMENT => 'Experiment',
            self::CATEGORY_OPERATIONAL => 'Operational',
            self::CATEGORY_PERMISSION => 'Permission',
            self::CATEGORY_RELEASE => 'Release',
        ];
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'key' => 'required|string|max:255|unique:cmis_features.feature_flags,key',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'flag_type' => 'required|in:' . implode(',', array_keys(self::getTypeOptions())),
            'status' => 'nullable|in:' . implode(',', array_keys(self::getStatusOptions())),
            'default_value' => 'nullable|boolean',
            'targeting_rules' => 'nullable|array',
            'rollout_percentage' => 'nullable|integer|min:0|max:100',
            'user_whitelist' => 'nullable|array',
            'user_blacklist' => 'nullable|array',
            'org_whitelist' => 'nullable|array',
            'org_blacklist' => 'nullable|array',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'tags' => 'nullable|array',
            'category' => 'required|in:' . implode(',', array_keys(self::getCategoryOptions())),
            'priority' => 'nullable|integer|min:0',
            'dependencies' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:' . implode(',', array_keys(self::getStatusOptions())),
            'default_value' => 'sometimes|boolean',
            'targeting_rules' => 'sometimes|array',
            'rollout_percentage' => 'sometimes|integer|min:0|max:100',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'org_id.required' => 'Organization is required',
            'key.required' => 'Feature flag key is required',
            'key.unique' => 'This feature flag key already exists',
            'name.required' => 'Feature flag name is required',
            'flag_type.required' => 'Flag type is required',
            'category.required' => 'Category is required',
            'rollout_percentage.min' => 'Rollout percentage must be between 0 and 100',
            'rollout_percentage.max' => 'Rollout percentage must be between 0 and 100',
            'end_date.after' => 'End date must be after start date',
        ];
    }
}
