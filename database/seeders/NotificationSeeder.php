<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->warn('Không có user nào trong DB — bỏ qua NotificationSeeder.');
            return;
        }

        $samples = [
            [
                'type'  => 'exam_reminder',
                'title' => 'Bài thi sắp bắt đầu',
                'body'  => 'Bài thi "Toán 12 - Giữa HK1" sẽ bắt đầu sau 30 phút. Hãy chuẩn bị sẵn sàng và đảm bảo kết nối mạng ổn định trước khi vào thi.',
                'data'  => ['url' => '/exams/1'],
                'read_at' => null,
                'created_at' => now()->subMinutes(2),
            ],
            [
                'type'  => 'schedule_reminder',
                'title' => 'Nhắc lịch học',
                'body'  => 'Buổi học "Vật lý - Dao động cơ học" được lên lịch lúc 14:00 hôm nay. Đừng quên chuẩn bị tài liệu trước khi vào buổi học.',
                'data'  => ['url' => '/calendar'],
                'read_at' => null,
                'created_at' => now()->subMinutes(15),
            ],
            [
                'type'  => 'ai_result',
                'title' => 'AI đã tạo xong bài thi',
                'body'  => 'Bài thi "CI/CD cơ bản" với 12 câu hỏi đã được tạo thành công và sẵn sàng để bạn xem lại hoặc chỉnh sửa trước khi xuất bản.',
                'data'  => ['url' => '/exams/20'],
                'read_at' => now()->subMinutes(10),
                'created_at' => now()->subHour(),
            ],
            [
                'type'  => 'streak',
                'title' => 'Giữ chuỗi học tập',
                'body'  => 'Bạn đã học liên tiếp 7 ngày! Đừng bỏ lỡ hôm nay để giữ vững chuỗi thành tích của mình.',
                'data'  => null,
                'read_at' => now()->subHours(3),
                'created_at' => now()->subHours(5),
            ],
            [
                'type'  => 'system',
                'title' => 'Cập nhật hệ thống',
                'body'  => 'EduNova vừa thêm tính năng Flash Card mới! Giờ đây bạn có thể tạo bộ thẻ ghi nhớ tự động bằng AI ngay trong khung chat.',
                'data'  => ['url' => '/changelog'],
                'read_at' => now()->subDay(),
                'created_at' => now()->subDay(),
            ],
        ];

        foreach ($samples as $item) {
            Notification::create(array_merge($item, ['user_id' => $user->id]));
        }

        $this->command->info('Đã tạo ' . count($samples) . ' thông báo mẫu cho user #' . $user->id);
    }
}