<?php

namespace App\Http\Controllers;

use App\Services\AIAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\NotificationController;
use App\Models\Message;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Security;
use App\Models\Event;
use App\Models\Deck;
use App\Models\Card;
use App\Models\UserLog;
class ChatbotController extends Controller
{
    public function __construct(private AIAgentService $aiService) {}

    // ─────────────────────────────────────────────────────────────────────────
    // SYSTEM PROMPTS
    // ─────────────────────────────────────────────────────────────────────────

    private function promptDefault(string $context): string
    {
        return
            "Bạn là trợ lý AI thông minh cho nền tảng học tập EduNova.\n" .
            "Hãy trả lời bằng tiếng Việt, thân thiện, rõ ràng và hữu ích.\n" .
            "Bạn có thể giúp: tạo lộ trình học, giải thích kiến thức, gợi ý tài liệu học tập.\n" .
            $context;
    }

        private function promptCreateExam(string $context): string
    {
        return <<<PROMPT
Bạn là trợ lý tạo đề thi cho nền tảng EduNova.
NHIỆM VỤ: Dựa trên mô tả của người dùng, tạo một bài thi hoàn chỉnh.
 
═══════════════════════════════════════════════
BƯỚC 1 — CHỌN ĐÚNG LOẠI CÂU HỎI (QUAN TRỌNG NHẤT)
═══════════════════════════════════════════════
 
Với MỖI câu hỏi, tự hỏi theo đúng thứ tự sau:
 
  Câu hỏi này có đúng 2 lựa chọn và bản chất là "câu này đúng hay sai"?
  → CÓ: dùng type = "truefalse"
  → KHÔNG: tiếp tục bước dưới
 
  Câu hỏi có CHỈ 1 đáp án đúng trong số nhiều lựa chọn?
  → CÓ: dùng type = "single"
  → KHÔNG: tiếp tục bước dưới
 
  Câu hỏi có TỪ 2 đáp án đúng trở lên trong số nhiều lựa chọn?
  → CÓ: dùng type = "multiple"
 
⚠️ QUY TẮC CỨNG — VI PHẠM LÀ LỖI NGHIÊM TRỌNG:
  - type = "single"   → correctAnswers PHẢI có ĐÚNG 1 phần tử.
  - type = "multiple" → correctAnswers PHẢI có TỪ 2 phần tử trở lên.
  - type = "truefalse"→ options PHẢI là đúng 2 phần tử ["true","false"], không được viết "Đúng"/"Sai" hay bất kỳ ngôn ngữ nào khác.
  - KHÔNG BAO GIỜ dùng type="single" hoặc "multiple" mà options chỉ là ["Đúng","Sai"] hoặc ["Sai","Đúng"] — đó luôn luôn phải là type="truefalse".
 
═══════════════════════════════════════════════
BƯỚC 2 — VÍ DỤ ĐỐI CHIẾU SAI / ĐÚNG (HỌC THUỘC TRƯỚC KHI LÀM)
═══════════════════════════════════════════════
 
❌ SAI (lỗi model hay mắc phải):
{
  "text": "Triển khai Liên tục (CD) mở rộng CI bằng cách tự động triển khai mã đã kiểm thử vào production?",
  "type": "single",
  "options": ["Sai", "Đúng"],
  "correctAnswers": [1]
}
→ Lỗi: đây là câu hỏi Đúng/Sai nhưng bị gắn type="single" và options dùng tiếng Việt.
 
✅ ĐÚNG (sửa lại câu trên):
{
  "text": "Triển khai Liên tục (CD) mở rộng CI bằng cách tự động triển khai mã đã kiểm thử vào production.",
  "type": "truefalse",
  "options": ["true", "false"],
  "correctAnswers": ["true"],
  "explanation": "Đúng. CD tự động hóa việc đưa code đã qua kiểm thử vào môi trường production hoặc staging."
}
 
---
 
❌ SAI (lỗi model hay mắc phải):
{
  "text": "GitLab CI là một công cụ CI/CD được sử dụng để xây dựng, tự động hóa và quản lý các pipeline.",
  "type": "multiple",
  "options": ["Jenkins", "GitLab CI", "GitHub Actions"],
  "correctAnswers": [1]
}
→ Lỗi kép: (1) chỉ có 1 correctAnswer nhưng để type="multiple", (2) options là danh sách công cụ không liên quan logic đến câu hỏi đang hỏi về GitLab CI cụ thể — đây thực chất là câu khẳng định Đúng/Sai.
 
✅ ĐÚNG (sửa lại câu trên):
{
  "text": "GitLab CI là một công cụ CI/CD được sử dụng để xây dựng, tự động hóa và quản lý các pipeline.",
  "type": "truefalse",
  "options": ["true", "false"],
  "correctAnswers": ["true"],
  "explanation": "Đúng. GitLab CI là công cụ CI/CD tích hợp sẵn trong GitLab để tự động hóa pipeline."
}
 
---
 
✅ VÍ DỤ ĐÚNG cho type="multiple" (câu hỏi liệt kê, nhiều đáp án cùng đúng):
{
  "text": "Những loại kiểm thử nào sau đây thuộc về CI/CD pipeline?",
  "type": "multiple",
  "options": ["Unit test", "Integration test", "End-to-end test", "Kiểm tra lương nhân viên"],
  "correctAnswers": [0, 1, 2],
  "explanation": "Unit test, Integration test và End-to-end test đều là các bước kiểm thử tự động trong CI/CD."
}
 
✅ VÍ DỤ ĐÚNG cho type="single" (câu hỏi chọn 1 trong nhiều, các lựa chọn KHÔNG phải Đúng/Sai):
{
  "text": "Công cụ nào sau đây được GitHub phát triển và tích hợp sẵn?",
  "type": "single",
  "options": ["Jenkins", "GitLab CI", "GitHub Actions", "CircleCI"],
  "correctAnswers": [2],
  "explanation": "GitHub Actions là công cụ CI/CD được GitHub phát triển và tích hợp sẵn trong nền tảng."
}
 
═══════════════════════════════════════════════
BƯỚC 3 — QUY TẮC FIELD CHI TIẾT
═══════════════════════════════════════════════
 
▶ type = "single":
  - options: mảng 3-4 lựa chọn nội dung cụ thể (KHÔNG được là ["Đúng","Sai"])
  - correctAnswers: mảng đúng 1 số nguyên (index 0-based)
 
▶ type = "multiple":
  - options: mảng 3-4 lựa chọn nội dung cụ thể
  - correctAnswers: mảng từ 2 số nguyên trở lên (index 0-based)
 
▶ type = "truefalse":
  - options: luôn luôn ["true", "false"] — không đổi
  - correctAnswers: luôn luôn ["true"] hoặc ["false"] dạng string
 
▶ Mọi loại đều BẮT BUỘC có "explanation" không rỗng — giải thích ngắn gọn (1-2 câu) vì sao đáp án đó đúng.
 
▶ "text": câu hỏi phải là MỘT câu hỏi hoàn chỉnh, rõ ràng, không lặp lại nguyên văn các options bên trong.
 
═══════════════════════════════════════════════
BƯỚC 4 — TỰ KIỂM TRA TRƯỚC KHI XUẤT (BẮT BUỘC)
═══════════════════════════════════════════════
 
Trước khi trả lời, tự rà soát TỪNG câu hỏi đã tạo:
  ☐ Nếu options là ["Đúng","Sai"] hoặc ["Sai","Đúng"] hoặc tương tự → ĐỔI thành type="truefalse", options=["true","false"], sửa correctAnswers thành ["true"]/["false"]
  ☐ Nếu type="single" mà correctAnswers có hơn 1 phần tử → đổi type thành "multiple"
  ☐ Nếu type="multiple" mà correctAnswers chỉ có 1 phần tử → đổi type thành "single"
  ☐ Nếu explanation rỗng → viết thêm giải thích
 
═══════════════════════════════════════════════
QUY TẮC ĐẦU RA
═══════════════════════════════════════════════
 
1. CHỈ trả về JSON thuần túy. KHÔNG markdown, KHÔNG ```json, KHÔNG lời chào/giải thích ngoài JSON.
2. JSON phải parse được ngay bằng json_decode().
3. Tạo tối thiểu 5 câu, tối đa 20 câu.
4. duration: số phút (mặc định 30). passMark: 0-100 (mặc định 60). points: mặc định 1.
 
═══════════════════════════════════════════════
JSON SCHEMA ĐẦU RA
═══════════════════════════════════════════════
 
{
  "title": "Tên bài thi",
  "description": "Mô tả ngắn",
  "duration": 30,
  "passMark": 60,
  "questions": [
    {
      "text": "Hà Nội là thủ đô của quốc gia nào?",
      "type": "single",
      "points": 1,
      "options": ["Việt Nam", "Trung Quốc", "Lào", "Campuchia"],
      "correctAnswers": [0],
      "explanation": "Hà Nội là thủ đô của Việt Nam."
    },
    {
      "text": "Những thành phố nào sau đây thuộc Việt Nam?",
      "type": "multiple",
      "points": 1,
      "options": ["Hà Nội", "London", "TP HCM", "Tokyo"],
      "correctAnswers": [0, 2],
      "explanation": "Hà Nội và TP HCM đều là thành phố của Việt Nam, London và Tokyo không phải."
    },
    {
      "text": "Hàm số f(x)=1/x liên tục tại x=0.",
      "type": "truefalse",
      "points": 1,
      "options": ["true", "false"],
      "correctAnswers": ["false"],
      "explanation": "f(x)=1/x không xác định tại x=0 nên không thể liên tục tại điểm này."
    }
  ]
}
 
PROMPT
        . $context;
    }


    private function promptCreateSchedule(string $context, string $today): string
    {
        return <<<PROMPT
Bạn là trợ lý lập lịch học cá nhân cho nền tảng EduNova.
NHIỆM VỤ: Dựa trên mô tả của người dùng, tạo lịch học chi tiết.
Ngày hôm nay là: {$today}

═══════════════════════════════════════════════
QUY TẮC BẮT BUỘC
═══════════════════════════════════════════════

1. CHỈ trả về JSON thuần túy. KHÔNG có markdown, KHÔNG có ```json.
2. date : định dạng YYYY-MM-DD, bắt đầu từ hôm nay ({$today}) trở đi.
3. start/end : định dạng HH:MM (24h). Ví dụ: "08:00", "10:30", "14:00".
4. Tạo tối thiểu 5 sự kiện, tối đa 30 sự kiện.
5. Mỗi buổi học KHÔNG quá 3 giờ liên tục.
6. summary: đoạn văn 2–3 câu mô tả tổng quan kế hoạch.

═══════════════════════════════════════════════
JSON SCHEMA ĐẦU RA
═══════════════════════════════════════════════

{
  "summary": "Lịch học 2 tuần tập trung vào Toán và Lý, mỗi ngày 2 buổi.",
  "events": [
    {
      "title": "Học Toán – Giải tích chương 1",
      "date": "{$today}",
      "start": "08:00",
      "end": "10:00",
      "note": "Ôn lý thuyết, làm bài tập SGK trang 12–15"
    }
  ]
}

PROMPT
        . $context;
    }

    private function promptCreateFlashcard(string $context): string
    {
                return <<<PROMPT
        Bạn là trợ lý tạo flash card cho nền tảng học tập EduNova.
        NHIỆM VỤ: Dựa trên chủ đề người dùng cung cấp, tạo bộ flash card chất lượng cao.

        ═══════════════════════════════════════════════
        QUY TẮC BẮT BUỘC
        ═══════════════════════════════════════════════

        1. CHỈ trả về JSON thuần túy. KHÔNG có markdown, KHÔNG có ```json.
        2. Tạo tối thiểu 8 thẻ, tối đa 30 thẻ.
        3. front  : câu hỏi / khái niệm / từ vựng (ngắn gọn, dưới 150 ký tự).
        4. back   : câu trả lời / định nghĩa đầy đủ, dễ hiểu.
        5. hint   : gợi ý nhỏ khi người dùng bí — KHÔNG tiết lộ hẳn đáp án.
        6. difficulty: chỉ được là "easy", "medium", hoặc "hard".
        7. color  : mã hex phù hợp chủ đề.

        ═══════════════════════════════════════════════
        JSON SCHEMA ĐẦU RA
        ═══════════════════════════════════════════════

        {
        "deck": {
            "name": "Tên bộ thẻ ngắn gọn",
            "subject": "Tên môn học",
            "description": "Mô tả bộ thẻ trong 1–2 câu",
            "color": "#6366f1"
        },
        "cards": [
            {
            "front": "Câu hỏi, khái niệm hoặc từ vựng tiếng anh cần nhớ",
            "back": "Câu trả lời hoặc định nghĩa đầy đủ",
            "hint": "Gợi ý nhỏ, không lộ đáp án",
            "difficulty": "medium"
            }
        ]
        }

        PROMPT
                . $context;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DATABASE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function getHistoryFromDb(int $userId, int $limit = 20): array
    {
        $rows = Message::where('user_id', $userId)
            ->where('status', 'active')
            ->orderBy('created_at', 'asc')
            ->get(['role', 'content'])
            ->toArray();

        $mapped = array_map(fn($r) => [
            'role'    => ($r['role'] === 'ai') ? 'assistant' : $r['role'],
            'content' => $r['content'] ?? '',
        ], $rows);

        return count($mapped) > $limit
            ? array_slice($mapped, -$limit)
            : $mapped;
    }

    private function buildContext(array $history): string
    {
        if (empty($history)) return '';

        $lines = [];
        foreach (array_slice($history, -6) as $msg) {
            if (!isset($msg['role'], $msg['content'])) continue;
            $role    = $msg['role'] === 'user' ? 'Người dùng' : 'AI';
            $lines[] = "{$role}: " . mb_substr($msg['content'], 0, 150);
        }

        return empty($lines)
            ? ''
            : "\n\nLịch sử hội thoại gần đây:\n" . implode("\n", $lines) . "\n";
    }

    /**
     * Parse JSON từ output AI — xử lý nhiều trường hợp model "nổi loạn"
     */
    private function parseAiJson(string $raw): ?array
    {
        $original = $raw;

        // 1. Xóa markdown fence ```json ... ``` hoặc ``` ... ```
        $clean = preg_replace('/```(?:json)?\s*/i', '', $raw);
        $clean = preg_replace('/```/', '', $clean);
        $clean = trim($clean);

        // 2. Thử parse trực tiếp
        $data = json_decode($clean, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }

        // 3. Tìm JSON object đầu tiên trong string (model hay thêm lời chào trước/sau)
        if (preg_match('/\{[\s\S]*\}/u', $clean, $matches)) {
            $data = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                Log::info('parseAiJson: extracted JSON from mixed text');
                return $data;
            }
        }

        // 4. Log để debug
        Log::error('parseAiJson: ALL strategies failed', [
            'json_error'   => json_last_error_msg(),
            'raw_length'   => strlen($original),
            'raw_preview'  => mb_substr($original, 0, 800),
        ]);

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC ENDPOINTS
    // ─────────────────────────────────────────────────────────────────────────

    /** GET /api/chatbot/history */
    public function history(Request $request): JsonResponse
    {
        $messages = $this->getHistoryFromDb(auth()->id(), 100);
        return response()->json(['success' => true, 'messages' => $messages]);
    }

    /** POST /api/chatbot/clear */
    public function clear(Request $request): JsonResponse
    {
        try {
            $affected = Message::where('user_id', auth()->id())
                ->where('status', 'active')
                ->update(['status' => 'deleted']);

            return response()->json(['success' => true, 'deleted' => $affected]);
        } catch (\Exception $e) {
            Log::error('Chat clear error: ' . $e->getMessage(), ['user_id' => auth()->id()]);
            return response()->json(['success' => false, 'message' => 'Không thể xóa lịch sử'], 500);
        }
    }

    /** POST /api/chatbot/stream */
    public function stream(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required|string|max:4000',
                'history' => 'nullable|array',
                'tag'     => 'nullable|string|in:create_exam,create_schedule,create_flashcard',
            ]);

            $userId  = auth()->id();
            $message = $request->input('message');
            $tag     = $request->input('tag');

            Log::info('Chatbot stream request', [
                'user_id' => $userId,
                'tag'     => $tag,
                'message' => mb_substr($message, 0, 200),
            ]);

            // Lưu user message
            try {
                Message::create(['user_id' => $userId, 'role' => 'user', 'content' => $message]);
            } catch (\Exception $e) {
                Log::warning('Cannot persist user message: ' . $e->getMessage());
            }

            $history      = $this->getHistoryFromDb($userId, 20);
            $context      = $this->buildContext($history);
            $today        = now()->format('Y-m-d');

            $systemPrompt = match ($tag) {
                'create_exam'      => $this->promptCreateExam($context),
                'create_schedule'  => $this->promptCreateSchedule($context, $today),
                'create_flashcard' => $this->promptCreateFlashcard($context),
                default            => $this->promptDefault($context),
            };

            // TAG mode
            if ($tag !== null) {
                return $this->handleTagStream($userId, $message, $systemPrompt, $tag);
            }

            // CHAT thường
            $assistantContent = '';

            // ── Kiểm tra token trước khi cho chat ──────────────────────────────────
            $estimatedCost = $this->estimateTokens($message) + 200; // 200 token buffer cho reply
            if (!$this->hasEnoughTokens($userId, $estimatedCost)) {
                $log = UserLog::where('user_id', $userId)->first();
                $remaining = $log?->token_limit ?? 0;
                return response()->stream(function () use ($remaining) {
                    echo "⚠️ Bạn đã hết token chat (còn {$remaining} token). " .
                        "Vui lòng nâng cấp gói để tiếp tục.";
                    @ob_flush(); @flush();
                }, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            // ───────────────────────────────────────────────────────────────────────

            return response()->stream(function () use ($message, $systemPrompt, &$assistantContent, $userId) {
                $this->aiService->streamOllama(
                    $message,
                    $systemPrompt,
                    function (string $chunk) use (&$assistantContent) {
                        if ($chunk === '') return;
                        $assistantContent .= $chunk;
                        echo $chunk;
                        @ob_flush(); @flush();
                    }
                );

                // Lưu message history
                try {
                    if (!empty(trim($assistantContent))) {
                        Message::create(['user_id' => $userId, 'role' => 'ai', 'content' => $assistantContent]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Cannot persist assistant message: ' . $e->getMessage());
                }

                // ── Trừ token SAU KHI đã có đủ reply ──────────────────────────────
                try {
                    $this->consumeTokens($userId, $message, $assistantContent);
                } catch (\Exception $e) {
                    Log::warning('consumeTokens failed: ' . $e->getMessage());
                }
                // ───────────────────────────────────────────────────────────────────

            }, 200, [
                'Content-Type'      => 'text/plain; charset=utf-8',
                'Cache-Control'     => 'no-cache, no-store, must-revalidate',
                'X-Accel-Buffering' => 'no',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Chatbot Stream Error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'reply' => 'Xin lỗi, đã xảy ra lỗi.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TAG HANDLER CHÍNH
    // ─────────────────────────────────────────────────────────────────────────

    private function handleTagStream(int $userId, string $message, string $systemPrompt, string $tag)
    {
        // ── Bước 1: Thu thập TOÀN BỘ output từ AI (blocking, không stream ra FE) ──
        $rawOutput = '';

        try {
            $this->aiService->streamOllama(
                $message,
                $systemPrompt,
                function (string $chunk) use (&$rawOutput) {
                    $rawOutput .= $chunk;
                }
            );
        } catch (\Exception $e) {
            Log::error('handleTagStream: streamOllama failed', [
                'tag'   => $tag,
                'error' => $e->getMessage(),
            ]);
            $errorMsg = "❌ AI không phản hồi được. Vui lòng thử lại sau.";
            return response()->stream(function () use ($errorMsg) {
                echo $errorMsg;
                @ob_flush(); @flush();
            }, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        Log::info('handleTagStream: AI raw output received', [
            'tag'        => $tag,
            'raw_length' => strlen($rawOutput),
            'preview'    => mb_substr($rawOutput, 0, 500),
        ]);

        // ── Bước 2: Parse JSON ──
        $data = $this->parseAiJson($rawOutput);

        // ── Bước 3: Gọi handler tương ứng ──
        [$replyMessage, $persistContent] = match ($tag) {
            'create_exam'      => $this->handleExamCreation($userId, $data, $rawOutput),
            'create_schedule'  => $this->handleScheduleCreation($userId, $data, $rawOutput),
            'create_flashcard' => $this->handleFlashcardCreation($userId, $data, $rawOutput),
            default            => [$rawOutput, $rawOutput],
        };

        // ── Bước 4: Lưu reply vào message history ──
        try {
            if (!empty(trim($persistContent))) {
                Message::create(['user_id' => $userId, 'role' => 'ai', 'content' => $persistContent]);
            }
        } catch (\Exception $e) {
            Log::warning('Cannot persist tag reply: ' . $e->getMessage());
        }

        // ── Bước 5: Stream reply về FE với hiệu ứng typing ──
        return response()->stream(function () use ($replyMessage) {
            $chunks = str_split($replyMessage, 4);
            foreach ($chunks as $chunk) {
                echo $chunk;
                @ob_flush(); @flush();
                usleep(8000);
            }
        }, 200, [
            'Content-Type'      => 'text/plain; charset=utf-8',
            'Cache-Control'     => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EXAM CREATION
    // ─────────────────────────────────────────────────────────────────────────

    private function handleExamCreation(int $userId, ?array $data, string $rawOutput): array
     {
        // Validate dữ liệu từ AI
        if (!$data) {
            Log::error('handleExamCreation: JSON parse returned null', [
                'raw' => mb_substr($rawOutput, 0, 800),
            ]);
            return [
                "❌ AI trả về dữ liệu không phải JSON hợp lệ. Vui lòng thử lại.\n\n" .
                "_Debug: Xem laravel.log để biết thêm chi tiết._",
                "[create_exam failed - invalid JSON]",
            ];
        }

        if (empty($data['questions']) || !is_array($data['questions'])) {
            Log::error('handleExamCreation: missing or empty questions array', [
                'data_keys' => array_keys($data),
                'raw'       => mb_substr($rawOutput, 0, 800),
            ]);
            return [
                "❌ AI tạo dữ liệu nhưng không có danh sách câu hỏi. Hãy mô tả rõ hơn về bài thi bạn muốn tạo.",
                "[create_exam failed - no questions field]",
            ];
        }

        Log::info('handleExamCreation: starting DB save', [
            'user_id'        => $userId,
            'title'          => $data['title'] ?? '(no title)',
            'question_count' => count($data['questions']),
        ]);

        try {
            // ── Tạo Security ──
            $security = Security::create([
                'noTab'           => false,
                'noCopy'          => false,
                'noRightClick'    => false,
                'fullRandom'      => false,
                'forceFullscreen' => false,
                'maxTabWarnings'  => 3,
                'useAccessKey'    => false,
                'accessKey'       => null,
            ]);

            Log::info('handleExamCreation: Security created', ['security_id' => $security->id]);

            // ── Tạo Exam ──
            // Lưu ý: model Exam dùng 'title' không phải 'name'
            $exam = Exam::create([
                'user_id'        => $userId,
                'security_id'    => $security->id,
                'title'          => trim($data['title'] ?? 'Bài thi chưa đặt tên'),
                'description'    => trim($data['description'] ?? ''),
                'duration'       => max(1, (int) ($data['duration'] ?? 30)),
                'passMark'       => min(100, max(0, (int) ($data['passMark'] ?? 60))),
                'maxAttempts'    => 0,
                'status'         => 'draft',
                'shuffle'        => false,
                'shuffleOptions' => false,
                'showResult'     => true,
                'requireName'    => false,
            ]);

            Log::info('handleExamCreation: Exam created', ['exam_id' => $exam->id]);

            // ── Tạo Questions ──
            $questionCount = 0;
            $skippedCount  = 0;

            foreach ($data['questions'] as $idx => $q) {
                // Validate type
                $type = in_array($q['type'] ?? '', ['single', 'multiple', 'truefalse'])
                    ? $q['type']
                    : 'single';

                // Validate options
                if ($type === 'truefalse') {
                    $options = ['true', 'false'];
                } elseif (!empty($q['options']) && is_array($q['options'])) {
                    $options = $q['options'];
                } else {
                    Log::warning("handleExamCreation: question #{$idx} has no valid options, skipping", [
                        'question' => $q,
                    ]);
                    $skippedCount++;
                    continue;
                }

                // Validate correctAnswers
                $correctAnswers = $q['correctAnswers'] ?? [];
                if (!is_array($correctAnswers) || empty($correctAnswers)) {
                    Log::warning("handleExamCreation: question #{$idx} has no correctAnswers, skipping", [
                        'question' => $q,
                    ]);
                    $skippedCount++;
                    continue;
                }

                // Đảm bảo text không rỗng
                $text = trim($q['text'] ?? '');
                if (empty($text)) {
                    $skippedCount++;
                    continue;
                }
                foreach ($data['questions'] as $idx => &$q) {
                    $opts = array_map('strtolower', $q['options'] ?? []);
                    $isBoolLike = count($opts) === 2 && 
                        (in_array('đúng', $opts) || in_array('sai', $opts) || 
                        in_array('true', $opts) || in_array('false', $opts));

                    if ($isBoolLike && $q['type'] !== 'truefalse') {
                        // Tự sửa: tìm xem đáp án đúng là "đúng/true" hay "sai/false"
                        $correctIdx = $q['correctAnswers'][0] ?? 0;
                        $wasTrue = in_array(strtolower($q['options'][$correctIdx] ?? ''), ['đúng', 'true']);
                        $q['type'] = 'truefalse';
                        $q['options'] = ['true', 'false'];
                        $q['correctAnswers'] = [$wasTrue ? 'true' : 'false'];
                    }

                    if ($q['type'] === 'single' && count($q['correctAnswers'] ?? []) > 1) {
                        $q['type'] = 'multiple';
                    }
                    if ($q['type'] === 'multiple' && count($q['correctAnswers'] ?? []) <= 1) {
                        $q['type'] = 'single';
                    }
                }
                unset($q);
                Question::create([
                    'examId'         => $exam->id,
                    'text'           => $text,
                    'type'           => $type,
                    'points'         => max(1, (int) ($q['points'] ?? 1)),
                    'options'        => $options,
                    'correctAnswers' => $correctAnswers,
                    'explanation'    => trim($q['explanation'] ?? ''),
                ]);

                $questionCount++;
            }

            Log::info('handleExamCreation: Questions created', [
                'exam_id'        => $exam->id,
                'created'        => $questionCount,
                'skipped'        => $skippedCount,
            ]);

            // Nếu không có câu nào được tạo, xóa exam và báo lỗi
            if ($questionCount === 0) {
                $exam->delete();
                $security->delete();
                return [
                    "❌ Tất cả câu hỏi bị bỏ qua do thiếu dữ liệu (options hoặc correctAnswers). " .
                    "Vui lòng thử lại với mô tả cụ thể hơn.",
                    "[create_exam failed - all questions skipped]",
                ];
            }

            // Tạo URL — fallback nếu route không tồn tại
            try {
                $examUrl = route('exams.show', $exam->id);
                $editUrl = route('exams.edit', $exam->id);
            } catch (\Exception $e) {
                $examUrl = '/exams/' . $exam->id;
                $editUrl = '/exams/' . $exam->id . '/edit';
            }

            $skipNote = $skippedCount > 0
                ? "\n• ⚠️ {$skippedCount} câu bị bỏ qua do thiếu dữ liệu"
                : '';

            $reply =
                "✅ **Đã tạo bài thi thành công!**\n\n" .
                "📋 **{$exam->title}**\n" .
                "• {$questionCount} câu hỏi{$skipNote}\n" .
                "• Thời gian: {$exam->duration} phút\n" .
                "• Điểm đậu: {$exam->passMark}%\n" .
                "• Trạng thái: Bản nháp\n\n" .
                "🔗 [Xem bài thi]({$examUrl}) · [Chỉnh sửa]({$editUrl})";

            $saveContent = "Đã tạo \"{$exam->title}\" với {$questionCount} câu hỏi.";

            try {
                $examUrl = route('exams.show', $exam->id);
            } catch (\Exception $e) {
                $examUrl = '/exams/' . $exam->id;
            }

            NotificationController::notify(
                $userId,
                'ai_result',
                'Bài thi AI đã tạo xong',
                "Bài thi \"{$exam->title}\" đã được tạo tự động. Nhấn để xem hoặc chỉnh sửa.",
                ['exam_id' => $exam->id, 'url' => $examUrl]
            );

            return [$reply, $saveContent];

        } catch (\Exception $e) {
            Log::error('handleExamCreation: DB exception', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return [
                "❌ Lỗi khi lưu bài thi vào cơ sở dữ liệu:\n`{$e->getMessage()}`",
                "[create_exam DB error] " . $e->getMessage(),
            ];
        }
    }

    //  ─────────────────────────────────────────────────────────────────────────
    // SCHEDULE CREATION
    // ─────────────────────────────────────────────────────────────────────────

    private function handleScheduleCreation(int $userId, ?array $data, string $rawOutput): array
    {
        if (!$data || empty($data['events']) || !is_array($data['events'])) {
            Log::error('handleScheduleCreation: parse failed or no events', [
                'data_keys' => $data ? array_keys($data) : null,
                'raw'       => mb_substr($rawOutput, 0, 800),
            ]);
            return [
                "❌ Không thể tạo lịch học. AI trả về dữ liệu không hợp lệ. Vui lòng thử lại.",
                "[create_schedule failed]",
            ];
        }

        // TODO: thay bằng ID thực của TypeEvent "Học tập" trong DB
        $defaultTypeEventId = 1;

        try {
            $created = 0;
            $skipped = 0;

            foreach ($data['events'] as $ev) {
                if (empty($ev['date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ev['date'])) {
                    $skipped++;
                    continue;
                }

                $start = $ev['start'] ?? '08:00';
                $end   = $ev['end']   ?? '10:00';
                if (!preg_match('/^\d{2}:\d{2}$/', $start)) $start = '08:00';
                if (!preg_match('/^\d{2}:\d{2}$/', $end))   $end   = '10:00';

                Event::create([
                    'user_id'       => $userId,
                    'type_event_id' => $defaultTypeEventId,
                    'title'         => trim($ev['title'] ?? 'Buổi học'),
                    'date'          => $ev['date'],
                    'start'         => $start,
                    'end'           => $end,
                    'note'          => trim($ev['note'] ?? ''),
                    'status'        => 'active',
                ]);
                $created++;
            }

            $summary = $data['summary'] ?? "Lịch học đã được tạo với {$created} sự kiện.";

            try {
                $calendarUrl = route('user.calendars');
            } catch (\Exception $e) {
                $calendarUrl = '/user/calendars';
            }

            $reply =
                "✅ **Đã tạo lịch học thành công!**\n\n" .
                "📅 {$summary}\n\n" .
                "• {$created} buổi học đã được thêm vào lịch" .
                ($skipped > 0 ? "\n• ⚠️ {$skipped} buổi bị bỏ qua do định dạng ngày không hợp lệ" : "") . "\n\n" .
                "🔗 [Xem lịch của bạn]({$calendarUrl})";

            if ($created > 0) {
                NotificationController::notify(
                    $userId,
                    'ai_result',
                    'Chatbot đã tạo lịch học xong',
                    "Chatbot đã tạo thành công {$created} buổi học. Xem chi tiết trong lịch của bạn.",
                    ['url' => $calendarUrl, 'created_events' => $created]
                );
            }

            return [$reply, "[create_schedule] Đã tạo {$created} sự kiện."];

        } catch (\Exception $e) {
            Log::error('handleScheduleCreation: DB exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return [
                "❌ Lỗi khi lưu lịch học:\n`{$e->getMessage()}`",
                "[create_schedule DB error] " . $e->getMessage(),
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FLASHCARD CREATION
    // ─────────────────────────────────────────────────────────────────────────

    private function handleFlashcardCreation(int $userId, ?array $data, string $rawOutput): array
    {
        if (!$data || empty($data['deck']) || empty($data['cards']) || !is_array($data['cards'])) {
            Log::error('handleFlashcardCreation: parse failed', [
                'data_keys' => $data ? array_keys($data) : null,
                'raw'       => mb_substr($rawOutput, 0, 800),
            ]);
            return [
                "❌ Không thể tạo flash card. AI trả về dữ liệu không hợp lệ. Vui lòng thử lại.",
                "[create_flashcard failed]",
            ];
        }

        try {
            $deckData = $data['deck'];

            $deck = Deck::create([
                'user_id'     => $userId,
                'name'        => trim($deckData['name']        ?? 'Bộ thẻ chưa đặt tên'),
                'subject'     => trim($deckData['subject']     ?? ''),
                'description' => trim($deckData['description'] ?? ''),
                'color'       => $deckData['color'] ?? '#6366f1',
                'status'      => 'active',
            ]);

            $cardCount = 0;
            foreach ($data['cards'] as $c) {
                $front = trim($c['front'] ?? '');
                $back  = trim($c['back']  ?? '');
                if (empty($front) || empty($back)) continue;

                $difficulty = in_array($c['difficulty'] ?? '', ['easy', 'medium', 'hard'])
                    ? $c['difficulty']
                    : 'medium';

                Card::create([
                    'deck_id'      => $deck->id,
                    'front'        => $front,
                    'back'         => $back,
                    'hint'         => trim($c['hint'] ?? ''),
                    'difficulty'   => $difficulty,
                    'status'       => 'active',
                    'starred'      => false,
                    'review_count' => 0,
                    'flipped'      => false,
                ]);
                $cardCount++;
            }

            try {
                $deckUrl = route('flashcards.show', $deck->id);
            } catch (\Exception $e) {
                $deckUrl = '/flashcards/' . $deck->id;
            }

            NotificationController::notify(
                $userId,
                'ai_result',
                'Flashcard AI đã sẵn sàng',
                "Bộ thẻ \"{$deck->name}\" đã được tạo xong. Nhấn để xem và ôn tập ngay.",
                ['deck_id' => $deck->id, 'url' => $deckUrl]
            );

            $reply =
                "✅ **Đã tạo bộ flash card thành công!**\n\n" .
                "🃏 **{$deck->name}**\n" .
                "• Môn học: {$deck->subject}\n" .
                "• {$cardCount} thẻ\n" .
                "• {$deck->description}\n\n" .
                "🔗 [Bắt đầu ôn tập ngay]({$deckUrl})";

            return [$reply, "[create_flashcard:id={$deck->id}] Đã tạo \"{$deck->name}\" với {$cardCount} thẻ."];

        } catch (\Exception $e) {
            Log::error('handleFlashcardCreation: DB exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return [
                "❌ Lỗi khi lưu flash card:\n`{$e->getMessage()}`",
                "[create_flashcard DB error] " . $e->getMessage(),
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LEGACY ENDPOINTS
    // ─────────────────────────────────────────────────────────────────────────

    /** POST /api/chatbot (non-streaming) */
    public function chat(Request $request): JsonResponse
    {
        try {
            $request->validate(['message' => 'required|string|max:2000']);
            $userId  = auth()->id();
            $message = $request->input('message');

            try {
                Message::create(['user_id' => $userId, 'role' => 'user', 'content' => $message]);
            } catch (\Exception $e) {
                Log::warning('Cannot persist user message: ' . $e->getMessage());
            }

            $history      = $this->getHistoryFromDb($userId, 20);
            $context      = $this->buildContext($history);
            $systemPrompt = $this->promptDefault($context);
            $reply        = $this->aiService->requestOllama($message, $systemPrompt);

            if (!$reply) {
                return response()->json(['success' => false, 'reply' => 'Xin lỗi, đang gặp sự cố.'], 503);
            }

            try {
                Message::create(['user_id' => $userId, 'role' => 'ai', 'content' => $reply]);
            } catch (\Exception $e) {
                Log::warning('Cannot persist assistant message: ' . $e->getMessage());
            }

            return response()->json(['success' => true, 'reply' => $reply]);

        } catch (\Exception $e) {
            Log::error('Chatbot chat error: ' . $e->getMessage());
            return response()->json(['success' => false, 'reply' => 'Đã xảy ra lỗi.'], 500);
        }
    }

    /** POST /api/chatbot/flashcard */
    public function createFlashcard(Request $request): JsonResponse
    {
        try {
            $request->validate(['topic' => 'required|string|max:500']);
            $flashcard = $this->aiService->createFlashcard($request->input('topic'));

            if (isset($flashcard['error'])) {
                return response()->json(['success' => false, 'message' => $flashcard['error']], 400);
            }

            return response()->json(['success' => true, 'data' => $flashcard]);
        } catch (\Exception $e) {
            Log::error('Flashcard Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Lỗi khi tạo flashcard'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TOKEN HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Ước tính số token từ một đoạn text.
     * Công thức đơn giản: ~4 ký tự = 1 token (chuẩn OpenAI/LLaMA).
     */
    private function estimateTokens(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }

    /**
     * Kiểm tra user còn đủ token không.
     * Trả về true nếu còn, false nếu hết hoặc không có log.
     */
    private function hasEnoughTokens(int $userId, int $required = 1): bool
    {
        $log = UserLog::where('user_id', $userId)->first();
        if (!$log) return false;                        // không có log → chặn
        return $log->token_limit >= $required;
    }

    /**
     * Trừ token sau khi AI trả lời xong.
     * Trừ theo tổng: token(user message) + token(AI reply).
     * Không cho phép về âm.
     */
    private function consumeTokens(int $userId, string $userMessage, string $aiReply): void
    {
        $used = $this->estimateTokens($userMessage) + $this->estimateTokens($aiReply);

        $affected = UserLog::where('user_id', $userId)
            ->where('token_limit', '>', 0)
            ->decrement('token_limit', $used);

        // Nếu decrement vượt quá số dư (edge case race condition), clamp về 0
        if (!$affected) {
            UserLog::where('user_id', $userId)
                ->update(['token_limit' => 0]);
        }

        Log::info('Token consumed', [
            'user_id'   => $userId,
            'used'      => $used,
            'breakdown' => [
                'user_msg' => $this->estimateTokens($userMessage),
                'ai_reply' => $this->estimateTokens($aiReply),
            ],
        ]);
    }
}