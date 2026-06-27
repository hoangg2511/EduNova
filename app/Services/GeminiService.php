<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\UserLog;
use Illuminate\Support\Facades\DB;
class GeminiService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;
    private int    $retries;

    private const TREE_SCHEMA = <<<JSON
    {
        "ten_chuyen_de": "string",
        "mo_ta": "string (mô tả tổng quan toàn bộ chủ đề)",
        "cac_chu_de_lon": [
            {
            "ten": "string (tên chủ đề lớn, ví dụ: Đại từ, Liên từ...)",
            "mo_ta": "string (mô tả vai trò và ý nghĩa của chủ đề lớn)",
            "cac_chu_de_con": [
                {
                "ten": "string (tên cụ thể, không chung chung)",
                "noi_dung": "string (giải thích chi tiết)",
                "cong_thuc": "string (cấu trúc/công thức hoặc quy tắc)",
                "vi_du": "string (ví dụ minh họa thực tế, câu hoàn chỉnh)",
                "trong_so": {
                    "thoi_gian_hoc_gio": "int (thang do 1-10, 1 là dễ nhất)",
                    "muc_do_uu_tien": "string (cao, trung bình, thấp)", 
                    "do_kho": "int (thang do 1-10, 1 là dễ nhất)"
                }
                }
            ]
            }
        ]
    }
    JSON;

    public function __construct()
    {
        $this->apiKey  = config('services.gemini.key');
        $this->model   = config('services.gemini.model', 'gemini-2.5-flash');
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
        $this->retries = 3;
    }

    // ──────────────────────────────────────────────────────────────
    // Nhận chủ đề → trả về cây kiến thức JSON ngay lập tức
    // ──────────────────────────────────────────────────────────────
    public function generateKnowledgeTree(string $topic): ?array
    {
        $history = [
            [
                'role'  => 'user',
                'parts' => [['text' => "Chủ đề: {$topic}"]],
            ],
        ];

        $response = $this->callGemini($history, $topic);

        return $response['result'] ?: null;
    }

    // ──────────────────────────────────────────────────────────────
    // SYSTEM PROMPT
    // ──────────────────────────────────────────────────────────────
    private function buildSystemPrompt(string $topic): string
    {
        $treeSchema = self::TREE_SCHEMA;

        return <<<PROMPT
            Bạn là chuyên gia xây dựng lộ trình học tập. Nhiệm vụ: nhận chủ đề và sinh ngay cây kiến thức JSON chi tiết.

            Chủ đề: {$topic}

            === QUY TẮC SINH CÂY KIẾN THỨC ===
            - Chia chủ đề thành 3-5 "cac_chu_de_lon" (nhóm lớn có ý nghĩa, không chung chung).
            - Mỗi "chu_de_lon" có 3-5 "cac_chu_de_con" cụ thể.
            - Mỗi mục con BẮT BUỘC có đủ 4 trường: ten, noi_dung, cong_thuc, vi_du.
            - "cong_thuc" là cấu trúc ngắn gọn (ví dụ: "S + V + Reflexive Pronoun").
            - "vi_du" phải là câu hoàn chỉnh, thực tế, sát với chủ đề.
            - Không được bỏ trống bất kỳ trường nào.

            === FORMAT TRẢ LỜI (JSON THUẦN TÚY, KHÔNG MARKDOWN) ===
            {$treeSchema}
            PROMPT;
    }

    // ──────────────────────────────────────────────────────────────
    // GỌI GEMINI API
    // ──────────────────────────────────────────────────────────────
    private function callGemini(array $history, string $topic): array
    {
        $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

        $body = [
            'system_instruction' => [
                'parts' => [['text' => $this->buildSystemPrompt($topic)]],
            ],
            'contents'         => $history,
            'generationConfig' => [
                'responseMimeType' => 'application/json',
            ],
        ];

        for ($attempt = 1; $attempt <= $this->retries; $attempt++) {
            try {
                $response = Http::timeout(60)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $body);

                if (in_array($response->status(), [429, 503])) {
                    $wait = $attempt * 20;
                    Log::warning("Gemini quota đầy, chờ {$wait}s (lần {$attempt}/{$this->retries})");
                    sleep($wait);
                    continue;
                }

                if ($response->failed()) {
                    Log::error('Gemini lỗi', ['status' => $response->status(), 'body' => $response->body()]);
                    return ['result' => [], 'history' => $history];
                }

                $text = $response->json('candidates.0.content.parts.0.text');

                if (!$text) {
                    Log::error('Gemini trả về rỗng', ['body' => $response->body()]);
                    return ['result' => [], 'history' => $history];
                }

                return [
                    'result'  => json_decode($text, true) ?? [],
                    'history' => $history,
                ];

            } catch (\Exception $e) {
                Log::error('Gemini exception', ['message' => $e->getMessage()]);
                return ['result' => [], 'history' => $history];
            }
        }

        return ['result' => [], 'history' => $history];
    }


    public static function consumeKnowledge(int $userId): bool
    {
        return DB::transaction(function () use ($userId) {
            // Lấy record và khóa dòng (lock for update) để tránh xung đột dữ liệu
            $userLog = UserLog::where('user_id', $userId)->lockForUpdate()->first();

            if ($userLog && $userLog->knowledge_limit > 0) {
                $userLog->decrement('knowledge_limit');
                return true;
            }

            return false;
        });
    }
}