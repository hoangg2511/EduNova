<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        $this->model = config('services.gemini.embedding_model');

    }

    /**
     * Trả về vector embedding (mảng float) cho 1 đoạn text.
     */
    public function embed(string $text): ?array
    {
        try {
            $response = Http::timeout(20)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:embedContent?key={$this->apiKey}",
                [
                    'model' => "models/{$this->model}",
                    'content' => ['parts' => [['text' => $text]]],
                    // // Khuyến nghị thêm task_type để tối ưu chất lượng embedding theo mục đích sử dụng
                    // 'taskType' => 'RETRIEVAL_DOCUMENT', // cho embed tài liệu
                ]
            );

            if (!$response->successful()) {
                Log::warning('Gemini embedding thất bại', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'model'  => $this->model,
                ]);
                return null;
            }

            return $response->json('embedding.values');
        } catch (\Throwable $e) {
            Log::error('Embedding exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Batch nhiều đoạn cùng lúc — nên dùng khi backfill để tiết kiệm số request.
     */
    public function embedBatch(array $texts): array
    {
        $requests = array_map(fn ($t) => [
            'model' => "models/{$this->model}",
            'content' => ['parts' => [['text' => $t]]],
        ], $texts);

        try {
            $response = Http::timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:batchEmbedContents?key={$this->apiKey}",
                ['requests' => $requests]
            );

            if (!$response->successful()) {
                Log::warning('Gemini batch embedding thất bại', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'model'  => $this->model,
                ]);
                return array_fill(0, count($texts), null);
            }

            return array_map(fn ($e) => $e['values'] ?? null, $response->json('embeddings', []));
        } catch (\Throwable $e) {
            Log::error('Batch embedding exception', ['error' => $e->getMessage()]);
            return array_fill(0, count($texts), null);
        }
    }

    public function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0; $normA = 0; $normB = 0;
        $len = min(count($a), count($b));

        for ($i = 0; $i < $len; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA == 0 || $normB == 0) return 0.0;

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}