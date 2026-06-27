<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Security;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        if (!$user) return;

        $security = Security::create([
            'noTab' => true,
            'noCopy' => true,
            'noRightClick' => true,
            'fullRandom' => false,
            'forceFullscreen' => false,
            'maxTabWarnings' => 3,
        ]);

        $exam = Exam::create([
            'user_id' => $user->id,
            'security_id' => $security->id,
            'title' => 'Kiểm tra Toán cao cấp A1',
            'description' => 'Chương 1 - Giới hạn và liên tục',
            'duration' => 30,
            'passMark' => 60,
            'status' => 'published',
            'maxAttempts' => 3,
            'shuffle' => true,
            'shuffleOptions' => true,
            'showResult' => true,
        ]);

        $questions = [
            [
                'text' => 'lim(x→0) (sin x)/x = ?',
                'type' => 'single',
                'points' => 1,
                'options' => ['0', '1', '∞', 'Không tồn tại'],
                'correctAnswers' => ['1'],
            ],
            [
                'text' => 'Hàm số f(x) = 1/x liên tục tại x = 0?',
                'type' => 'truefalse',
                'points' => 1,
                'options' => ['true', 'false'],
                'correctAnswers' => ['false'],
            ],
            [
                'text' => 'Đạo hàm của x² là?',
                'type' => 'single',
                'points' => 1,
                'options' => ['x', '2x', 'x²', '2'],
                'correctAnswers' => ['1'],
            ],
        ];

        foreach ($questions as $q) {
            Question::create(array_merge($q, ['examId' => $exam->id]));
        }
    }
}
