<?php

namespace App\Models\Analytics;

use App\Models\Core\Org;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alert Notification Model (Phase 13)
 *
 * Tracks delivery of alert notifications across channels
 *
 * @property string $notification_id
 * @property string $alert_id
 * @property string $org_id
 * @property string $channel
 * @property string $recipient
 * @property \Carbon\Carbon $sent_at
 * @property string $status
 * @property string|null $error_message
 * @property int $retry_count
 * @property \Carbon\Carbon|null $delivered_at
 * @property \Carbon\Carbon|null $read_at
 * @property array|null $metadata
 */
class AlertNotification extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cmis.alert_notifications';
    protected $primaryKey = 'notification_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'alert_id',
        'org_id',
        'channel',
        'recipient',
        'sent_at',
        'status',
        'error_message',
        'retry_count',
        'delivered_at',
        'read_at',
        'metadata'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'retry_count' => 'integer',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the alert
     */
    public function alert(): BelongsTo
    {
        return $this->belongsTo(AlertHistory::class, 'alert_id', 'alert_id');
    }

    /**
     * Get the organization
     */
    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class, 'org_id', 'org_id');
    }

    /**
     * Scope: By channel
     */
    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope: Pending notifications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Failed notifications
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Successfully delivered
     */
    public function scopeDelivered($query)
    {
        return $query->whereIn('status', ['delivered', 'read']);
    }

    /**
     * Mark as sent
     */
    public function markSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);
    }

    /**
     * Mark as delivered
     */
    public function markDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now()
        ]);
    }

    /**
     * Mark as read
     */
    public function markRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now()
        ]);
    }

    /**
     * Mark as failed
     */
    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1
        ]);
    }

    /**
     * Check if notification can be retried
     */
    public function canRetry(int $maxRetries = 3): bool
    {
        return $this->status === 'failed' &&
               $this->retry_count < $maxRetries;
    }
}
