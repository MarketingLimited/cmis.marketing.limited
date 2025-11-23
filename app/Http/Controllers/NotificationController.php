<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Concerns\ApiResponse;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * Constructor - Apply authentication middleware
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(): JsonResponse
    {
        $user = Auth::user();
        $notifications = $user->notifications()->paginate(20);
        
        return $this->success($notifications, 'Retrieved successfully');
    }

    public function markAsRead($notificationId): JsonResponse
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($notificationId);
        $notification->markAsRead();

        return $this->success(['message' => 'تم تعليم الإشعار كمقروء'], 'Operation completed successfully');
    }

    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return $this->success(['message' => 'تم تعليم جميع الإشعارات كمقروءة'], 'Operation completed successfully');
    }

    public function destroy($notificationId): JsonResponse
    {
        $user = Auth::user();
        $user->notifications()->findOrFail($notificationId)->delete();

        return $this->success(['message' => 'تم حذف الإشعار'], 'Operation completed successfully');
    }
}
