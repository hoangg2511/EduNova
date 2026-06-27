<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExamAttemptSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $exam = Exam::where('title', 'Kiểm tra Toán cao cấp A1')->first();
        if (!$exam) {
            return;
        }

        $attempts = [
            [
                'exam_id' => $exam->id,
                'candidate_name' => 'Nguyễn Văn A',
                'score' => 80,
                'correct' => 2,
                'total_questions' => 3,
                'passed' => true,
                'time_taken_seconds' => 12 * 60,
                'answers' => [0 => 1, 1 => 'false', 2 => 3],
            ],
            [
                'exam_id' => $exam->id,
                'candidate_name' => 'Trần Thị B',
                'score' => 33,
                'correct' => 1,
                'total_questions' => 3,
                'passed' => false,
                'time_taken_seconds' => 8 * 60,
                'answers' => [0 => 0, 1 => 'false', 2 => 1],
            ],
            [
                'exam_id' => $exam->id,
                'candidate_name' => 'Lê Văn C',
                'score' => 100,
                'correct' => 3,
                'total_questions' => 3,
                'passed' => true,
                'time_taken_seconds' => 5 * 60,
                'answers' => [0 => 1, 1 => 'false', 2 => 1],
            ],
        ];

        foreach ($attempts as $attempt) {
            ExamAttempt::create($attempt);
        }
    }
}
