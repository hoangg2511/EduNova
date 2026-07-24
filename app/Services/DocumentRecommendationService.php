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
            // fallback về FULLTEXT nếu Gemini lỗi/rate limit
            return $this->fulltextFallback($query, $limit);
        }

        // So sánh với vector trung bình từng tài liệu (cache trong bảng riêng nếu cần tối ưu thêm)
        $documents = Document::where('status', 'approved')
            ->with('embeddings') // hasMany DocumentEmbedding
            ->get();

        $scored = $documents->map(function ($doc) use ($embedder, $queryVector) {
            if ($doc->embeddings->isEmpty()) {
                $doc->relevance = 0;
                return $doc;
            }

            // Lấy điểm cao nhất trong các chunk (tài liệu dài vẫn được tìm đúng đoạn liên quan)
            $best = $doc->embeddings->map(
                fn ($e) => $embedder->cosineSimilarity($queryVector, $e->embedding)
            )->max();

            $doc->relevance = $best;
            return $doc;
        })
        ->filter(fn ($d) => $d->relevance > 0.5)
        ->sortByDesc('relevance')
        ->take($limit);

        return $scored;
    }

    private function fulltextFallback(string $query, int $limit): Collection
    {
        return Document::where('status', 'approved')
            ->whereRaw("MATCH(name, description) AGAINST(? IN NATURAL LANGUAGE MODE)", [$query])
            ->limit($limit)
            ->get();
    }
}