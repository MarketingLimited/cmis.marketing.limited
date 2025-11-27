<?php

namespace App\Models\FeatureManagement;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FeatureFlagOverride extends BaseModel
{
    use HasFactory, HasOrganization;

    protected $table = 'cmis_features.feature_flag_overrides';
    protected $primaryKey = 'override_id';

    protected $fillable = [
        'override_id',
        'flag_id',
        'org_id',
        'override_type',
        'override_id_value',
        'value',
        'variant_id',
        'reason',
        'is_active',
        'expires_at',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'value' => 'boolean',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Override type constants
    public const TYPE_USER = 'user';
    public const TYPE_ORGANIZATION = 'organization';
    public const TYPE_ROLE = 'role';
    public const TYPE_SEGMENT = 'segment';
    public const TYPE_CUSTOM = 'custom';

    // Relationships
    public function flag(): BelongsTo
    {
        return $this->belongsTo(FeatureFlag::class, 'flag_id', 'flag_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(FeatureFlagVariant::class, 'variant_id', 'variant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function target(): MorphTo
    {
        return $this->morphTo('override', 'override_type', 'override_id_value');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('override_type', $type);
    }

    public function scopeForTarget($query, string $type, string $id)
    {
        return $query->where('override_type', $type)
                     ->where('override_id_value', $id);
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->is_active === true && !$this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    public function extend(int $days): bool
    {
        $newExpiration = ($this->expires_at ?? now())->addDays($days);

        return $this->update(['expires_at' => $newExpiration]);
    }

    public function removeExpiration(): bool
    {
        return $this->update(['expires_at' => null]);
    }

    public function setExpiration(\DateTime $expiresAt): bool
    {
        return $this->update(['expires_at' => $expiresAt]);
    }

    public function updateValue(bool $value, ?string $reason = null): bool
    {
        $updates = ['value' => $value];

        if ($reason !== null) {
            $updates['reason'] = $reason;
        }

        return $this->update($updates);
    }

    public function assignVariant(string $variantId, ?string $reason = null): bool
    {
        $updates = ['variant_id' => $variantId];

        if ($reason !== null) {
            $updates['reason'] = $reason;
        }

        return $this->update($updates);
    }

    public function getTargetDescription(): string
    {
        return match($this->override_type) {
            self::TYPE_USER => "User: {$this->override_id_value}",
            self::TYPE_ORGANIZATION => "Organization: {$this->override_id_value}",
            self::TYPE_ROLE => "Role: {$this->override_id_value}",
            self::TYPE_SEGMENT => "Segment: {$this->override_id_value}",
            self::TYPE_CUSTOM => "Custom: {$this->override_id_value}",
            default => "Unknown: {$this->override_id_value}",
        };
    }

    public function getStatusColor(): string
    {
        if (!$this->is_active) {
            return 'gray';
        }

        if ($this->isExpired()) {
            return 'red';
        }

        return $this->value ? 'green' : 'orange';
    }

    public function getStatusText(): string
    {
        if (!$this->is_active) {
            return 'Inactive';
        }

        if ($this->isExpired()) {
            return 'Expired';
        }

        if ($this->expires_at) {
            $daysUntilExpiry = now()->diffInDays($this->expires_at);
            return "Active (expires in {$daysUntilExpiry} days)";
        }

        return 'Active';
    }

    public function getTypeIcon(): string
    {
        return match($this->override_type) {
            self::TYPE_USER => 'user',
            self::TYPE_ORGANIZATION => 'building',
            self::TYPE_ROLE => 'user-tag',
            self::TYPE_SEGMENT => 'users',
            self::TYPE_CUSTOM => 'cog',
            default => 'flag',
        };
    }

    // Static Methods
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_USER => 'User',
            self::TYPE_ORGANIZATION => 'Organization',
            self::TYPE_ROLE => 'Role',
            self::TYPE_SEGMENT => 'Segment',
            self::TYPE_CUSTOM => 'Custom',
        ];
    }

    public static function createForUser(
        string $flagId,
        string $orgId,
        string $userId,
        bool $value,
        ?string $reason = null,
        ?\DateTime $expiresAt = null
    ): self {
        return static::create([
            'flag_id' => $flagId,
            'org_id' => $orgId,
            'override_type' => self::TYPE_USER,
            'override_id_value' => $userId,
            'value' => $value,
            'reason' => $reason,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);
    }

    public static function createForOrganization(
        string $flagId,
        string $orgId,
        string $targetOrgId,
        bool $value,
        ?string $reason = null,
        ?\DateTime $expiresAt = null
    ): self {
        return static::create([
            'flag_id' => $flagId,
            'org_id' => $orgId,
            'override_type' => self::TYPE_ORGANIZATION,
            'override_id_value' => $targetOrgId,
            'value' => $value,
            'reason' => $reason,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);
    }

    public static function cleanupExpired(): int
    {
        return static::expired()
                     ->update(['is_active' => false]);
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'flag_id' => 'required|uuid|exists:cmis_features.feature_flags,flag_id',
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'override_type' => 'required|in:' . implode(',', array_keys(self::getTypeOptions())),
            'override_id_value' => 'required|string|max:255',
            'value' => 'required|boolean',
            'variant_id' => 'nullable|uuid|exists:cmis_features.feature_flag_variants,variant_id',
            'reason' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'expires_at' => 'nullable|date|after:now',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'value' => 'sometimes|boolean',
            'variant_id' => 'sometimes|uuid|exists:cmis_features.feature_flag_variants,variant_id',
            'reason' => 'sometimes|string|max:500',
            'is_active' => 'sometimes|boolean',
            'expires_at' => 'sometimes|date|after:now',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'flag_id.required' => 'Feature flag is required',
            'org_id.required' => 'Organization is required',
            'override_type.required' => 'Override type is required',
            'override_id_value.required' => 'Override target is required',
            'value.required' => 'Override value is required',
            'expires_at.after' => 'Expiration date must be in the future',
        ];
    }
}
