<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    /** GET /api/notifications */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::forUser(auth()->id())
            ->latest()
            ->limit(50)
            ->get();

        return response()->json([
            'success'       => true,
            'notifications' => $notifications,
        ]);
    }

    /** GET /api/notifications/unread-count */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::forUser(auth()->id())->unread()->count();

        return response()->json([
            'success' => true,
            'count'   => $count,
        ]);
    }

    /** POST /api/notifications/{id}/read */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $notif = Notification::forUser(auth()->id())->find($id);

        if (!$notif) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy thông báo'], 404);
        }

        $notif->markAsRead();

        return response()->json(['success' => true]);
    }

    /** POST /api/notifications/read-all */
    public function markAllRead(Request $request): JsonResponse
    {
        try {
            $affected = Notification::forUser(auth()->id())
                ->unread()
                ->update(['read_at' => now()]);

            return response()->json(['success' => true, 'updated' => $affected]);
        } catch (\Exception $e) {
            Log::error('markAllRead error: ' . $e->getMessage(), ['user_id' => auth()->id()]);
            return response()->json(['success' => false, 'message' => 'Không thể cập nhật'], 500);
        }
    }

    /** GET /notifications — trang xem tất cả thông báo (full page) */
    public function pageIndex(Request $request)
    {
        $notifications = Notification::forUser(auth()->id())
            ->latest()
            ->paginate(20);

        return view('layouts.notification', compact('notifications'));
    }
}