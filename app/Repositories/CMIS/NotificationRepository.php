<?php

namespace App\Repositories\CMIS;

use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Repository for CMIS Notification Functions
 * Encapsulates PostgreSQL functions and queries related to notifications
 */
class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * Create notification
     * Corresponds to: cmis.create_notification()
     *
     * @param string $userId User UUID
     * @param string $type Notification type
     * @param string $title Notification title
     * @param string $message Notification message
     * @param array|null $data Additional data as array
     * @return string|null Notification UUID
     */
    public function createNotification(
        string $userId,
        string $type,
        string $title,
        string $message,
        ?array $data = null
    ): ?string {
        // Get org_id from session if available
        $orgId = session('current_org_id');

        // Convert data array to JSON
        $jsonData = $data ? json_encode($data) : '{}';

        $result = DB::selectOne(
            'SELECT cmis.create_notification(?, ?, ?, ?, ?, ?::jsonb) as notification_id',
            [$userId, $orgId, $type, $title, $message, $jsonData]
        );

        return $result?->notification_id;
    }

    /**
     * Get user notifications
     *
     * @param string $userId User UUID
     * @param bool $unreadOnly Filter for unread only
     * @param int $limit Maximum number of results
     * @return Collection Collection of notifications
     */
    public function getUserNotifications(
        string $userId,
        bool $unreadOnly = false,
        int $limit = 50
    ): Collection {
        $query = "
            SELECT
                notification_id,
                user_id,
                org_id,
                type,
                title,
                message,
                data,
                read,
                read_at,
                created_at,
                updated_at
            FROM cmis.notifications
            WHERE user_id = ?
        ";

        $params = [$userId];

        if ($unreadOnly) {
            $query .= " AND read = false";
        }

        $query .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;

        $results = DB::select($query, $params);

        return collect($results);
    }

    /**
     * Mark notification as read
     * Corresponds to: cmis.mark_notification_as_read()
     *
     * @param string $notificationId Notification UUID
     * @return bool Success status
     */
    public function markAsRead(string $notificationId): bool
    {
        $result = DB::selectOne(
            'SELECT cmis.mark_notification_as_read(?) as success',
            [$notificationId]
        );

        return $result?->success ?? false;
    }

    /**
     * Mark all notifications as read for user
     *
     * @param string $userId User UUID
     * @return bool Success status
     */
    public function markAllAsRead(string $userId): bool
    {
        try {
            DB::statement(
                "UPDATE cmis.notifications
                SET read = true, read_at = CURRENT_TIMESTAMP
                WHERE user_id = ? AND read = false",
                [$userId]
            );

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to mark all notifications as read', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
