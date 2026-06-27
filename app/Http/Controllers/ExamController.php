<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ExcelService;
class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $exams = Exam::with(['questions', 'security'])
            ->where('user_id', Auth::id())
            ->whereNot('status', 'deleted')
            ->get();
            
        $userExamIds = $exams->pluck('id');
        $allAttempts = ExamAttempt::whereIn('exam_id', $userExamIds)->get();

        // 1. Cấu trúc lại dữ liệu Exam
        $formattedExams = $exams->map(function ($exam) {
            return [
                'id' => $exam->id,
                'title' => $exam->title,
                'description' => $exam->description,
                'duration' => $exam->duration,
                'passMark' => $exam->passMark,
                'status' => $exam->status,
                'maxAttempts' => $exam->maxAttempts,
                'shuffle' => (bool)$exam->shuffle,
                'shuffleOptions' => (bool)$exam->shuffleOptions,
                'showResult' => (bool)$exam->showResult,
                'requireName' => (bool)$exam->requireName,
                'security' => $exam->security ? [
                    'useAccessKey' => (bool)$exam->security->useAccessKey,
                    'accessKey' => $exam->security->accessKey,
                    'noTab' => (bool)$exam->security->noTab,
                    'noCopy' => (bool)$exam->security->noCopy,
                    'noRightClick' => (bool)$exam->security->noRightClick,
                    'forceFullscreen' => (bool)$exam->security->forceFullscreen,
                    'maxTabWarnings' => $exam->security->maxTabWarnings,
                ] : null,
                'questions' => $exam->questions->map(function ($q) {
                    return [
                        '_id' => $q->id,
                        'text' => $q->text,
                        'type' => $q->type,
                        'points' => $q->points,
                        'options' => $q->options,
                        'correctAnswers' => $q->correctAnswers,
                        'explanation' => $q->explanation,
                    ];
                })
            ];
        });

        // 2. Cấu trúc lại dữ liệu Attempts
        $formattedAttempts = $allAttempts->map(function ($attempt) {
            return [
                'examId' => $attempt->exam_id,
                'examTitle' => $attempt->exam->title, // Yêu cầu relationship 'exam' trong model Attempt
                'candidate_name' => $attempt->candidate_name,
                'score' => $attempt->score,
                'correct' => $attempt->correct,
                'total' => $attempt->total_questions,
                'passed' => (bool)$attempt->passed,
                'date' => $attempt->created_at->format('Y-m-d H:i'),
                'timeTaken' => $attempt->time_taken_seconds . ' giây',
                'answers' => $attempt->answers,
            ];
        });

        Log::info('Fetched exams and attempts', [
            'user_id' => Auth::id(),
            'exams' => $formattedExams,
            'attempts' => $formattedAttempts,
        ]);

        // Nếu bạn muốn truyền sang Blade view dưới dạng JSON hoặc Object đã format
        return view('user.exams.index', [
            'exams' => $formattedExams, 
            'allAttempts' => $formattedAttempts
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'required|integer',
            'passMark' => 'required|integer',
            'status' => 'required|in:published,draft',
            'questions' => 'required|array',
            'security' => 'nullable|array',
        ]);
       return DB::transaction(function () use ($request) {
        
        // 2. Tạo bài thi
            $exam = Exam::create([
                'user_id' => Auth::id(),
                'title' => $request->title,
                'description' => $request->description,
                'duration' => $request->duration,
                'passMark' => $request->passMark,
                'status' => $request->status,
                'maxAttempts' => $request->maxAttempts ?? 1,
                'shuffle' => $request->shuffle ?? false,
                'shuffleOptions' => $request->shuffleOptions ?? false,
                'showResult' => $request->showResult ?? true,
                'requireName' => $request->requireName ?? true,
            ]);

            // 3. Lưu Security
            if ($request->has('security')) {
                $exam->security()->create($request->security);
            }
            // 4. Lưu Questions
            foreach ($request->questions as $q) {
                $exam->questions()->create([
                    'text' => $q['text'],
                    'type' => $q['type'],
                    'points' => $q['points'] ?? 1,
                    'options' => $q['options'], // Laravel sẽ tự cast sang JSON nếu đã config trong Model
                    'correctAnswers' => $q['correctAnswers'],
                    'explanation' => $q['explanation'] ?? null,
                ]);
            }

            return response()->json([
                'message' => 'Bài thi đã được tạo thành công!',
                'exam' => $exam->load(['questions', 'security'])
            ], 201);
        });
    }

    public function destroy($id)
    {
        log::info('Attempting to delete exam', [
            'user_id' => Auth::id(),
            'exam_id' => $id,
        ]);
        // Tìm bài thi thuộc về user hiện tại
        $exam = Exam::where('user_id', Auth::id())
                    ->where('id', $id)
                    ->firstOrFail();

        // Cập nhật trạng thái thành 'deleted'
        $exam->update(['status' => 'deleted']);

        return response()->json([
            'message' => 'Bài thi đã được xóa thành công!'
        ]);
    }

    public function update(Request $request, $id)
    {
        // 1. Validate
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'required|integer',
            'passMark' => 'required|integer',
            'status' => 'required|in:published,draft',
            'questions' => 'required|array',
            'security' => 'nullable|array',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $exam = Exam::where('user_id', Auth::id())
                        ->where('id', $id)
                        ->firstOrFail();

            // 2. Xử lý Security (Logic tạo mới/cập nhật theo kiểu belongsTo)
            $securityData = $request->input('security', []);
            
            $defaultSecurity = [
                'useAccessKey' => false,
                'accessKey' => '',
                'noTab' => true,
                'noCopy' => true,
                'noRightClick' => false,
                'forceFullscreen' => false,
                'maxTabWarnings' => 3
            ];
            
            $finalSecurity = array_merge($defaultSecurity, $securityData);

            // Nếu bài thi chưa có security, tạo mới và lấy ID
            // Nếu đã có, lấy đối tượng hiện tại để cập nhật
            $security = $exam->security()->updateOrCreate(
                ['id' => $exam->security_id], // Tìm dựa trên ID hiện có trong cột security_id
                $finalSecurity
            );

            // 3. Cập nhật thông tin chính của bài thi
            $exam->update($request->only([
                'title', 'description', 'duration', 'passMark', 
                'status', 'maxAttempts', 'shuffle', 'shuffleOptions'
            ]));

            // Gán security_id nếu chưa có (trường hợp bài thi tạo mới trước đó chưa có security)
            if (!$exam->security_id) {
                $exam->security_id = $security->id;
                $exam->save();
            }

            // 4. Cập nhật Questions
            $exam->questions()->delete();
            foreach ($request->questions as $q) {
                $exam->questions()->create([
                    'examId' => $exam->id, // Giữ nguyên theo Model của bạn
                    'text' => $q['text'] ?? 'Câu hỏi không nội dung',
                    'type' => $q['type'],
                    'points' => $q['points'] ?? 1,
                    'options' => $q['options'],
                    'correctAnswers' => $q['correctAnswers'],
                    'explanation' => $q['explanation'] ?? null,
                ]);
            }

            return response()->json([
                'message' => 'Đã cập nhật thành công!',
                'exam' => $exam->load(['questions', 'security'])
            ]);
        });
    }

    public function exportTemplateQuestion(ExcelService $excelService)
    {
        log::info('User requested exam question template export', [
            'user_id' => Auth::id(),
        ]);
        return $excelService->templateQuestion();
    }

    public function exportTemplateExam(ExcelService $excelService)
    {
        log::info('User requested exam template export', [
            'user_id' => Auth::id(),
        ]);
        return $excelService->templateExam();
    }

    public function importExam(Request $request, ExcelService $importService)
    {
        // Validate file
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        try {
            // Gọi service xử lý lưu vào Database
            $exam = $importService->importExam($request->file('file')->getRealPath());

            return response()->json([
                'success' => true,
                'message' => 'Đã tạo bài thi: ' . $exam->title
            ]);
        } catch (\Exception $e) {
            Log::error('Import error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Lỗi xử lý file.'], 500);
        }
    }

    public function exportExam($id, ExcelService $excelService)
    {
        // Bỏ dấu $ ở ngay trước excelService
        return $excelService->exportExam($id);
    }

    /**
     * Tạo access key ngẫu nhiên.
     */
    public function generateKey(): JsonResponse
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $key   = '';
        for ($i = 0; $i < 8; $i++) {
            $key .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return response()->json(['key' => $key]);
    }

    /**
     * Trả về share URL và thông tin chia sẻ của bài thi.
     */
    public function shareInfo(Request $request, Exam $exam): JsonResponse
    {
        $shareUrl = route('exams.taker', ['id' => $exam->id]);

        return response()->json([
            'url'       => $shareUrl,
            'title'     => $exam->name,
            'accessKey' => $exam->security?->useAccessKey ? $exam->security->accessKey : null,
        ]);
    }

}
