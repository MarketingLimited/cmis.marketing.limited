<?php

namespace App\Models\Notification;

use App\Models\Concerns\HasOrganization;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Notification extends BaseModel
{
    use SoftDeletes, HasUuids;

    protected $table = 'cmis.notifications';
    protected $primaryKey = 'notification_id';
    protected $fillable = [
        'user_id',
        'org_id',
        'type',
        'title',
        'message',
        'action_url',
        'priority',
        'category',
        'related_entity_type',
        'related_entity_id',
        'data',
        'is_read',
        'read_at',
        'expires_at',
    ];

    protected $casts = [
        'notification_id' => 'string',
        'user_id' => 'string',
        'org_id' => 'string',
        'related_entity_id' => 'string',
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    

    /**
     * Scope unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);

    /**
     * Scope by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);

    /**
     * Scope by priority
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');

    /**
     * Scope not expired
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());

    /**
     * Mark as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

    /**
     * Check if expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;

        return $this->expires_at->isPast();
}
