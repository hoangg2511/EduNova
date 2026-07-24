<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentEmbedding;
use App\Models\RelatedDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateRelatedDocuments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    /** Số lượng tài liệu liên quan tối đa lưu cho mỗi document */
    private const TOP_N = 5;

    /** Ngưỡng similarity tối thiểu để coi là "liên quan", tránh lưu rác điểm quá thấp */
    private const MIN_SCORE = 0.5;

    public function __construct(private int $documentId) {}

    public function handle(): void
    {
        if (! Document::where('id', $this->documentId)->exists()) {
            Log::info('GenerateRelatedDocuments: document không tồn tại', ['document_id' => $this->documentId]);
            return;
        }

        $centroid = $this->centroidFromVectors(
            DocumentEmbedding::where('document_id', $this->documentId)->pluck('embedding')
        );

        if ($centroid === null) {
            Log::info('GenerateRelatedDocuments: document chưa có embedding, bỏ qua', ['document_id' => $this->documentId]);
            return;
        }

        // Danh sách document approved khác, đã có ít nhất 1 embedding
        $candidateIds = Document::approved()
            ->where('id', '!=', $this->documentId)
            ->whereHas('embeddings')
            ->pluck('id');

        if ($candidateIds->isEmpty()) {
            Log::info('GenerateRelatedDocuments: không có candidate để so sánh', ['document_id' => $this->documentId]);
            return;
        }

        // 1 query duy nhất lấy toàn bộ embedding của các candidate, tránh N+1
        $embeddingsByDoc = DocumentEmbedding::whereIn('document_id', $candidateIds)
            ->get(['document_id', 'embedding'])
            ->groupBy('document_id');

        $scores = [];
        foreach ($embeddingsByDoc as $candidateId => $rows) {
            $candidateCentroid = $this->centroidFromVectors($rows->pluck('embedding'));
            if ($candidateCentroid === null) continue;

            $score = $this->cosineSimilarity($centroid, $candidateCentroid);
            if ($score >= self::MIN_SCORE) {
                $scores[$candidateId] = $score;
            }
        }

        arsort($scores);
        $top = array_slice($scores, 0, self::TOP_N, true);

        DB::transaction(function () use ($top) {
            // Xoá related cũ của document này trước — tránh giữ rác khi re-approve / nội dung đổi
            RelatedDocument::where('document_id', $this->documentId)->delete();

            foreach ($top as $relatedId => $score) {
                RelatedDocument::create([
                    'document_id'         => $this->documentId,
                    'related_document_id' => $relatedId,
                    'score'               => round($score, 4),
                ]);
            }
        });

        Log::info('GenerateRelatedDocuments: đã tính xong', [
            'document_id'   => $this->documentId,
            'related_count' => count($top),
        ]);
    }

    /**
     * Vector trung tâm (centroid) = trung bình cộng các chunk embedding.
     * Trả về null nếu không có vector nào.
     *
     * @param Collection<int, array> $vectors
     */
    private function centroidFromVectors(Collection $vectors): ?array
    {
        if ($vectors->isEmpty()) {
            return null;
        }

        $dimensions = count($vectors->first());
        $sum = array_fill(0, $dimensions, 0.0);

        foreach ($vectors as $vector) {
            foreach ($vector as $i => $val) {
                $sum[$i] += (float) $val;
            }
        }

        $count = $vectors->count();
        return array_map(fn ($v) => $v / $count, $sum);
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $val) {
            $bVal = $b[$i] ?? 0.0;
            $dot   += $val * $bVal;
            $normA += $val ** 2;
            $normB += $bVal ** 2;
        }

        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}