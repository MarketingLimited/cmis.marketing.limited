<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
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

        return response()->json(['message' => 'تم تعليم الإشعار كمقروء']);
    }

    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return response()->json(['message' => 'تم تعليم جميع الإشعارات كمقروءة']);
    }

    public function destroy($notificationId)
    {
        $user = Auth::user();
        $user->notifications()->findOrFail($notificationId)->delete();

        return response()->json(['message' => 'تم حذف الإشعار']);
    }
}
