<?php

namespace App\Models\Webhook;

use App\Models\BaseModel;
use App\Models\Concerns\HasOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * WebhookConfiguration Model
 *
 * Allows organizations to configure their own webhook endpoints
 * to receive forwarded events (messages, status changes, etc.)
 *
 * Similar to Meta's webhook subscription model:
 * - Callback URL: Where to send events
 * - Verify Token: For endpoint verification
 * - Secret Key: For HMAC signature verification
 *
 * @property string $id UUID primary key
 * @property string $org_id Organization UUID
 * @property string $name User-friendly webhook name
 * @property string $callback_url Endpoint URL
 * @property string $verify_token Verification token
 * @property string $secret_key HMAC signing secret
 * @property array|null $subscribed_events Event types to forward
 * @property string|null $platform Filter by platform
 * @property bool $is_active Whether webhook is active
 * @property bool $is_verified Whether endpoint is verified
 * @property \Carbon\Carbon|null $verified_at When verified
 * @property \Carbon\Carbon|null $last_triggered_at Last event sent
 * @property int $timeout_seconds Request timeout
 * @property int $max_retries Maximum retry attempts
 * @property string $content_type Content-Type header
 * @property array|null $custom_headers Custom headers
 * @property int $success_count Successful deliveries
 * @property int $failure_count Failed deliveries
 */
class WebhookConfiguration extends BaseModel
{
    use HasOrganization, SoftDeletes;

    protected $table = 'cmis.webhook_configurations';

    protected $fillable = [
        'org_id',
        'name',
        'callback_url',
        'verify_token',
        'secret_key',
        'subscribed_events',
        'platform',
        'is_active',
        'is_verified',
        'verified_at',
        'last_triggered_at',
        'timeout_seconds',
        'max_retries',
        'content_type',
        'custom_headers',
        'success_count',
        'failure_count',
        'last_success_at',
        'last_failure_at',
        'last_error',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'subscribed_events' => 'array',
        'custom_headers' => 'array',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'last_triggered_at' => 'datetime',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
        'timeout_seconds' => 'integer',
        'max_retries' => 'integer',
        'success_count' => 'integer',
        'failure_count' => 'integer',
    ];

    protected $hidden = [
        'secret_key', // Never expose in JSON responses
    ];

    /**
     * Available event types for subscription
     */
    public const EVENT_TYPES = [
        // Message events
        'message.received' => 'New message received',
        'message.sent' => 'Message sent',
        'message.delivered' => 'Message delivered',
        'message.read' => 'Message read',
        'message.failed' => 'Message delivery failed',

        // Status events
        'status.changed' => 'Status changed',

        // Platform events
        'webhook.meta' => 'Meta (Facebook/Instagram) events',
        'webhook.whatsapp' => 'WhatsApp events',
        'webhook.tiktok' => 'TikTok events',
        'webhook.twitter' => 'Twitter/X events',
        'webhook.linkedin' => 'LinkedIn events',
        'webhook.snapchat' => 'Snapchat events',
        'webhook.google' => 'Google Ads events',

        // Lead events
        'lead.created' => 'New lead created',
        'lead.updated' => 'Lead updated',

        // Campaign events
        'campaign.status_changed' => 'Campaign status changed',
        'campaign.budget_alert' => 'Campaign budget alert',

        // Post events
        'post.published' => 'Post published',
        'post.failed' => 'Post publishing failed',
        'post.engagement' => 'Post engagement update',
    ];

    /**
     * Supported platforms for filtering
     */
    public const PLATFORMS = [
        'meta' => 'Meta (Facebook/Instagram)',
        'whatsapp' => 'WhatsApp',
        'tiktok' => 'TikTok',
        'twitter' => 'Twitter/X',
        'linkedin' => 'LinkedIn',
        'snapchat' => 'Snapchat',
        'google' => 'Google Ads',
    ];

    // ===== Relationships =====

    /**
     * Get delivery logs for this webhook configuration
     */
    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(WebhookDeliveryLog::class, 'webhook_config_id');
    }

    // ===== Scopes =====

    /**
     * Get active webhook configurations
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get verified webhook configurations
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    /**
     * Filter by platform
     */
    public function scopeForPlatform(Builder $query, string $platform): Builder
    {
        return $query->where(function ($q) use ($platform) {
            $q->whereNull('platform')
                ->orWhere('platform', $platform);
        });
    }

    /**
     * Get configurations subscribed to an event type
     */
    public function scopeSubscribedTo(Builder $query, string $eventType): Builder
    {
        return $query->where(function ($q) use ($eventType) {
            $q->whereNull('subscribed_events')
                ->orWhereJsonContains('subscribed_events', $eventType);
        });
    }

    // ===== Helper Methods =====

    /**
     * Generate a new verify token
     */
    public static function generateVerifyToken(): string
    {
        return Str::random(32);
    }

    /**
     * Generate a new secret key
     */
    public static function generateSecretKey(): string
    {
        return 'whsec_' . Str::random(40);
    }

    /**
     * Check if subscribed to an event type
     */
    public function isSubscribedTo(string $eventType): bool
    {
        // If no specific events configured, subscribe to all
        if (empty($this->subscribed_events)) {
            return true;
        }

        return in_array($eventType, $this->subscribed_events);
    }

    /**
     * Check if should receive events from a platform
     */
    public function shouldReceiveFromPlatform(?string $platform): bool
    {
        // If no platform filter, receive from all
        if (empty($this->platform)) {
            return true;
        }

        return $this->platform === $platform;
    }

    /**
     * Sign a payload with the secret key
     */
    public function signPayload(string $payload): string
    {
        $timestamp = time();
        $signaturePayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signaturePayload, $this->secret_key);

        return "t={$timestamp},v1={$signature}";
    }

    /**
     * Verify a webhook signature
     */
    public function verifySignature(string $payload, string $signature, int $tolerance = 300): bool
    {
        // Parse signature header
        $parts = [];
        foreach (explode(',', $signature) as $part) {
            [$key, $value] = explode('=', $part, 2);
            $parts[$key] = $value;
        }

        if (!isset($parts['t']) || !isset($parts['v1'])) {
            return false;
        }

        $timestamp = (int) $parts['t'];
        $providedSignature = $parts['v1'];

        // Check timestamp tolerance
        if (abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        // Compute expected signature
        $signaturePayload = "{$timestamp}.{$payload}";
        $expectedSignature = hash_hmac('sha256', $signaturePayload, $this->secret_key);

        return hash_equals($expectedSignature, $providedSignature);
    }

    /**
     * Mark as verified
     */
    public function markVerified(): bool
    {
        return $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Mark as unverified
     */
    public function markUnverified(): bool
    {
        return $this->update([
            'is_verified' => false,
            'verified_at' => null,
        ]);
    }

    /**
     * Record successful delivery
     */
    public function recordSuccess(): void
    {
        $this->increment('success_count');
        $this->update([
            'last_success_at' => now(),
            'last_triggered_at' => now(),
            'last_error' => null,
        ]);
    }

    /**
     * Record failed delivery
     */
    public function recordFailure(string $error): void
    {
        $this->increment('failure_count');
        $this->update([
            'last_failure_at' => now(),
            'last_triggered_at' => now(),
            'last_error' => $error,
        ]);
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        $total = $this->success_count + $this->failure_count;
        if ($total === 0) {
            return 100.0;
        }

        return round(($this->success_count / $total) * 100, 2);
    }

    /**
     * Regenerate verify token
     */
    public function regenerateVerifyToken(): string
    {
        $token = self::generateVerifyToken();
        $this->update([
            'verify_token' => $token,
            'is_verified' => false,
            'verified_at' => null,
        ]);

        return $token;
    }

    /**
     * Regenerate secret key
     */
    public function regenerateSecretKey(): string
    {
        $secret = self::generateSecretKey();
        $this->update(['secret_key' => $secret]);

        return $secret;
    }
}
