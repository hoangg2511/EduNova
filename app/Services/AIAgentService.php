<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIAgentService
{
    protected $apiUrl;
    protected $model;

    public function __construct()
    {
        $this->apiUrl = 'http://localhost:11434/api/generate';
        $this->model = 'llama3.1:8b';
    }

    /**
     * Hàm gọi AI tập trung để tái sử dụng
     */
    public function requestOllama(string $prompt, string $systemPrompt): ?string
    {
        try {
            $response = Http::timeout(300)->post($this->apiUrl, [
                'model'  => $this->model,
                'prompt' => $systemPrompt . "\n\nInput: " . $prompt,
                'stream' => false
            ]);

            return $response->json()['response'] ?? null;
        } catch (\Exception $e) {
            Log::error("Ollama API Error: " . $e->getMessage());
            return null;
        }
    }

  public function streamOllama(string $prompt, string $systemPrompt, callable $onChunk): void
    {
        try {
            $client = new \GuzzleHttp\Client(['timeout' => 180, 'http_errors' => false]);

            $response = $client->post($this->apiUrl, [
                'json' => [
                    'model' => $this->model,
                    'prompt' => $systemPrompt . "\n\nInput: " . $prompt,
                    'stream' => true,
                ],
                'stream' => true,
            ]);

            // Lấy stream gốc từ Guzzle
            $stream = $response->getBody()->detach();

            while (!feof($stream)) {
                // Đọc từng dòng, dừng ngay khi gặp \n
                $line = fgets($stream);
                
                if ($line === false || trim($line) === '') continue;

                $json = json_decode($line, true);
                if ($json && isset($json['response'])) {
                    $onChunk($json['response']);
                    
                    // Đẩy dữ liệu ra bộ đệm ngay lập tức
                    if (ob_get_level()) ob_flush();
                    flush();
                }
            }
            fclose($stream);
            
        } catch (\Exception $e) {
            Log::error("Ollama Streaming Error: " . $e->getMessage());
        }
    }

    /**
     * 1. Hàm tạo Flashcard (Trả về mảng JSON)
     */
    public function createFlashcard(string $topic): array
    {
        $system = "Bạn là trợ lý học tập. Hãy tạo 1 flashcard duy nhất về chủ đề này. " .
                  "Trả về đúng định dạng JSON: {\"question\": \"nội dung câu hỏi\", \"answer\": \"nội dung câu trả lời\"}";
        
        $result = $this->requestOllama($topic, $system);
        return json_decode($result, true) ?? ['error' => 'Không thể tạo flashcard'];
    }

    /**
     * 2. Hàm xây dựng thời khóa biểu (Trả về mảng JSON)
     */
    public function createSchedule(string $taskList): array
    {
        $system = "Sắp xếp danh sách công việc sau vào thời khóa biểu logic trong ngày. " .
                  "Trả về JSON dạng: [{\"time\": \"08:00\", \"activity\": \"...\"}, ...]";
        
        $result = $this->requestOllama($taskList, $system);
        return json_decode($result, true) ?? ['error' => 'Không thể tạo lịch trình'];
    }

    /**
     * 3. Hàm gợi ý tài liệu (RAG mẫu)
     */
    public function getDocumentRecommendation(string $userQuestion, string $context): string
    {
        $system = "Dựa vào đoạn tài liệu sau đây, hãy trả lời câu hỏi của người dùng. " .
                  "Nếu trong tài liệu không có thông tin, hãy nói bạn không biết. " .
                  "Tài liệu: " . $context;
        
        return $this->requestOllama($userQuestion, $system) ?? "Đang gặp sự cố khi truy xuất tài liệu.";
    }
    public function testConnection(): array
    {
        try {
            $response = Http::timeout(10)->post($this->apiUrl, [
                'model' => $this->model,
                'prompt' => 'ping',
                'stream' => false
            ]);

            if ($response->successful()) {
                return ['status' => 'success', 'message' => 'Kết nối thành công!'];
            }

            // CẬP NHẬT: Trả về nội dung lỗi để debug
            return [
                'status' => 'error', 
                'message' => 'Ollama phản hồi lỗi: ' . $response->status(),
                'debug_info' => $response->body() // Đây là chìa khóa
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Lỗi kết nối: ' . $e->getMessage()];
        }
    }

    private function estimateTokens(string $text): int
    {
        // Quy tắc: 1 từ trung bình ~ 1.3 token (tùy model)
        $wordCount = str_word_count($text);
        return (int) ceil($wordCount * 1.3);
    }
}