<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * NotificationService — nơi TẬP TRUNG duy nhất để tạo thông báo.
 *
 * Nguyên tắc khi thêm 1 loại thông báo mới:
 *  1. Thêm hằng số TYPE_... trong App\Models\Notification.
 *  2. Thêm 1 method mới ở đây (ví dụ examReminder(), planExpiring()...),
 *     tự soạn title/body tiếng Việt + data cần thiết, rồi gọi create().
 *  3. Gọi method đó từ Command / Observer / Listener / Controller tương ứng.
 *
 * Không tạo Notification::create() trực tiếp ở nơi khác trong code —
 * luôn đi qua Service này để dễ kiểm soát và tránh trùng lặp logic.
 */
class NotificationService
{
    /**
     * Hàm tạo thông báo gốc — các method bên dưới đều gọi qua đây.
     */
    protected function create(int $userId, string $type, string $title, string $body, ?array $data = null): Notification
    {
        $notif = Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data,
        ]);

        Log::info("NotificationService: Đã tạo thông báo '{$type}' cho User ID {$userId}", [
            'notification_id' => $notif->id,
        ]);

        return $notif;
    }

    /**
     * Kiểm tra đã tạo thông báo cho 1 "khóa" cụ thể chưa (chống trùng lặp),
     * ví dụ cùng 1 event_id, cùng 1 subscription_id...
     */
    protected function alreadyNotified(int $userId, string $type, string $dataKey, mixed $dataValue): bool
    {
        return Notification::forUser($userId)
            ->ofType($type)
            ->where("data->{$dataKey}", $dataValue)
            ->exists();
    }

    // ══════════════════════════════════════════════════════════════
    // Ví dụ khung sẵn cho các loại thông báo tương lai —
    // xoá phần này khi thêm loại thật, giữ lại làm mẫu tham khảo:
    //
    // public function documentApproved(User $user, Document $doc): Notification
    // {
    //     return $this->create(
    //         $user->id,
    //         Notification::TYPE_DOCUMENT_APPROVED,
    //         "Tài liệu đã được duyệt",
    //         "Tài liệu \"{$doc->title}\" của bạn đã được duyệt và xuất bản.",
    //         ['document_id' => $doc->id]
    //     );
    // }
    // ══════════════════════════════════════════════════════════════

    
    // ══════════════════════════════════════════════════════════════
    // NHẮC LỊCH SỰ KIỆN (schedule_reminder)
    // ══════════════════════════════════════════════════════════════

    /**
     * Đồng bộ thông báo nhắc lịch cho các sự kiện sắp diễn ra trong 60 phút tới.
     * Gọi định kỳ từ Scheduled Command.
     */
    public function syncDueEventNotifications(int $userId): int
    {
        $now       = now();
        $today     = $now->toDateString();
        $nowTime   = $now->format('H:i');
        $windowEnd = $now->copy()->addMinutes(60)->format('H:i');

        $events = Event::where('user_id', $userId)
            ->where('status', 'active')
            ->whereDate('date', $today)
            ->where(function ($query) use ($nowTime, $windowEnd) {
                // Sự kiện không giờ cụ thể luôn được nhắc trong ngày,
                // hoặc sự kiện có giờ bắt đầu nằm trong khoảng [bây giờ, +60 phút].
                // Bắt buộc có chặn dưới (>= $nowTime) để không nhắc lại
                // các sự kiện đã diễn ra từ trước đó trong ngày.
                $query->whereNull('start')
                      ->orWhereBetween('start', [$nowTime, $windowEnd]);
            })
            ->get();

        $created = 0;

        foreach ($events as $event) {
            if ($this->alreadyNotified($userId, Notification::TYPE_SCHEDULE_REMINDER, 'event_id', $event->id)) {
                continue;
            }

            try {
                $calendarUrl = route('user.calendars');
            } catch (\Exception $e) {
                $calendarUrl = '/user/calendars';
            }

            $this->create(
                $userId,
                Notification::TYPE_SCHEDULE_REMINDER,
                "Sự kiện \"{$event->title}\" sắp diễn ra",
                "Sự kiện của bạn diễn ra vào {$event->date->format('d/m/Y')}" .
                    ($event->start ? " lúc {$event->start}" : ''),
                ['event_id' => $event->id, 'url' => $calendarUrl]
            );

            $created++;
        }

        return $created;
    }

    // ══════════════════════════════════════════════════════════════
    // NHẮC LỊCH THI (exam_reminder)
    // ══════════════════════════════════════════════════════════════

    public function examReminder(User $user, string $examTitle, int $examId, \DateTimeInterface $examDate): Notification
    {
        return $this->create(
            $user->id,
            Notification::TYPE_EXAM_REMINDER,
            "Bài thi \"{$examTitle}\" sắp diễn ra",
            "Bạn có bài thi vào {$examDate->format('d/m/Y H:i')}, hãy chuẩn bị trước nhé!",
            ['exam_id' => $examId]
        );
    }

    // ══════════════════════════════════════════════════════════════
    // GÓI SẮP HẾT HẠN (plan_expiring)
    // ══════════════════════════════════════════════════════════════

    public function planExpiring(Subscription $subscription, int $daysLeft): ?Notification
    {
        if ($this->alreadyNotified(
            $subscription->user_id,
            Notification::TYPE_PLAN_EXPIRING,
            'subscription_id',
            $subscription->id
        )) {
            return null;
        }

        return $this->create(
            $subscription->user_id,
            Notification::TYPE_PLAN_EXPIRING,
            "Gói {$subscription->plan->name} sắp hết hạn",
            "Gói của bạn sẽ hết hạn sau {$daysLeft} ngày. Gia hạn ngay để không bị gián đoạn học tập.",
            ['subscription_id' => $subscription->id, 'plan_id' => $subscription->plan_id]
        );
    }

    // ══════════════════════════════════════════════════════════════
    // THANH TOÁN HOÀN TẤT (payment_completed)
    // ══════════════════════════════════════════════════════════════

    public function paymentCompleted(User $user, Plan $plan, string $invoiceNumber): Notification
    {
        return $this->create(
            $user->id,
            Notification::TYPE_PAYMENT_COMPLETED,
            "Thanh toán thành công",
            "Bạn đã đăng ký thành công gói {$plan->name}. Hạn mức mới đã được kích hoạt.",
            ['plan_id' => $plan->id, 'invoice' => $invoiceNumber]
        );
    }

    
}