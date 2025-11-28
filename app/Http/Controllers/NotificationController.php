<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Concerns\ApiResponse;

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

    public function index()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->paginate(20);
        
        return response()->json($notifications);
    }

    public function markAsRead($notificationId)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($notificationId);
        $notification->markAsRead();

        return response()->json(['message' => __('notifications.marked_read')]);
    }

    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return response()->json(['message' => __('notifications.all_marked_read')]);
    }

    public function destroy($notificationId)
    {
        $user = Auth::user();
        $user->notifications()->findOrFail($notificationId)->delete();

        return response()->json(['message' => __('notifications.deleted_success')]);
    }
}
