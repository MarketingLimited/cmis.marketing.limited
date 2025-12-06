<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * Announcement Model
 *
 * Represents platform-wide announcements and broadcasts from super admins.
 */
class Announcement extends BaseModel
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'cmis.announcements';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'announcement_id';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'content',
        'type',
        'priority',
        'target_audience',
        'target_ids',
        'is_dismissible',
        'is_active',
        'starts_at',
        'ends_at',
        'action_text',
        'action_url',
        'icon',
        'color',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'target_ids' => 'array',
        'is_dismissible' => 'boolean',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Announcement types.
     */
    public const TYPES = [
        'info' => 'Information',
        'warning' => 'Warning',
        'critical' => 'Critical',
        'maintenance' => 'Maintenance',
        'feature' => 'New Feature',
    ];

    /**
     * Priority levels.
     */
    public const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    /**
     * Target audiences.
     */
    public const TARGET_AUDIENCES = [
        'all' => 'All Users',
        'admins' => 'Admins Only',
        'specific_plans' => 'Specific Plans',
        'specific_orgs' => 'Specific Organizations',
    ];

    /**
     * Get the user who created the announcement.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Get all dismissals for this announcement.
     */
    public function dismissals(): HasMany
    {
        return $this->hasMany(AnnouncementDismissal::class, 'announcement_id', 'announcement_id');
    }

    /**
     * Get all views for this announcement.
     */
    public function views(): HasMany
    {
        return $this->hasMany(AnnouncementView::class, 'announcement_id', 'announcement_id');
    }

    /**
     * Scope a query to only include active announcements.
     */
    public function scopeActive($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            });
    }

    /**
     * Scope a query to only include scheduled announcements.
     */
    public function scopeScheduled($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '>', now());
    }

    /**
     * Scope a query to only include expired announcements.
     */
    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<', now());
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by priority.
     */
    public function scopeOfPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Check if the announcement is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at > $now) {
            return false;
        }

        if ($this->ends_at && $this->ends_at < $now) {
            return false;
        }

        return true;
    }

    /**
     * Check if the announcement is visible to a specific user.
     */
    public function isVisibleToUser($user, $userOrgId = null, $userPlanId = null): bool
    {
        if (!$this->isCurrentlyActive()) {
            return false;
        }

        switch ($this->target_audience) {
            case 'all':
                return true;

            case 'admins':
                return $user->is_super_admin || ($user->role ?? null) === 'admin';

            case 'specific_orgs':
                if (empty($this->target_ids) || !$userOrgId) {
                    return false;
                }
                return in_array($userOrgId, $this->target_ids);

            case 'specific_plans':
                if (empty($this->target_ids) || !$userPlanId) {
                    return false;
                }
                return in_array($userPlanId, $this->target_ids);

            default:
                return true;
        }
    }

    /**
     * Check if a user has dismissed this announcement.
     */
    public function isDismissedByUser($userId): bool
    {
        return $this->dismissals()->where('user_id', $userId)->exists();
    }

    /**
     * Get the type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Get the priority label.
     */
    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    /**
     * Get the target audience label.
     */
    public function getTargetAudienceLabelAttribute(): string
    {
        return self::TARGET_AUDIENCES[$this->target_audience] ?? $this->target_audience;
    }

    /**
     * Get the default icon for the announcement type.
     */
    public function getDefaultIconAttribute(): string
    {
        if ($this->icon) {
            return $this->icon;
        }

        return match ($this->type) {
            'warning' => 'fas fa-exclamation-triangle',
            'critical' => 'fas fa-exclamation-circle',
            'maintenance' => 'fas fa-tools',
            'feature' => 'fas fa-star',
            default => 'fas fa-info-circle',
        };
    }

    /**
     * Get the default color for the announcement type.
     */
    public function getDefaultColorAttribute(): string
    {
        if ($this->color) {
            return $this->color;
        }

        return match ($this->type) {
            'warning' => 'yellow',
            'critical' => 'red',
            'maintenance' => 'orange',
            'feature' => 'green',
            default => 'blue',
        };
    }

    /**
     * Get view count for this announcement.
     */
    public function getViewCountAttribute(): int
    {
        return $this->views()->count();
    }

    /**
     * Get unique viewer count for this announcement.
     */
    public function getUniqueViewerCountAttribute(): int
    {
        return $this->views()->distinct('user_id')->count('user_id');
    }

    /**
     * Get dismissal count for this announcement.
     */
    public function getDismissalCountAttribute(): int
    {
        return $this->dismissals()->count();
    }
}
