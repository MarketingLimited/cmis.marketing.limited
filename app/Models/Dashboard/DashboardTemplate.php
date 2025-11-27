<?php

namespace App\Models\Dashboard;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DashboardTemplate extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis_dashboard.dashboard_templates';
    protected $primaryKey = 'template_id';

    protected $fillable = [
        'template_id',
        'org_id',
        'name',
        'description',
        'layout_config',
        'widgets',
        'filters',
        'refresh_interval',
        'is_public',
        'is_default',
        'category',
        'tags',
        'permissions',
        'created_by',
        'shared_with',
        'last_viewed_at',
        'view_count',
        'favorite_count',
        'metadata',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'widgets' => 'array',
        'filters' => 'array',
        'refresh_interval' => 'integer',
        'is_public' => 'boolean',
        'is_default' => 'boolean',
        'tags' => 'array',
        'permissions' => 'array',
        'shared_with' => 'array',
        'last_viewed_at' => 'datetime',
        'view_count' => 'integer',
        'favorite_count' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Category constants
    public const CATEGORY_PERFORMANCE = 'performance';
    public const CATEGORY_FINANCIAL = 'financial';
    public const CATEGORY_MARKETING = 'marketing';
    public const CATEGORY_ANALYTICS = 'analytics';
    public const CATEGORY_OPERATIONS = 'operations';
    public const CATEGORY_CUSTOM = 'custom';

    // Relationships
    public function dashboardWidgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class, 'template_id', 'template_id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(DashboardSnapshot::class, 'template_id', 'template_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ReportSchedule::class, 'template_id', 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSharedWith($query, string $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('is_public', true)
              ->orWhere('created_by', $userId)
              ->orWhereJsonContains('shared_with', $userId);
        });
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopePopular($query, int $minViews = 10)
    {
        return $query->where('view_count', '>=', $minViews)
                     ->orderBy('view_count', 'desc');
    }

    // Helper Methods
    public function isPublic(): bool
    {
        return $this->is_public === true;
    }

    public function isDefault(): bool
    {
        return $this->is_default === true;
    }

    public function isSharedWith(string $userId): bool
    {
        if ($this->isPublic()) {
            return true;
        }

        if ($this->created_by === $userId) {
            return true;
        }

        return in_array($userId, $this->shared_with ?? []);
    }

    public function shareWith(array $userIds): bool
    {
        $currentShares = $this->shared_with ?? [];
        $newShares = array_unique(array_merge($currentShares, $userIds));

        return $this->update(['shared_with' => $newShares]);
    }

    public function unshareWith(array $userIds): bool
    {
        $currentShares = $this->shared_with ?? [];
        $newShares = array_diff($currentShares, $userIds);

        return $this->update(['shared_with' => array_values($newShares)]);
    }

    public function makePublic(): bool
    {
        return $this->update(['is_public' => true]);
    }

    public function makePrivate(): bool
    {
        return $this->update(['is_public' => false]);
    }

    public function makeDefault(): bool
    {
        // Remove default from other templates in same org/category
        static::where('org_id', $this->org_id)
              ->where('category', $this->category)
              ->where('template_id', '!=', $this->template_id)
              ->update(['is_default' => false]);

        return $this->update(['is_default' => true]);
    }

    public function incrementViewCount(): bool
    {
        return $this->increment('view_count', 1, [
            'last_viewed_at' => now()
        ]);
    }

    public function incrementFavoriteCount(): bool
    {
        return $this->increment('favorite_count');
    }

    public function decrementFavoriteCount(): bool
    {
        return $this->decrement('favorite_count');
    }

    public function getWidgetCount(): int
    {
        return is_array($this->widgets) ? count($this->widgets) : 0;
    }

    public function hasFilters(): bool
    {
        return !empty($this->filters);
    }

    public function getCategoryColor(): string
    {
        return match($this->category) {
            self::CATEGORY_PERFORMANCE => 'blue',
            self::CATEGORY_FINANCIAL => 'green',
            self::CATEGORY_MARKETING => 'purple',
            self::CATEGORY_ANALYTICS => 'orange',
            self::CATEGORY_OPERATIONS => 'teal',
            self::CATEGORY_CUSTOM => 'gray',
            default => 'gray',
        };
    }

    // Static Methods
    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_PERFORMANCE => 'Performance',
            self::CATEGORY_FINANCIAL => 'Financial',
            self::CATEGORY_MARKETING => 'Marketing',
            self::CATEGORY_ANALYTICS => 'Analytics',
            self::CATEGORY_OPERATIONS => 'Operations',
            self::CATEGORY_CUSTOM => 'Custom',
        ];
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'layout_config' => 'nullable|array',
            'widgets' => 'nullable|array',
            'filters' => 'nullable|array',
            'refresh_interval' => 'nullable|integer|min:5|max:3600',
            'is_public' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'category' => 'required|in:' . implode(',', array_keys(self::getCategoryOptions())),
            'tags' => 'nullable|array',
            'permissions' => 'nullable|array',
            'shared_with' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'layout_config' => 'sometimes|array',
            'widgets' => 'sometimes|array',
            'filters' => 'sometimes|array',
            'refresh_interval' => 'sometimes|integer|min:5|max:3600',
            'is_public' => 'sometimes|boolean',
            'tags' => 'sometimes|array',
            'shared_with' => 'sometimes|array',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'org_id.required' => 'Organization is required',
            'name.required' => 'Dashboard name is required',
            'category.required' => 'Category is required',
            'refresh_interval.min' => 'Refresh interval must be at least 5 seconds',
            'refresh_interval.max' => 'Refresh interval cannot exceed 1 hour',
        ];
    }
}
