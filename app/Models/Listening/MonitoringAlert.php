<?php

namespace App\Models\Listening;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BaseModel;

class MonitoringAlert extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.monitoring_alerts';
    protected $primaryKey = 'alert_id';

    protected $fillable = [
        'org_id',
        'created_by',
        'alert_name',
        'alert_type',
        'description',
        'trigger_conditions',
        'severity',
        'threshold_value',
        'threshold_unit',
        'notification_channels',
        'recipients',
        'notification_frequency',
        'last_notification_at',
        'status',
        'trigger_count',
        'last_triggered_at',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'notification_channels' => 'array',
        'recipients' => 'array',
        'last_notification_at' => 'datetime',
        'last_triggered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status Management
     */

    public function activate(): void
    {
        $this->update(['status' => 'active']);

        }
    public function pause(): void
    {
        $this->update(['status' => 'paused']);

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
     * Trigger Management
     */

    public function trigger(): void
    {
        $this->increment('trigger_count');
        $this->update(['last_triggered_at' => now()]);

        }
    public function canSendNotification(): bool
    {
        if (!$this->isActive()) {
            return false;



            }
    public function markNotificationSent(): void
    {
        $this->update(['last_notification_at' => now()]);

    }
    /**
     * Condition Evaluation
     */

    public function evaluateConditions(array $data): bool
    {
        $conditions = $this->trigger_conditions;

        // Check keyword conditions
        if (isset($conditions['keyword_ids']) && isset($data['keyword_id'])) {
            if (!in_array($data['keyword_id'], $conditions['keyword_ids'])) {
                return false;

        // Check sentiment conditions
        if (isset($conditions['sentiment']) && isset($data['sentiment'])) {
            if (!in_array($data['sentiment'], $conditions['sentiment'])) {
                return false;

        // Check threshold conditions
        if ($this->threshold_value !== null && isset($data[$this->threshold_unit])) {
            if ($data[$this->threshold_unit] < $this->threshold_value) {
                return false;

        // Check platform conditions
        if (isset($conditions['platforms']) && isset($data['platform'])) {
            if (!in_array($data['platform'], $conditions['platforms'])) {
                return false;

        // Check influencer condition
        if (isset($conditions['influencer_only']) && $conditions['influencer_only']) {
            if (!($data['author_followers_count'] > 10000 || $data['author_is_verified'])) {
                return false;

        return true;

    }
    /**
     * Notification Management
     */

    public function addChannel(string $channel): void
    {
        $channels = $this->notification_channels;
        if (!in_array($channel, $channels)) {
            $channels[] = $channel;
            $this->update(['notification_channels' => $channels]);

            }
    public function removeChannel(string $channel): void
    {
        $channels = array_filter($this->notification_channels, fn($c) => $c !== $channel);
        $this->update(['notification_channels' => array_values($channels)]);

        }
    public function hasChannel(string $channel): bool
    {
        return in_array($channel, $this->notification_channels);

        }
    public function addRecipient(string $recipient): void
    {
        $recipients = $this->recipients;
        if (!in_array($recipient, $recipients)) {
            $recipients[] = $recipient;
            $this->update(['recipients' => $recipients]);

            }
    public function removeRecipient(string $recipient): void
    {
        $recipients = array_filter($this->recipients, fn($r) => $r !== $recipient);
        $this->update(['recipients' => array_values($recipients)]);

    }
    /**
     * Severity Helpers
     */

    public function isCritical(): bool
    {
        return $this->severity === 'critical';

        }
    public function isHigh(): bool
    {
        return $this->severity === 'high';

        }
    public function isMedium(): bool
    {
        return $this->severity === 'medium';

        }
    public function isLow(): bool
    {
        return $this->severity === 'low';

        }
    public function getSeverityColor(): string
    {
        return match($this->severity) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'blue',
            default => 'gray'
        };

        }
    public function getSeverityIcon(): string
    {
        return match($this->severity) {
            'critical' => '🚨',
            'high' => '⚠️',
            'medium' => '⚡',
            'low' => 'ℹ️',
            default => '📌'
        };

    }
    /**
     * Scopes
     */

    public function scopeActive($query): Builder
    {
        return $query->where('status', 'active');

        }
    public function scopeOfType($query, string $type): Builder
    {
        return $query->where('alert_type', $type);

        }
    public function scopeBySeverity($query, string $severity): Builder
    {
        return $query->where('severity', $severity);

        }
    public function scopeCritical($query): Builder
    {
        return $query->where('severity', 'critical');

        }
    public function scopeRecentlyTriggered($query, int $hours = 24): Builder
    {
        return $query->where('last_triggered_at', '>=', now()->subHours($hours));
}
}
}
}
}
}
}
}
}
}
}
}
}
}
}
}
}
}
}
}
}
}
}
