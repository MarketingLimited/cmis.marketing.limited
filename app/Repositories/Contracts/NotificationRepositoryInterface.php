<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface NotificationRepositoryInterface
{
    /**
     * Create notification
     */
    public function createNotification(
        string $userId,
        string $type,
        string $title,
        string $message,
        ?array $data = null
    ): ?string;

    /**
     * Get user notifications
     */
    public function getUserNotifications(
        string $userId,
        bool $unreadOnly = false,
        int $limit = 50
    ): Collection;

    /**
     * Mark as read
     */
    public function markAsRead(string $notificationId): bool;

    /**
     * Mark all as read for user
     */
    public function markAllAsRead(string $userId): bool;
}
