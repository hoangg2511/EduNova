<?php

namespace App\Http\Controllers;

use App\Http\Controllers\NotificationController;
use App\Models\Document;
use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $totalExams = Exam::where('user_id', $userId)
            ->where('status', '!=', 'deleted')
            ->count();

        $examIds = Exam::where('user_id', $userId)
            ->where('status', '!=', 'deleted')
            ->pluck('id');

        $totalAttempts = ExamAttempt::whereIn('exam_id', $examIds)->count();

        $passCount = ExamAttempt::whereIn('exam_id', $examIds)
            ->where('passed', true)
            ->count();

        $passRate = $totalAttempts > 0 ? round($passCount / $totalAttempts * 100) : 0;

        $createdReminders = NotificationController::syncDueEventNotifications($userId);
        if ($createdReminders > 0) {
            Log::info("Created {$createdReminders} due event reminders for user {$userId}");
        }

        $totalDocs = Document::where('uploaded_by', $userId)->count();

        $recentDocuments = Document::where('uploaded_by', $userId)
            ->latest()
            ->take(5)
            ->get();

        $recentExamAttempts = ExamAttempt::whereIn('exam_id', $examIds)
            ->with('exam')
            ->latest()
            ->take(5)
            ->get();

        $recentExams = Exam::with(['attempts'])
            ->where('user_id', $userId)
            ->where('status', '!=', 'deleted')
            ->latest()
            ->take(3)
            ->get();

        $activities = collect();

        foreach ($recentExams as $exam) {
            $activities->push([
                'type' => 'exam',
                'title' => $exam->title,
                'meta' => 'Tạo bài thi',
                'icon' => 'file-text',
                'color' => 'slate',
                'time' => $exam->created_at->diffForHumans(),
                'created_at' => $exam->created_at,
            ]);
        }

        foreach ($recentDocuments as $doc) {
            $activities->push([
                'type' => 'doc',
                'title' => $doc->name,
                'meta' => 'Tải lên · ' . $doc->created_at->format('H:i · d/m'),
                'icon' => 'book-open',
                'color' => 'violet',
                'time' => $doc->created_at->diffForHumans(),
                'created_at' => $doc->created_at,
            ]);
        }

        foreach ($recentExamAttempts as $attempt) {
            $activities->push([
                'type' => 'attempt',
                'title' => $attempt->exam?->title ?? 'Bài thi',
                'meta' => ($attempt->passed ? 'Đậu' : 'Rớt') . ' · ' . $attempt->created_at->format('H:i · d/m'),
                'icon' => 'check-circle',
                'color' => 'teal',
                'time' => $attempt->created_at->diffForHumans(),
                'created_at' => $attempt->created_at,
            ]);
        }

        $activities = $activities->sortByDesc('created_at')->values()->take(5);

        $recentExamsData = $recentExams->map(function ($exam) {
            $attemptsCount = $exam->attempts->count();
            $passCountForExam = $exam->attempts->where('passed', true)->count();
            $passRateForExam = $attemptsCount > 0 ? round($passCountForExam / $attemptsCount * 100) : 0;

            return [
                'name' => $exam->title,
                'attempts' => $attemptsCount,
                'max' => max(1, $attemptsCount),
                'pass' => $passRateForExam,
            ];
        });

        $recentAttemptRows = $recentExamAttempts->map(function ($attempt) {
            return [
                'name' => $attempt->candidate_name ?: 'Khách',
                'exam' => $attempt->exam?->title ?? 'Bài thi',
                'score' => $attempt->score,
                'passed' => (bool) $attempt->passed,
                'time' => $attempt->created_at->format('H:i · d/m'),
            ];
        });

        return view('user.dashboard.index', [
            'totalExams' => $totalExams,
            'totalAttempts' => $totalAttempts,
            'passRate' => $passRate,
            'totalDocs' => $totalDocs,
            'activities' => $activities,
            'recentExams' => $recentExamsData,
            'attempts' => $recentAttemptRows,
        ]);
    }
}
