<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentEmbedding;
use App\Services\EmbeddingService;
use App\Services\TextChunkerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateDocumentEmbeddings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(private int $documentId) {}

    public function handle(TextChunkerService $chunker, EmbeddingService $embedder): void
    {
        $document = Document::find($this->documentId);

        if (!$document || empty($document->extracted_text)) {
            Log::info('Bỏ qua embedding: không có extracted_text', ['document_id' => $this->documentId]);
            return;
        }

        // Xoá embedding cũ nếu tài liệu được re-approve / cập nhật
        DocumentEmbedding::where('document_id', $document->id)->delete();

        $chunks = $chunker->chunk($document->extracted_text);
        if (empty($chunks)) return;

        // Batch để giảm số lần gọi API
        $vectors = $embedder->embedBatch($chunks);

        foreach ($chunks as $i => $chunk) {
            if (empty($vectors[$i])) continue;

            DocumentEmbedding::create([
                'document_id' => $document->id,
                'chunk_index' => $i,
                'chunk_text'  => $chunk,
                'embedding'   => $vectors[$i],
            ]);
        }

        Log::info('Đã sinh embedding cho tài liệu', [
            'document_id' => $document->id,
            'chunks' => count($chunks),
        ]);
    }
}