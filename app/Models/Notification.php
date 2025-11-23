<?php

namespace App\Models;

use App\Models\Concerns\HasOrganization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class Notification extends BaseModel
{
    use HasFactory, HasUuids;
    use HasOrganization;

    protected $table = 'cmis.notifications';
    protected $primaryKey = 'notification_id';
    protected $fillable = [
        'user_id',
        'org_id',
        'type',
        'title',
        'message',
        'data',
        'read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * Get the user that owns the notification
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');

    }
    /**
     * Get the organization associated with the notification
     */
    public function organization()
    {
        return $this->belongsTo(Core\Org::class, 'org_id', 'org_id');

    }
    /**
     * Scope to get notifications for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);

    }
    /**
     * Scope to get unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('read', false);

    }
    /**
     * Scope to get read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('read', true);

    }
    /**
     * Scope to get recent notifications
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);

    }
    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        if ($this->read) {
            return false;

        return $this->update([
            'read' => true,
            'read_at' => now(),
        ]);

    }
    /**
     * Mark notification as unread
     */
    public function markAsUnread()
    {
        return $this->update([
            'read' => false,
            'read_at' => null,
        ]);

    }
    /**
     * Get formatted time ago
     */
    public function getTimeAttribute()
    {
        return $this->created_at->diffForHumans();

    }
    /**
     * Create a new notification
     */
    public static function createNotification(
        string $userId,
        string $type,
        string $message,
        ?string $orgId = null,
        ?string $title = null,
        ?array $data = null
    ) {
        return static::create([
            'user_id' => $userId,
            'org_id' => $orgId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data ?? [],
        ]);

    }
    /**
     * Notify user about campaign
     */
    public static function notifyCampaign(string $userId, string $message, ?string $orgId = null, ?array $data = null)
    {
        return static::createNotification($userId, 'campaign', $message, $orgId, 'حملة تسويقية', $data);

    }
    /**
     * Notify user about analytics
     */
    public static function notifyAnalytics(string $userId, string $message, ?string $orgId = null, ?array $data = null)
    {
        return static::createNotification($userId, 'analytics', $message, $orgId, 'تحليلات', $data);

    }
    /**
     * Notify user about integration
     */
    public static function notifyIntegration(string $userId, string $message, ?string $orgId = null, ?array $data = null)
    {
        return static::createNotification($userId, 'integration', $message, $orgId, 'تكامل', $data);

    }
    /**
     * Notify user about system event
     */
    public static function notifySystem(string $userId, string $message, ?string $orgId = null, ?array $data = null)
    {
        return static::createNotification($userId, 'system', $message, $orgId, 'نظام', $data);
}
}
}
