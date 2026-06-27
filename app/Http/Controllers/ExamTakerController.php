<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use App\Models\Question;
use Illuminate\Support\Facades\Log;

class ExamTakerController extends Controller
{
    /**
     * Hiển thị trang làm bài thi.
     */
    public function show($id)
    {
        Log::info("Accessing exam taker for exam ID: {$id}");

        $exam = Exam::with(['questions', 'security'])->findOrFail($id);

        $examData = $this->formatExamData($exam);

        return view('user.exams.taker', compact('examData'));
    }

    /**
     * Lưu kết quả làm bài.
     */
    public function submit(Request $request, $id)
    {
       $exam = Exam::with(['questions' => function($query) use ($id) {
            // Lọc các câu hỏi có examId khớp với ID bài thi
            $query->where('examId', $id);
        }, 'security'])
        ->findOrFail($id);
        $validated = $request->validate([
            'name'              => 'nullable|string|max:255',
            'access_key'        => 'nullable|string',
            'answers'           => 'required|array',
            'time_taken_seconds'=> 'required|integer|min:0',
        ]);

        // Kiểm tra access key nếu bài thi yêu cầu
        if ($exam->security?->useAccessKey) {
            $submitted = strtoupper(trim($validated['access_key'] ?? ''));
            $expected  = strtoupper(trim($exam->security->accessKey ?? ''));
            if ($submitted !== $expected) {
                return response()->json(['message' => 'Mã truy cập không đúng.'], 422);
            }
        }

        // Kiểm tra số lần làm bài (theo user hoặc theo tên nếu không đăng nhập)
        // Tính điểm
        log::info("data exam with questions for scoring: " . json_encode($exam));
        $scoring = $this->calculateScore($exam, $validated['answers']);

        $passed = $scoring['score'] >= ($exam->passMark ?? 60);

        $attempt = ExamAttempt::create([
            'exam_id'            => $exam->id,
            'user_id'            => auth()->id(),
            'candidate_name'     => $validated['name'] ?? null,
            'score'              => $scoring['score'],
            'correct'            => $scoring['correct'],
            'total_questions'    => $scoring['total'],
            'passed'             => $passed,
            'time_taken_seconds' => $validated['time_taken_seconds'],
            'answers'            => $validated['answers'],
        ]);

        return response()->json([
            'attempt' => [
                'id'      => $attempt->id,
                'score'   => $attempt->score,
                'correct' => $attempt->correct,
                'total'   => $attempt->total_questions,
                'passed'  => $attempt->passed,
            ],
        ]);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function formatExamData(Exam $exam): array
    {
        return [
            'id'             => $exam->id,
            'title'          => $exam->name,
            'description'    => $exam->description,
            'duration'       => $exam->duration,
            'passMark'       => $exam->passMark,
            'maxAttempts'    => $exam->maxAttempts,
            'status'         => $exam->status,
            'shuffle'        => $exam->shuffle,
            'shuffleOptions' => $exam->shuffleOptions,
            'showResult'     => $exam->showResult,
            'requireName'    => $exam->requireName,
            'security'       => $exam->security ? [
                'useAccessKey'    => $exam->security->useAccessKey,
                'accessKey'       => $exam->security->accessKey,
                'noTab'           => $exam->security->noTab,
                'noCopy'          => $exam->security->noCopy,
                'noRightClick'    => $exam->security->noRightClick,
                'forceFullscreen' => $exam->security->forceFullscreen,
                'maxTabWarnings'  => $exam->security->maxTabWarnings,
            ] : null,
            'questions' => $exam->questions->map(fn($q) => [
                '_id'            => $q->id,
                'text'           => $q->text,
                'type'           => $q->type,
                'points'         => $q->points,
                'options'        => $q->options ?? [],
                'correctAnswers' => $q->correctAnswers ?? [],
                'explanation'    => $q->explanation,
            ])->values()->toArray(),
        ];
    }

    private function calculateScore(Exam $exam, array $answers): array
    {
        $correct     = 0;
        $totalPoints = 0;
        $earned      = 0;
        log::info("view correct answers: " . json_encode($exam->questions->pluck('correctAnswers')));
        log::info("view submitted answers: " . json_encode($answers));
        foreach ($exam->questions as $question) {
            $points      = $question->points ?? 1;
            $totalPoints += $points;

            $correctSet  = collect($question->correctAnswers)->map(fn($v) => (string) $v)->sort()->values()->join(',');
            $submitted   = $answers[$question->id] ?? null; // Use question ID as key

            if (is_array($submitted)) {
                $submittedSet = collect($submitted)->map(fn($v) => (string) $v)->sort()->values()->join(',');
            } else {
                $submittedSet = $submitted !== null ? (string) $submitted : '';
            }

            // --- GHI LOG ĐỂ KIỂM TRA ---
            Log::info("Debug Score Calculation:", [
                'question_id'    => $question->id,
                'correctSet'     => $correctSet,
                'submittedSet'   => $submittedSet,
                'is_match'       => ($correctSet === $submittedSet)
            ]);
            // ---------------------------

            if ($correctSet === $submittedSet) {
                $correct++;
                $earned += $points;
            }
        }

        $score = $totalPoints > 0 ? (int) round(($earned / $totalPoints) * 100) : 0;

        return [
            'score'   => $score,
            'correct' => $correct,
            'total'   => $exam->questions->count(),
        ];
    }
}