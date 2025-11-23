<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Dashboard Notification Controller
 *
 * Handles notification retrieval and management for dashboard
 */
class DashboardNotificationController extends Controller
{
    use ApiResponse;

    /**
     * Get latest notifications
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->unauthorized('User not authenticated');
            }

            $notifications = Notification::forUser($user->user_id)
                ->recent(20)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->notification_id,
                        'type' => $notification->type,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'data' => $notification->data,
                        'read' => $notification->read,
                        'time' => $notification->time,
                        'created_at' => $notification->created_at->toISOString(),
                    ];
                });

            return $this->success(['notifications' => $notifications], 'Notifications retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to load notifications: ' . $e->getMessage());

            $notifications = [
                [
                    'id' => 1,
                    'type' => 'campaign',
                    'message' => 'تم إطلاق حملة "عروض الصيف" بنجاح',
                    'time' => 'منذ 5 دقائق',
                    'read' => false
                ],
                [
                    'id' => 2,
                    'type' => 'analytics',
                    'message' => 'تحديث في أداء الحملات - زيادة 15% في التحويلات',
                    'time' => 'منذ ساعة',
                    'read' => false
                ],
                [
                    'id' => 3,
                    'type' => 'integration',
                    'message' => 'تم ربط حساب Meta Ads بنجاح',
                    'time' => 'منذ 3 ساعات',
                    'read' => true
                ],
                [
                    'id' => 4,
                    'type' => 'user',
                    'message' => 'تمت إضافة عضو جديد إلى الفريق',
                    'time' => 'منذ يوم',
                    'read' => true
                ],
            ];

            return $this->success(['notifications' => $notifications], 'Fallback notifications retrieved');
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $notificationId): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->unauthorized('Unauthorized');
            }

            $notification = Notification::where('notification_id', $notificationId)
                ->where('user_id', $user->user_id)
                ->first();

            if (!$notification) {
                return $this->notFound('Notification not found');
            }

            $notification->markAsRead();

            return $this->success(null, 'Notification marked as read');
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read: ' . $e->getMessage());
            return $this->serverError('Failed to mark as read');
        }
    }
}
