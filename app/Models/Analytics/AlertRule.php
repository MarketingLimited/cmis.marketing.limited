<?php

namespace App\Models\Analytics;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * Alert Rule Model (Phase 13)
 *
 * Defines real-time monitoring rules for campaigns, ads, and other entities
 *
 * @property string $rule_id
 * @property string $org_id
 * @property string $created_by
 * @property string $name
 * @property string|null $description
 * @property string $entity_type
 * @property string|null $entity_id
 * @property string $metric
 * @property string $condition
 * @property float $threshold
 * @property int $time_window_minutes
 * @property string $severity
 * @property array $notification_channels
 * @property array $notification_config
 * @property int $cooldown_minutes
 * @property bool $is_active
 * @property \Carbon\Carbon|null $last_triggered_at
 * @property int $trigger_count
 */
class AlertRule extends BaseModel
{
    use HasFactory, HasOrganization, SoftDeletes;

    protected $table = 'cmis.alert_rules';
    protected $primaryKey = 'rule_id';

    protected $fillable = [
        'org_id',
        'created_by',
        'name',
        'description',
        'entity_type',
        'entity_id',
        'metric',
        'condition',
        'threshold',
        'time_window_minutes',
        'severity',
        'notification_channels',
        'notification_config',
        'cooldown_minutes',
        'is_active',
    ];

    protected $casts = [
        'threshold' => 'decimal:4',
        'time_window_minutes' => 'integer',
        'cooldown_minutes' => 'integer',
        'is_active' => 'boolean',
        'notification_channels' => 'array',
        'notification_config' => 'array',
        'last_triggered_at' => 'datetime',
        'trigger_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user who created the rule
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Get triggered alerts for this rule
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(AlertHistory::class, 'rule_id', 'rule_id');
    }

    /**
     * Get recent triggered alerts
     */
    public function recentAlerts(): HasMany
    {
        return $this->alerts()
            ->where('triggered_at', '>=', now()->subDays(30))
            ->latest('triggered_at');
    }

    /**
     * Scope: Active rules only
     */
    public function scopeActive($query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By entity type
     */
    public function scopeForEntity($query, string $entityType, ?string $entityId = null): Builder
    {
        $query->where('entity_type', $entityType);

        if ($entityId) {
            $query->where(function ($q) use ($entityId) {
                $q->where('entity_id', $entityId)
                  ->orWhereNull('entity_id');
            });
        }

        return $query;
    }

    /**
     * Scope: By severity
     */
    public function scopeBySeverity($query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope: Due for evaluation (cooldown period expired)
     */
    public function scopeDueForEvaluation($query): Builder
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('last_triggered_at')
                  ->orWhereRaw('last_triggered_at <= NOW() - INTERVAL \'1 minute\' * cooldown_minutes');
            });
    }

    /**
     * Check if rule is in cooldown period
     */
    public function isInCooldown(): bool
    {
        if (!$this->last_triggered_at) {
            return false;
        }

        $cooldownEnd = $this->last_triggered_at->addMinutes($this->cooldown_minutes);
        return now()->lt($cooldownEnd);
    }

    /**
     * Evaluate condition against actual value
     */
    public function evaluateCondition(float $actualValue): bool
    {
        return match ($this->condition) {
            'gt' => $actualValue > $this->threshold,
            'gte' => $actualValue >= $this->threshold,
            'lt' => $actualValue < $this->threshold,
            'lte' => $actualValue <= $this->threshold,
            'eq' => abs($actualValue - $this->threshold) < 0.0001,
            'ne' => abs($actualValue - $this->threshold) >= 0.0001,
            default => false
        };
    }

    /**
     * Get human-readable condition text
     */
    public function getConditionText(): string
    {
        return match ($this->condition) {
            'gt' => 'greater than',
            'gte' => 'greater than or equal to',
            'lt' => 'less than',
            'lte' => 'less than or equal to',
            'eq' => 'equal to',
            'ne' => 'not equal to',
            'change_pct' => 'changes by',
            default => 'unknown'
        };
    }

    /**
     * Mark as triggered
     */
    public function markTriggered(): void
    {
        $this->update([
            'last_triggered_at' => now(),
            'trigger_count' => $this->trigger_count + 1
        ]);
    }
}
