<?php

namespace App\Services;

use App\Models\Document;
use App\Models\RelatedDocument;
use Illuminate\Support\Collection;

class DocumentRecommendationService
{
    /**
     * Gợi ý dựa trên 1 tài liệu cụ thể (trang "Chi tiết tài liệu" → "Có thể bạn quan tâm").
     * Kết hợp: RAG similarity (đã precompute) + cùng tag + độ phổ biến.
     */
    public function relatedTo(Document $document, int $limit = 6): Collection
    {
        // 1. Lấy candidate từ RAG (đã precompute, đọc nhanh)
        $ragIds = RelatedDocument::where('document_id', $document->id)
            ->orderByDesc('score')
            ->limit($limit * 2)
            ->pluck('related_document_id', 'score');

        // 2. Lấy candidate từ FULLTEXT / cùng tag (đã có sẵn, giữ nguyên logic cũ của bạn)
        $tagIds = Document::where('status', 'approved')
            ->where('id', '!=', $document->id)
            ->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $document->tags->pluck('id')))
            ->limit($limit * 2)
            ->pluck('id');

        // 3. Hợp nhất, tính điểm tổng hợp (weighted)
        $candidates = collect($ragIds->keys())->merge($tagIds)->unique();

        $docs = Document::whereIn('id', $candidates)
            ->where('status', 'approved')
            ->get()
            ->map(function ($doc) use ($ragIds, $tagIds, $document) {
                $ragScore = $ragIds->search($doc->id) !== false
                    ? $ragIds->flip()->get($doc->id, 0)
                    : 0;
                $sameTagBonus = $tagIds->contains($doc->id) ? 0.15 : 0;
                $popularityScore = min($doc->downloads / 100, 0.2); // chuẩn hoá, tránh doc cũ áp đảo

                $doc->recommend_score = ($ragScore * 0.6) + $sameTagBonus + $popularityScore;
                return $doc;
            })
            ->sortByDesc('recommend_score')
            ->take($limit);

        return $docs;
    }

    /**
     * Gợi ý theo truy vấn tự do (dùng cho chatbot: "gợi ý tài liệu về đạo hàm").
     * Đây là nơi duy nhất cần embedding real-time — chấp nhận được vì tần suất thấp hơn nhiều so với page view.
     */
    public function searchByQuery(string $query, int $limit = 5): Collection
    {
        $embedder = app(EmbeddingService::class);
        $queryVector = $embedder->embed($query);

        if (!$queryVector) {
            \Illuminate\Support\Facades\Log::warning('DocumentRecommendationService: embed() failed, dùng fulltext fallback', [
                'query' => $query,
            ]);
            return $this->fulltextFallback($query, $limit);
        }

        $documents = Document::where('status', 'approved')
            ->with('embeddings')
            ->get();

        // ⚠ Ngưỡng nâng từ 0.5 → 0.65, và log điểm số để bạn tinh chỉnh dựa trên dữ liệu thật
        $threshold = 0.65;

        $scored = $documents->map(function ($doc) use ($embedder, $queryVector) {
            if ($doc->embeddings->isEmpty()) {
                $doc->relevance = 0;
                return $doc;
            }

            $best = $doc->embeddings->map(
                fn ($e) => $embedder->cosineSimilarity($queryVector, $e->embedding)
            )->max();

            $doc->relevance = $best;
            return $doc;
        })
        ->filter(fn ($d) => $d->relevance > $threshold)
        ->sortByDesc('relevance')
        ->take($limit);

        \Illuminate\Support\Facades\Log::info('DocumentRecommendationService: search scores', [
            'query'     => $query,
            'threshold' => $threshold,
            'top_scores'=> $scored->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'score' => round($d->relevance, 4)])->values()->all(),
        ]);

        return $scored;
    }

    private function fulltextFallback(string $query, int $limit): Collection
    {
        // Tách query thành các từ có nghĩa (bỏ từ quá ngắn/stop-word phổ biến tiếng Việt)
        $stopWords = ['tài', 'liệu', 'về', 'của', 'và', 'là', 'cho', 'với', 'các', 'một', 'những'];
        $words = collect(preg_split('/\s+/u', trim($query)))
            ->map(fn ($w) => trim($w))
            ->filter(fn ($w) => mb_strlen($w) >= 2 && !in_array(mb_strtolower($w), $stopWords))
            ->values();

        if ($words->isEmpty()) {
            return collect();
        }

        // Boolean mode: yêu cầu match CÀNG NHIỀU từ khóa CÀNG TỐT, không chỉ 1 từ ngẫu nhiên là đủ.
        // Dùng '+' để bắt buộc các từ khóa chính (>=4 ký tự, tránh bị FULLTEXT bỏ qua) phải xuất hiện.
        $required = $words->filter(fn ($w) => mb_strlen($w) >= 4);
        $boolQuery = $required->isNotEmpty()
            ? $required->map(fn ($w) => '+' . $w . '*')->implode(' ')
            : $words->map(fn ($w) => $w . '*')->implode(' ');

        $results = Document::where('status', 'approved')
            ->whereRaw(
                "MATCH(name, description) AGAINST(? IN BOOLEAN MODE)",
                [$boolQuery]
            )
            ->limit($limit)
            ->get();

        // Nếu boolean mode với '+' không ra kết quả (quá khắt khe), nới lỏng về natural language
        // nhưng vẫn yêu cầu tên tài liệu chứa ít nhất 1 từ khóa chính — tránh trả bừa.
        if ($results->isEmpty() && $required->isNotEmpty()) {
            $results = Document::where('status', 'approved')
                ->where(function ($q) use ($required) {
                    foreach ($required as $word) {
                        $q->orWhere('name', 'like', "%{$word}%");
                    }
                })
                ->limit($limit)
                ->get();
        }

        return $results;
    }
}