<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
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

    // ─────────────────────────────────────────────────────────────────
    // HELPER TĨNH — gọi từ nơi khác trong code để tạo thông báo mới
    // Ví dụ: NotificationController::notify($userId, 'exam_reminder', ...)
    // ─────────────────────────────────────────────────────────────────

    public static function notify(int $userId, string $type, string $title, string $body, ?array $data = null): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data,
        ]);
    }

    public static function syncDueEventNotifications(int $userId): int
    {
        $now = now();
        $today = $now->toDateString();
        $windowEnd = $now->copy()->addMinutes(60)->format('H:i');

        $events = Event::where('user_id', $userId)
            ->where('status', 'active')
            ->whereDate('date', $today)
            ->where(function ($query) use ($now, $windowEnd) {
                $query->whereNull('start')
                      ->orWhere('start', '<=', $windowEnd);
            })
            ->get();

        $created = 0;

        foreach ($events as $event) {
            $alreadyNotified = Notification::where('user_id', $userId)
                ->where('type', 'schedule_reminder')
                ->where('data->event_id', $event->id)
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            try {
                $calendarUrl = route('user.calendars');
            } catch (\Exception $e) {
                $calendarUrl = '/user/calendars';
            }

            self::notify(
                $userId,
                'schedule_reminder',
                "Sự kiện \"{$event->title}\" sắp diễn ra",
                "Sự kiện của bạn diễn ra vào {$event->date->format('d/m/Y')}" .
                    ($event->start ? " lúc {$event->start}" : '') ,
                ['event_id' => $event->id, 'data' => $calendarUrl]
            );

            $created++;
        }

        return $created;
    }
}