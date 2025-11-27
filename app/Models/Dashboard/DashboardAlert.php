<?php

namespace App\Models\Dashboard;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardAlert extends BaseModel
{
    use HasFactory, SoftDeletes, HasOrganization;

    protected $table = 'cmis_dashboard.dashboard_alerts';
    protected $primaryKey = 'alert_id';

    protected $fillable = [
        'alert_id',
        'widget_id',
        'org_id',
        'name',
        'description',
        'metric',
        'condition',
        'threshold_value',
        'comparison_period',
        'check_frequency',
        'severity',
        'is_active',
        'notification_channels',
        'recipients',
        'message_template',
        'last_checked_at',
        'last_triggered_at',
        'trigger_count',
        'consecutive_triggers',
        'cooldown_period',
        'auto_resolve',
        'resolved_at',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'threshold_value' => 'decimal:2',
        'check_frequency' => 'integer',
        'is_active' => 'boolean',
        'notification_channels' => 'array',
        'recipients' => 'array',
        'last_checked_at' => 'datetime',
        'last_triggered_at' => 'datetime',
        'trigger_count' => 'integer',
        'consecutive_triggers' => 'integer',
        'cooldown_period' => 'integer',
        'auto_resolve' => 'boolean',
        'resolved_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Condition constants
    public const CONDITION_GREATER_THAN = 'greater_than';
    public const CONDITION_LESS_THAN = 'less_than';
    public const CONDITION_EQUALS = 'equals';
    public const CONDITION_NOT_EQUALS = 'not_equals';
    public const CONDITION_BETWEEN = 'between';
    public const CONDITION_INCREASES_BY = 'increases_by';
    public const CONDITION_DECREASES_BY = 'decreases_by';
    public const CONDITION_PERCENTAGE_CHANGE = 'percentage_change';

    // Severity constants
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_CRITICAL = 'critical';

    // Notification channel constants
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_SLACK = 'slack';
    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_WEBHOOK = 'webhook';
    public const CHANNEL_IN_APP = 'in_app';

    // Comparison period constants
    public const PERIOD_CURRENT = 'current';
    public const PERIOD_PREVIOUS_HOUR = 'previous_hour';
    public const PERIOD_PREVIOUS_DAY = 'previous_day';
    public const PERIOD_PREVIOUS_WEEK = 'previous_week';
    public const PERIOD_PREVIOUS_MONTH = 'previous_month';

    // Relationships
    public function widget(): BelongsTo
    {
        return $this->belongsTo(DashboardWidget::class, 'widget_id', 'widget_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeDueForCheck($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('last_checked_at')
                           ->orWhereRaw('last_checked_at + (check_frequency || \' seconds\')::interval <= NOW()');
                     });
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    public function scopeTriggered($query, int $hours = 24)
    {
        return $query->whereNotNull('last_triggered_at')
                     ->where('last_triggered_at', '>=', now()->subHours($hours));
    }

    public function scopeInCooldown($query)
    {
        return $query->whereNotNull('last_triggered_at')
                     ->whereNotNull('cooldown_period')
                     ->whereRaw('last_triggered_at + (cooldown_period || \' seconds\')::interval > NOW()');
    }

    // Helper Methods
    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function isCritical(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    public function isDueForCheck(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if (!$this->last_checked_at) {
            return true;
        }

        $nextCheck = $this->last_checked_at->addSeconds($this->check_frequency);
        return $nextCheck->isPast();
    }

    public function isInCooldown(): bool
    {
        if (!$this->cooldown_period || !$this->last_triggered_at) {
            return false;
        }

        $cooldownEnd = $this->last_triggered_at->addSeconds($this->cooldown_period);
        return $cooldownEnd->isFuture();
    }

    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    public function recordCheck(bool $triggered, ?float $currentValue = null): bool
    {
        $updates = [
            'last_checked_at' => now(),
        ];

        if ($triggered) {
            $updates['last_triggered_at'] = now();
            $updates['trigger_count'] = $this->trigger_count + 1;
            $updates['consecutive_triggers'] = $this->consecutive_triggers + 1;
            $updates['resolved_at'] = null;
        } else {
            $updates['consecutive_triggers'] = 0;

            if ($this->auto_resolve && $this->last_triggered_at) {
                $updates['resolved_at'] = now();
            }
        }

        if ($currentValue !== null) {
            $updates['metadata'] = array_merge($this->metadata ?? [], [
                'last_value' => $currentValue,
                'last_check_time' => now()->toIso8601String(),
            ]);
        }

        return $this->update($updates);
    }

    public function resetTriggers(): bool
    {
        return $this->update([
            'trigger_count' => 0,
            'consecutive_triggers' => 0,
            'last_triggered_at' => null,
            'resolved_at' => null,
        ]);
    }

    public function addRecipient(string $identifier): bool
    {
        $recipients = $this->recipients ?? [];

        if (!in_array($identifier, $recipients)) {
            $recipients[] = $identifier;
            return $this->update(['recipients' => $recipients]);
        }

        return true;
    }

    public function removeRecipient(string $identifier): bool
    {
        $recipients = $this->recipients ?? [];
        $recipients = array_values(array_diff($recipients, [$identifier]));

        return $this->update(['recipients' => $recipients]);
    }

    public function addChannel(string $channel): bool
    {
        $channels = $this->notification_channels ?? [];

        if (!in_array($channel, $channels)) {
            $channels[] = $channel;
            return $this->update(['notification_channels' => $channels]);
        }

        return true;
    }

    public function removeChannel(string $channel): bool
    {
        $channels = $this->notification_channels ?? [];
        $channels = array_values(array_diff($channels, [$channel]));

        return $this->update(['notification_channels' => $channels]);
    }

    public function evaluateCondition(float $currentValue, ?float $previousValue = null): bool
    {
        return match($this->condition) {
            self::CONDITION_GREATER_THAN => $currentValue > $this->threshold_value,
            self::CONDITION_LESS_THAN => $currentValue < $this->threshold_value,
            self::CONDITION_EQUALS => abs($currentValue - $this->threshold_value) < 0.01,
            self::CONDITION_NOT_EQUALS => abs($currentValue - $this->threshold_value) >= 0.01,
            self::CONDITION_INCREASES_BY => $previousValue !== null && ($currentValue - $previousValue) >= $this->threshold_value,
            self::CONDITION_DECREASES_BY => $previousValue !== null && ($previousValue - $currentValue) >= $this->threshold_value,
            self::CONDITION_PERCENTAGE_CHANGE => $previousValue !== null && $this->checkPercentageChange($currentValue, $previousValue),
            default => false,
        };
    }

    protected function checkPercentageChange(float $current, float $previous): bool
    {
        if ($previous == 0) {
            return false;
        }

        $percentChange = (($current - $previous) / abs($previous)) * 100;
        return abs($percentChange) >= $this->threshold_value;
    }

    public function getConditionDescription(): string
    {
        $descriptions = [
            self::CONDITION_GREATER_THAN => "is greater than {$this->threshold_value}",
            self::CONDITION_LESS_THAN => "is less than {$this->threshold_value}",
            self::CONDITION_EQUALS => "equals {$this->threshold_value}",
            self::CONDITION_NOT_EQUALS => "does not equal {$this->threshold_value}",
            self::CONDITION_INCREASES_BY => "increases by {$this->threshold_value}",
            self::CONDITION_DECREASES_BY => "decreases by {$this->threshold_value}",
            self::CONDITION_PERCENTAGE_CHANGE => "changes by {$this->threshold_value}%",
        ];

        return $descriptions[$this->condition] ?? 'unknown condition';
    }

    public function getSeverityColor(): string
    {
        return match($this->severity) {
            self::SEVERITY_INFO => 'blue',
            self::SEVERITY_WARNING => 'yellow',
            self::SEVERITY_ERROR => 'orange',
            self::SEVERITY_CRITICAL => 'red',
            default => 'gray',
        };
    }

    public function getSeverityIcon(): string
    {
        return match($this->severity) {
            self::SEVERITY_INFO => 'info-circle',
            self::SEVERITY_WARNING => 'exclamation-triangle',
            self::SEVERITY_ERROR => 'exclamation-circle',
            self::SEVERITY_CRITICAL => 'times-circle',
            default => 'bell',
        };
    }

    // Static Methods
    public static function getConditionOptions(): array
    {
        return [
            self::CONDITION_GREATER_THAN => 'Greater Than',
            self::CONDITION_LESS_THAN => 'Less Than',
            self::CONDITION_EQUALS => 'Equals',
            self::CONDITION_NOT_EQUALS => 'Not Equals',
            self::CONDITION_BETWEEN => 'Between',
            self::CONDITION_INCREASES_BY => 'Increases By',
            self::CONDITION_DECREASES_BY => 'Decreases By',
            self::CONDITION_PERCENTAGE_CHANGE => 'Percentage Change',
        ];
    }

    public static function getSeverityOptions(): array
    {
        return [
            self::SEVERITY_INFO => 'Info',
            self::SEVERITY_WARNING => 'Warning',
            self::SEVERITY_ERROR => 'Error',
            self::SEVERITY_CRITICAL => 'Critical',
        ];
    }

    public static function getChannelOptions(): array
    {
        return [
            self::CHANNEL_EMAIL => 'Email',
            self::CHANNEL_SLACK => 'Slack',
            self::CHANNEL_SMS => 'SMS',
            self::CHANNEL_WEBHOOK => 'Webhook',
            self::CHANNEL_IN_APP => 'In-App',
        ];
    }

    public static function getPeriodOptions(): array
    {
        return [
            self::PERIOD_CURRENT => 'Current',
            self::PERIOD_PREVIOUS_HOUR => 'Previous Hour',
            self::PERIOD_PREVIOUS_DAY => 'Previous Day',
            self::PERIOD_PREVIOUS_WEEK => 'Previous Week',
            self::PERIOD_PREVIOUS_MONTH => 'Previous Month',
        ];
    }

    // Validation Rules
    public static function createRules(): array
    {
        return [
            'widget_id' => 'required|uuid|exists:cmis_dashboard.dashboard_widgets,widget_id',
            'org_id' => 'required|uuid|exists:cmis.orgs,org_id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'metric' => 'required|string|max:255',
            'condition' => 'required|in:' . implode(',', array_keys(self::getConditionOptions())),
            'threshold_value' => 'required|numeric',
            'comparison_period' => 'nullable|in:' . implode(',', array_keys(self::getPeriodOptions())),
            'check_frequency' => 'required|integer|min:60',
            'severity' => 'required|in:' . implode(',', array_keys(self::getSeverityOptions())),
            'notification_channels' => 'required|array|min:1',
            'notification_channels.*' => 'in:' . implode(',', array_keys(self::getChannelOptions())),
            'recipients' => 'required|array|min:1',
            'message_template' => 'nullable|string',
            'cooldown_period' => 'nullable|integer|min:0',
            'auto_resolve' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }

    public static function updateRules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'threshold_value' => 'sometimes|numeric',
            'check_frequency' => 'sometimes|integer|min:60',
            'notification_channels' => 'sometimes|array|min:1',
            'recipients' => 'sometimes|array|min:1',
            'is_active' => 'sometimes|boolean',
            'metadata' => 'sometimes|array',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'widget_id.required' => 'Widget is required',
            'org_id.required' => 'Organization is required',
            'name.required' => 'Alert name is required',
            'metric.required' => 'Metric is required',
            'condition.required' => 'Condition is required',
            'threshold_value.required' => 'Threshold value is required',
            'check_frequency.required' => 'Check frequency is required',
            'check_frequency.min' => 'Check frequency must be at least 60 seconds',
            'severity.required' => 'Severity is required',
            'notification_channels.required' => 'At least one notification channel is required',
            'recipients.required' => 'At least one recipient is required',
        ];
    }
}
